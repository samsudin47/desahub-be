<?php

namespace Shared\Utilities;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Shared\Constants\PaginationConstantsHelper;

class PaginationHelper
{
    /**
     * @return array{
     *     currentPage: int,
     *     perPage: int,
     *     total: int,
     *     lastPage: int,
     *     from: int|null,
     *     to: int|null
     * }
     */
    public static function fromPaginator(LengthAwarePaginator $paginator): array
    {
        return [
            PaginationConstantsHelper::CURRENT_PAGE => $paginator->currentPage(),
            PaginationConstantsHelper::PER_PAGE => $paginator->perPage(),
            PaginationConstantsHelper::TOTAL => $paginator->total(),
            PaginationConstantsHelper::LAST_PAGE => $paginator->lastPage(),
            PaginationConstantsHelper::FROM => $paginator->firstItem(),
            PaginationConstantsHelper::TO => $paginator->lastItem(),
        ];
    }

    /**
     * @param  array<string, mixed>|LengthAwarePaginator  $pagination
     * @return array{
     *     currentPage: int,
     *     perPage: int,
     *     total: int,
     *     lastPage: int,
     *     from: int|null,
     *     to: int|null
     * }
     */
    public static function normalize(array|LengthAwarePaginator $pagination): array
    {
        if ($pagination instanceof LengthAwarePaginator) {
            return self::fromPaginator($pagination);
        }

        return [
            PaginationConstantsHelper::CURRENT_PAGE => (int) ($pagination[PaginationConstantsHelper::CURRENT_PAGE] ?? 1),
            PaginationConstantsHelper::PER_PAGE => (int) ($pagination[PaginationConstantsHelper::PER_PAGE] ?? PaginationConstantsHelper::DEFAULT_PER_PAGE),
            PaginationConstantsHelper::TOTAL => (int) ($pagination[PaginationConstantsHelper::TOTAL] ?? 0),
            PaginationConstantsHelper::LAST_PAGE => (int) ($pagination[PaginationConstantsHelper::LAST_PAGE] ?? 1),
            PaginationConstantsHelper::FROM => isset($pagination[PaginationConstantsHelper::FROM])
                ? (int) $pagination[PaginationConstantsHelper::FROM]
                : null,
            PaginationConstantsHelper::TO => isset($pagination[PaginationConstantsHelper::TO])
                ? (int) $pagination[PaginationConstantsHelper::TO]
                : null,
        ];
    }

    public static function resolvePerPage(?int $perPage = null): int
    {
        $perPage ??= PaginationConstantsHelper::DEFAULT_PER_PAGE;

        if ($perPage < 1) {
            return PaginationConstantsHelper::DEFAULT_PER_PAGE;
        }

        return min($perPage, PaginationConstantsHelper::MAX_PER_PAGE);
    }
}
