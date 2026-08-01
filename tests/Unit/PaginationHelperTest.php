<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Shared\Constants\PaginationConstantsHelper;
use Shared\Utilities\PaginationHelper;

it('maps length aware paginator into standardized pagination payload', function () {
    $paginator = new LengthAwarePaginator(
        items: [['uuid' => '1'], ['uuid' => '2']],
        total: 20,
        perPage: 2,
        currentPage: 2,
    );

    expect(PaginationHelper::fromPaginator($paginator))->toBe([
        PaginationConstantsHelper::CURRENT_PAGE => 2,
        PaginationConstantsHelper::PER_PAGE => 2,
        PaginationConstantsHelper::TOTAL => 20,
        PaginationConstantsHelper::LAST_PAGE => 10,
        PaginationConstantsHelper::FROM => 3,
        PaginationConstantsHelper::TO => 4,
    ]);
});

it('normalizes pagination arrays into the standardized shape', function () {
    expect(PaginationHelper::normalize([
        PaginationConstantsHelper::CURRENT_PAGE => '3',
        PaginationConstantsHelper::PER_PAGE => '10',
        PaginationConstantsHelper::TOTAL => '25',
        PaginationConstantsHelper::LAST_PAGE => '3',
        PaginationConstantsHelper::FROM => '21',
        PaginationConstantsHelper::TO => '25',
    ]))->toBe([
        PaginationConstantsHelper::CURRENT_PAGE => 3,
        PaginationConstantsHelper::PER_PAGE => 10,
        PaginationConstantsHelper::TOTAL => 25,
        PaginationConstantsHelper::LAST_PAGE => 3,
        PaginationConstantsHelper::FROM => 21,
        PaginationConstantsHelper::TO => 25,
    ]);
});

it('resolves per page within allowed bounds', function () {
    expect(PaginationHelper::resolvePerPage(null))->toBe(PaginationConstantsHelper::DEFAULT_PER_PAGE)
        ->and(PaginationHelper::resolvePerPage(0))->toBe(PaginationConstantsHelper::DEFAULT_PER_PAGE)
        ->and(PaginationHelper::resolvePerPage(50))->toBe(50)
        ->and(PaginationHelper::resolvePerPage(PaginationConstantsHelper::MAX_PER_PAGE + 1))
        ->toBe(PaginationConstantsHelper::MAX_PER_PAGE);
});
