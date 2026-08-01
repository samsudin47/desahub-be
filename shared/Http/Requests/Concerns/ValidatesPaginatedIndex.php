<?php

namespace Shared\Http\Requests\Concerns;

use Shared\Constants\PaginationConstantsHelper;

trait ValidatesPaginatedIndex
{
    /**
     * @return array<string, mixed>
     */
    protected function paginatedIndexRules(): array
    {
        return [
            PaginationConstantsHelper::REQUEST_PAGE => ['sometimes', 'integer', 'min:1'],
            PaginationConstantsHelper::REQUEST_PER_PAGE => [
                'sometimes',
                'integer',
                'min:1',
                'max:'.PaginationConstantsHelper::MAX_PER_PAGE,
            ],
            PaginationConstantsHelper::REQUEST_SEARCH => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->integer(
            PaginationConstantsHelper::REQUEST_PER_PAGE,
            PaginationConstantsHelper::DEFAULT_PER_PAGE
        );
    }

    public function search(): ?string
    {
        $search = $this->string(PaginationConstantsHelper::REQUEST_SEARCH)->trim()->toString();

        return $search !== '' ? $search : null;
    }
}
