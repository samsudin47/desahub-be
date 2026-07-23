<?php

namespace Modules\MarketplaceUmkmService\Services;

use App\Facades\ResponseStandardAPI;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Modules\MarketplaceUmkmService\Models\Checkout;
use Modules\MarketplaceUmkmService\Models\CheckoutShipping;
use Shared\Constants\ResponseTypeConstantsHelper;

class CheckoutShippingService
{
    /**
     * @param  array{
     *     nama_penerima: string,
     *     no_hp_penerima: string,
     *     alamat_penerima: string,
     *     latitude?: float|string|null,
     *     longitude?: float|string|null
     * }  $data
     * @return array{
     *     uuid: string,
     *     uuid_checkout: string,
     *     nama_penerima: string|null,
     *     no_hp_penerima: string|null,
     *     alamat_penerima: string|null,
     *     latitude: string|null,
     *     longitude: string|null
     * }
     */
    public function upsert(string $checkoutUuid, array $data): array
    {
        return DB::transaction(function () use ($checkoutUuid, $data) {
            $checkout = $this->findPendingCheckoutForUserOrFail($checkoutUuid);

            $shipping = CheckoutShipping::query()
                ->notDeleted()
                ->where('uuid_checkout', $checkout->uuid)
                ->lockForUpdate()
                ->first();

            if ($shipping === null) {
                $shipping = CheckoutShipping::query()->create([
                    'uuid' => generateUuid(),
                    'uuid_checkout' => $checkout->uuid,
                    'nama_penerima' => $data['nama_penerima'],
                    'no_hp_penerima' => $data['no_hp_penerima'],
                    'alamat_penerima' => $data['alamat_penerima'],
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'is_deleted' => false,
                    'created_by' => getUserId(),
                ]);
            } else {
                $shipping->update([
                    'nama_penerima' => $data['nama_penerima'],
                    'no_hp_penerima' => $data['no_hp_penerima'],
                    'alamat_penerima' => $data['alamat_penerima'],
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'updated_by' => getUserId(),
                ]);
            }

            return $this->formatShipping($shipping->fresh());
        });
    }

    /**
     * @return array{
     *     uuid: string,
     *     uuid_checkout: string,
     *     nama_penerima: string|null,
     *     no_hp_penerima: string|null,
     *     alamat_penerima: string|null,
     *     latitude: string|null,
     *     longitude: string|null
     * }
     */
    public function show(string $checkoutUuid): array
    {
        $checkout = $this->findCheckoutForUserOrFail($checkoutUuid);

        $shipping = CheckoutShipping::query()
            ->notDeleted()
            ->where('uuid_checkout', $checkout->uuid)
            ->first();

        if ($shipping === null) {
            throw new HttpResponseException(
                ResponseStandardAPI::type(ResponseTypeConstantsHelper::TYPE_ERROR)
                    ->info('Data pengiriman tidak ditemukan')
                    ->detail('Data pengiriman tidak ditemukan')
                    ->response()
            );
        }

        return $this->formatShipping($shipping);
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
                    ->info('Data pengiriman tidak dapat diubah')
                    ->detail('Data pengiriman hanya dapat diubah pada checkout berstatus pending')
                    ->response()
            );
        }

        return $checkout;
    }

    /**
     * @return array{
     *     uuid: string,
     *     uuid_checkout: string,
     *     nama_penerima: string|null,
     *     no_hp_penerima: string|null,
     *     alamat_penerima: string|null,
     *     latitude: string|null,
     *     longitude: string|null
     * }
     */
    private function formatShipping(CheckoutShipping $shipping): array
    {
        return [
            'uuid' => $shipping->uuid,
            'uuid_checkout' => $shipping->uuid_checkout,
            'nama_penerima' => $shipping->nama_penerima,
            'no_hp_penerima' => $shipping->no_hp_penerima,
            'alamat_penerima' => $shipping->alamat_penerima,
            'latitude' => $shipping->latitude !== null ? (string) $shipping->latitude : null,
            'longitude' => $shipping->longitude !== null ? (string) $shipping->longitude : null,
        ];
    }
}
