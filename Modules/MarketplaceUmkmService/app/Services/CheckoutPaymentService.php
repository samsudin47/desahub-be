<?php

namespace Modules\MarketplaceUmkmService\Services;

use App\Facades\ResponseStandardAPI;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\IAMService\Models\User;
use Modules\MarketplaceService\Models\Product;
use Modules\MarketplaceUmkmService\Models\Checkout;
use Modules\MarketplaceUmkmService\Models\CheckoutItem;
use Modules\MarketplaceUmkmService\Models\CheckoutPayment;
use Modules\MarketplaceUmkmService\Models\CheckoutPaymentNotification;
use Modules\MarketplaceUmkmService\Models\CheckoutShipping;
use Modules\MarketplaceUmkmService\Services\Midtrans\MidtransSnapClient;
use Shared\Constants\ResponseTypeConstantsHelper;
use Throwable;

class CheckoutPaymentService
{
    public function __construct(private MidtransSnapClient $midtransSnapClient) {}

    /**
     * @return array{
     *     uuid: string,
     *     uuid_checkout: string,
     *     order_id: string,
     *     snap_token: string|null,
     *     client_key: string,
     *     is_production: bool,
     *     gross_amount: int,
     *     payment_type: string|null,
     *     bank: string|null,
     *     va_number: string|null,
     *     bill_key: string|null,
     *     biller_code: string|null,
     *     transaction_status: string|null,
     *     status: string,
     *     expired_at: string|null,
     *     paid_at: string|null
     * }
     */
    public function create(string $checkoutUuid): array
    {
        return DB::transaction(function () use ($checkoutUuid) {
            $checkout = $this->findPendingCheckoutForUserOrFail($checkoutUuid);
            $this->ensureShippingExists($checkout);

            $existingPayment = CheckoutPayment::query()
                ->notDeleted()
                ->pending()
                ->where('uuid_checkout', $checkout->uuid)
                ->whereNotNull('snap_token')
                ->lockForUpdate()
                ->latest('created_at')
                ->first();

            if ($existingPayment !== null) {
                return $this->formatPayment($existingPayment);
            }

            $checkout->loadMissing([
                'checkoutItems' => fn ($query) => $query->notDeleted(),
                'checkoutItems.product',
                'shipping',
                'user',
            ]);

            $grossAmount = (int) round((float) $checkout->total_price);
            $orderId = $this->generateOrderId($checkout);
            $params = $this->buildSnapParams($checkout, $orderId, $grossAmount);

            try {
                $snapToken = $this->midtransSnapClient->createSnapToken($params);
            } catch (Throwable $exception) {
                report($exception);

                throw new HttpResponseException(
                    ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                        ->info('Gagal membuat pembayaran Midtrans')
                        ->detail('Gagal membuat pembayaran Midtrans')
                        ->response()
                );
            }

            $payment = CheckoutPayment::query()->create([
                'uuid' => generateUuid(),
                'uuid_checkout' => $checkout->uuid,
                'order_id' => $orderId,
                'snap_token' => $snapToken,
                'gross_amount' => $grossAmount,
                'status' => 'pending',
                'transaction_status' => 'pending',
                'raw_response' => ['snap_params' => $params],
                'is_deleted' => false,
                'created_by' => getUserId(),
            ]);

            return $this->formatPayment($payment);
        });
    }

    /**
     * @return array{
     *     uuid: string,
     *     uuid_checkout: string,
     *     order_id: string,
     *     snap_token: string|null,
     *     client_key: string,
     *     is_production: bool,
     *     gross_amount: int,
     *     payment_type: string|null,
     *     bank: string|null,
     *     va_number: string|null,
     *     bill_key: string|null,
     *     biller_code: string|null,
     *     transaction_status: string|null,
     *     status: string,
     *     expired_at: string|null,
     *     paid_at: string|null
     * }
     */
    public function show(string $checkoutUuid): array
    {
        $checkout = $this->findCheckoutForUserOrFail($checkoutUuid);

        $payment = CheckoutPayment::query()
            ->notDeleted()
            ->where('uuid_checkout', $checkout->uuid)
            ->latest('created_at')
            ->first();

        if ($payment === null) {
            throw new HttpResponseException(
                ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                    ->info('Data pembayaran tidak ditemukan')
                    ->detail('Data pembayaran tidak ditemukan')
                    ->response()
            );
        }

        return $this->formatPayment($payment);
    }

    public function cancelPendingPaymentsForCheckout(string $checkoutUuid, string $reason = 'checkout_cancelled'): void
    {
        $payments = CheckoutPayment::query()
            ->notDeleted()
            ->pending()
            ->where('uuid_checkout', $checkoutUuid)
            ->lockForUpdate()
            ->get();

        foreach ($payments as $payment) {
            if ($payment->order_id) {
                $this->midtransSnapClient->cancelTransaction($payment->order_id);
            }

            $payment->update([
                'status' => 'cancelled',
                'transaction_status' => 'cancel',
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
                'snap_token' => null,
                'updated_by' => getUserId() ?? 'system',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{message: string}
     */
    public function handleNotification(array $payload, ?string $ipAddress = null): array
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $signatureKey = (string) ($payload['signature_key'] ?? '');
        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $payloadHash = hash('sha256', $orderId.'|'.$statusCode.'|'.$grossAmount.'|'.$transactionStatus.'|'.$signatureKey);

        // Anti-replay/spam: dedupe notifikasi identik dalam 2 menit
        $dedupeKey = 'midtrans:dedupe:'.$payloadHash;
        if (! Cache::add($dedupeKey, true, 120)) {
            return ['message' => 'Notifikasi duplikat diabaikan'];
        }

        $signatureValid = $this->midtransSnapClient->isValidSignature(
            $orderId,
            $statusCode,
            $grossAmount,
            $signatureKey
        );

        if (! $signatureValid) {
            $this->markIpInvalid($ipAddress);
            $this->storeRejectedNotification(
                payload: ['order_id' => $orderId, 'transaction_status' => $transactionStatus], // jangan simpan full payload palsu
                orderId: $orderId,
                transactionStatus: $transactionStatus,
                signatureValid: false,
                rejectReason: 'invalid_signature',
                ipAddress: $ipAddress,
                payloadHash: $payloadHash,
                httpStatus: 400,
            );

            throw new HttpResponseException(
                ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                    ->info('Signature Midtrans tidak valid')
                    ->detail('Signature Midtrans tidak valid')
                    ->response(400)
            );
        }

        return DB::transaction(function () use (
            $payload,
            $orderId,
            $grossAmount,
            $transactionStatus,
            $payloadHash,
            $ipAddress
        ) {
            $payment = CheckoutPayment::query()
                ->notDeleted()
                ->where('order_id', $orderId)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                $this->storeRejectedNotification(
                    payload: $payload,
                    orderId: $orderId,
                    transactionStatus: $transactionStatus,
                    signatureValid: true,
                    rejectReason: 'payment_not_found',
                    ipAddress: $ipAddress,
                    payloadHash: $payloadHash,
                    httpStatus: 400,
                );

                throw new HttpResponseException(
                    ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                        ->info('Data pembayaran tidak ditemukan')
                        ->detail('Data pembayaran tidak ditemukan')
                        ->response(400)
                );
            }

            if ($this->normalizeAmount($grossAmount) !== (int) $payment->gross_amount) {
                $this->storeRejectedNotification(
                    payload: $payload,
                    orderId: $orderId,
                    transactionStatus: $transactionStatus,
                    signatureValid: true,
                    rejectReason: 'amount_mismatch',
                    ipAddress: $ipAddress,
                    payloadHash: $payloadHash,
                    httpStatus: 400,
                    paymentUuid: $payment->uuid,
                );

                throw new HttpResponseException(
                    ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                        ->info('Nominal pembayaran tidak sesuai')
                        ->detail('Nominal pembayaran tidak sesuai')
                        ->response(400)
                );
            }

            try {
                $statusFromApi = $this->midtransSnapClient->getTransactionStatus($orderId);
            } catch (Throwable $exception) {
                report($exception);

                $this->storeRejectedNotification(
                    payload: $payload,
                    orderId: $orderId,
                    transactionStatus: $transactionStatus,
                    signatureValid: true,
                    rejectReason: 'status_api_failed',
                    ipAddress: $ipAddress,
                    payloadHash: $payloadHash,
                    httpStatus: 502,
                    paymentUuid: $payment->uuid,
                );

                throw new HttpResponseException(
                    ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                        ->info('Gagal verifikasi status Midtrans')
                        ->detail('Gagal verifikasi status Midtrans')
                        ->response(502)
                );
            }

            $apiOrderId = (string) ($statusFromApi['order_id'] ?? '');
            $apiStatus = (string) ($statusFromApi['transaction_status'] ?? '');
            $apiGross = (string) ($statusFromApi['gross_amount'] ?? '');
            $apiFraud = isset($statusFromApi['fraud_status']) ? (string) $statusFromApi['fraud_status'] : null;

            if (
                $apiOrderId !== $orderId
                || $this->normalizeAmount($apiGross) !== (int) $payment->gross_amount
            ) {
                $this->storeRejectedNotification(
                    payload: $payload,
                    orderId: $orderId,
                    transactionStatus: $transactionStatus,
                    signatureValid: true,
                    rejectReason: 'status_api_mismatch',
                    ipAddress: $ipAddress,
                    payloadHash: $payloadHash,
                    httpStatus: 400,
                    paymentUuid: $payment->uuid,
                    verifiedViaApi: true,
                );

                throw new HttpResponseException(
                    ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                        ->info('Status Midtrans tidak sesuai')
                        ->detail('Status Midtrans tidak sesuai')
                        ->response(400)
                );
            }

            $notification = CheckoutPaymentNotification::query()->create([
                'uuid' => generateUuid(),
                'uuid_checkout_payment' => $payment->uuid,
                'order_id' => $orderId,
                'transaction_status' => $apiStatus,
                'payload' => $payload,
                'signature_valid' => true,
                'reject_reason' => null,
                'verified_via_api' => true,
                'ip_address' => $ipAddress,
                'payload_hash' => $payloadHash,
                'http_status' => 200,
                'processed_at' => null,
            ]);

            $payment->fill([
                'last_status_payload' => $statusFromApi,
                'verified_at' => now(),
                'verification_source' => 'status_api',
                'updated_by' => 'midtrans',
            ]);

            if ($payment->status === 'paid') {
                $notification->update(['processed_at' => now()]);
                $payment->save();

                return ['message' => 'Pembayaran sudah diproses'];
            }

            // Gunakan status dari API, bukan body mentah
            $this->applyNotificationToPayment($payment, array_merge($payload, [
                'transaction_status' => $apiStatus,
                'fraud_status' => $apiFraud,
                'payment_type' => $statusFromApi['payment_type'] ?? ($payload['payment_type'] ?? null),
                'transaction_id' => $statusFromApi['transaction_id'] ?? ($payload['transaction_id'] ?? null),
            ]));

            $notification->update(['processed_at' => now()]);

            return ['message' => 'Notifikasi berhasil diproses'];
        });
    }

    private function normalizeAmount(string $amount): int
    {
        return (int) round((float) $amount);
    }

    private function markIpInvalid(?string $ipAddress): void
    {
        if ($ipAddress === null || $ipAddress === '') {
            return;
        }

        $counterKey = 'midtrans:invalid:'.$ipAddress;
        $count = (int) Cache::increment($counterKey);

        if ($count === 1) {
            Cache::put($counterKey, 1, 300);
        }

        if ($count >= 20) {
            Cache::put('midtrans:block:'.$ipAddress, true, 900);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function storeRejectedNotification(
        array $payload,
        string $orderId,
        string $transactionStatus,
        bool $signatureValid,
        string $rejectReason,
        ?string $ipAddress,
        string $payloadHash,
        int $httpStatus,
        ?string $paymentUuid = null,
        bool $verifiedViaApi = false,
    ): void {
        // Batasi tulis DB saat flood: max 1 row / hash / 5 menit
        $writeKey = 'midtrans:reject-write:'.$payloadHash;
        if (! Cache::add($writeKey, true, 300)) {
            return;
        }

        CheckoutPaymentNotification::query()->create([
            'uuid' => generateUuid(),
            'uuid_checkout_payment' => $paymentUuid,
            'order_id' => $orderId !== '' ? $orderId : null,
            'transaction_status' => $transactionStatus !== '' ? $transactionStatus : null,
            'payload' => $payload,
            'signature_valid' => $signatureValid,
            'reject_reason' => $rejectReason,
            'verified_via_api' => $verifiedViaApi,
            'ip_address' => $ipAddress,
            'payload_hash' => $payloadHash,
            'http_status' => $httpStatus,
            'processed_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function applyNotificationToPayment(CheckoutPayment $payment, array $payload): void
    {
        $transactionStatus = (string) ($payload['transaction_status'] ?? '');
        $fraudStatus = isset($payload['fraud_status']) ? (string) $payload['fraud_status'] : null;
        $paymentType = isset($payload['payment_type']) ? (string) $payload['payment_type'] : null;
        $transactionId = isset($payload['transaction_id']) ? (string) $payload['transaction_id'] : null;

        [$bank, $vaNumber, $billKey, $billerCode] = $this->extractVirtualAccountDetails($payload);

        $payment->fill([
            'payment_type' => $paymentType,
            'bank' => $bank,
            'va_number' => $vaNumber,
            'bill_key' => $billKey,
            'biller_code' => $billerCode,
            'transaction_id' => $transactionId,
            'transaction_status' => $transactionStatus,
            'fraud_status' => $fraudStatus,
            'raw_response' => array_merge($payment->raw_response ?? [], [
                'last_notification' => $payload,
            ]),
            'updated_by' => 'midtrans',
        ]);

        if (in_array($transactionStatus, ['capture', 'settlement'], true)
            && ($fraudStatus === null || $fraudStatus === 'accept')
        ) {
            $this->markPaymentAsPaid($payment);

            return;
        }

        if ($transactionStatus === 'pending') {
            $payment->status = 'pending';
            $payment->save();

            return;
        }

        if ($transactionStatus === 'expire') {
            $payment->status = 'expired';
            $payment->save();

            return;
        }

        if (in_array($transactionStatus, ['deny', 'failure'], true)) {
            $payment->status = 'failed';
            $payment->save();

            return;
        }

        if ($transactionStatus === 'cancel') {
            $payment->status = 'cancelled';
            $payment->save();
        }
    }

    private function markPaymentAsPaid(CheckoutPayment $payment): void
    {
        $checkout = Checkout::query()
            ->notDeleted()
            ->where('uuid', $payment->uuid_checkout)
            ->lockForUpdate()
            ->first();

        if ($checkout === null) {
            return;
        }

        // Celah cancel-then-pay ditutup di sini
        if ($checkout->status !== 'pending') {
            $payment->fill([
                'transaction_status' => $payment->transaction_status,
                'updated_by' => 'midtrans',
            ]);
            $payment->save();

            return;
        }

        $payment->status = 'paid';
        $payment->paid_at = now();
        $payment->save();

        $this->deductStockForCheckout($checkout);

        $checkout->update([
            'status' => 'paid',
            'updated_by' => 'midtrans',
        ]);
    }

    private function deductStockForCheckout(Checkout $checkout): void
    {
        $items = CheckoutItem::query()
            ->notDeleted()
            ->where('uuid_checkout', $checkout->uuid)
            ->get();

        $quantityByProduct = $items
            ->groupBy('uuid_product')
            ->map(fn ($grouped) => (int) $grouped->sum('quantity'));

        foreach ($quantityByProduct as $productUuid => $quantity) {
            $product = Product::query()
                ->notDeleted()
                ->where('uuid', $productUuid)
                ->lockForUpdate()
                ->first();

            if ($product === null) {
                continue;
            }

            $product->update([
                'stock' => max(0, (int) $product->stock - $quantity),
                'updated_by' => 'midtrans',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0: string|null, 1: string|null, 2: string|null, 3: string|null}
     */
    private function extractVirtualAccountDetails(array $payload): array
    {
        $bank = null;
        $vaNumber = null;
        $billKey = null;
        $billerCode = null;

        if (isset($payload['va_numbers'][0]) && is_array($payload['va_numbers'][0])) {
            $bank = isset($payload['va_numbers'][0]['bank'])
                ? (string) $payload['va_numbers'][0]['bank']
                : null;
            $vaNumber = isset($payload['va_numbers'][0]['va_number'])
                ? (string) $payload['va_numbers'][0]['va_number']
                : null;
        }

        if (isset($payload['permata_va_number'])) {
            $bank = $bank ?? 'permata';
            $vaNumber = (string) $payload['permata_va_number'];
        }

        if (isset($payload['biller_code']) || isset($payload['bill_key'])) {
            $bank = $bank ?? 'mandiri';
            $billerCode = isset($payload['biller_code']) ? (string) $payload['biller_code'] : null;
            $billKey = isset($payload['bill_key']) ? (string) $payload['bill_key'] : null;
        }

        return [$bank, $vaNumber, $billKey, $billerCode];
    }

    private function findCheckoutForUserOrFail(string $uuid): Checkout
    {
        $checkout = Checkout::query()
            ->notDeleted()
            ->where('uuid_user', getUserId())
            ->where('uuid', $uuid)
            ->first();

        if ($checkout === null) {
            throw new HttpResponseException(
                ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                    ->info('Data checkout tidak ditemukan')
                    ->detail('Data checkout tidak ditemukan')
                    ->response()
            );
        }

        return $checkout;
    }

    private function findPendingCheckoutForUserOrFail(string $uuid): Checkout
    {
        $checkout = $this->findCheckoutForUserOrFail($uuid);

        if ($checkout->status !== 'pending') {
            throw new HttpResponseException(
                ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                    ->info('Pembayaran tidak dapat dibuat')
                    ->detail('Pembayaran hanya dapat dibuat pada checkout berstatus pending')
                    ->response()
            );
        }

        return $checkout;
    }

    private function ensureShippingExists(Checkout $checkout): void
    {
        $shipping = CheckoutShipping::query()
            ->notDeleted()
            ->where('uuid_checkout', $checkout->uuid)
            ->first();

        if ($shipping === null) {
            throw new HttpResponseException(
                ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                    ->info('Data pengiriman belum diisi')
                    ->detail('Lengkapi data pengiriman sebelum melanjutkan pembayaran')
                    ->response()
            );
        }
    }

    private function generateOrderId(Checkout $checkout): string
    {
        $prefix = 'DH-'.str_replace('-', '', substr($checkout->uuid, 0, 8));
        $suffix = now()->format('ymdHis');

        return substr($prefix.'-'.$suffix, 0, 50);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSnapParams(Checkout $checkout, string $orderId, int $grossAmount): array
    {
        /** @var User|null $user */
        $user = $checkout->user;
        /** @var CheckoutShipping|null $shipping */
        $shipping = $checkout->shipping;

        $itemDetails = $checkout->checkoutItems
            ->map(function (CheckoutItem $item) {
                return [
                    'id' => $item->uuid_product,
                    'price' => (int) $item->harga_satuan,
                    'quantity' => (int) $item->quantity,
                    'name' => substr((string) ($item->product?->nama_product ?? 'Produk'), 0, 50),
                ];
            })
            ->values()
            ->all();

        return [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'item_details' => $itemDetails,
            'customer_details' => [
                'first_name' => $shipping?->nama_penerima ?? $user?->username ?? 'Customer',
                'email' => $user?->email ?? 'customer@desahub.local',
                'phone' => $shipping?->no_hp_penerima ?? '080000000000',
                'shipping_address' => [
                    'first_name' => $shipping?->nama_penerima,
                    'phone' => $shipping?->no_hp_penerima,
                    'address' => $shipping?->alamat_penerima,
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     uuid: string,
     *     uuid_checkout: string,
     *     order_id: string,
     *     snap_token: string|null,
     *     client_key: string,
     *     is_production: bool,
     *     gross_amount: int,
     *     payment_type: string|null,
     *     bank: string|null,
     *     va_number: string|null,
     *     bill_key: string|null,
     *     biller_code: string|null,
     *     transaction_status: string|null,
     *     status: string,
     *     expired_at: string|null,
     *     paid_at: string|null
     * }
     */
    private function formatPayment(CheckoutPayment $payment): array
    {
        return [
            'uuid' => $payment->uuid,
            'uuid_checkout' => $payment->uuid_checkout,
            'order_id' => $payment->order_id,
            'snap_token' => $payment->snap_token,
            'client_key' => $this->midtransSnapClient->clientKey(),
            'is_production' => $this->midtransSnapClient->isProduction(),
            'gross_amount' => (int) $payment->gross_amount,
            'payment_type' => $payment->payment_type,
            'bank' => $payment->bank,
            'va_number' => $payment->va_number,
            'bill_key' => $payment->bill_key,
            'biller_code' => $payment->biller_code,
            'transaction_status' => $payment->transaction_status,
            'status' => $payment->status,
            'expired_at' => $payment->expired_at?->toIso8601String(),
            'paid_at' => $payment->paid_at?->toIso8601String(),
        ];
    }
}
