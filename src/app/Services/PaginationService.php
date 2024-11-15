<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Exception;
use PHPUnit\Framework\Constraint\IsEmpty;

class PaginationService
{
    public function wa_paginate($items, $perPage = 5, $page = null, $options = [])

    {
        /**
         * The attributes that are mass assignable.
         *
         * @var array
         */

        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);

        // $items = $items instanceof Collection ? $items : Collection::make($items);

        $currentItems = array_slice($items, $perPage * ($page - 1), $perPage);

        // $paginator = new LengthAwarePaginator($currentItems, count($items), $perPage, $currentPage);

        return new LengthAwarePaginator($currentItems, count($items), $perPage, $page, $options);
    }

    public function wa_paginateCollection($collection, $perPage, $page = null)
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $offset = ($page - 1) * $perPage;

        $paginatedData = new LengthAwarePaginator(
            $collection->slice($offset, $perPage)->values()->all(),
            count($collection),
            $perPage,
            $page
        );

        return $paginatedData;
    }

    public function paginateCollection($collection, $perPage)
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $pagedData = $collection->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $paginatedData = new LengthAwarePaginator($pagedData, count($collection), $perPage);
        return $paginatedData;
    }

    public function paginate_resp($collection, $perPage, $page = null)
    {
       if (!$collection instanceof Collection) { $collection = collect($collection); }
        $page = $page ?: (LengthAwarePaginator::resolveCurrentPage() ?: 1);

        $offset = ($page - 1) * $perPage;
        $paginatedData = $collection->slice($offset, $perPage)->values();

        return new LengthAwarePaginator(
            $paginatedData,
            $collection->count(), // count
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(), // Lien pour les pages
                'pageName' => 'page',
            ]
        );
    }

    public function setPaginate($collection, $perPage = 10)
    {
        $currentPage = request('page', 1);
        // $paginatedData = $this->paginateCollection(collect($collection), $perPage)->setPageName('page')->appends(request()->except('page'));
        $perPage = request('per_page', $perPage);
        $paginatedData = $collection->paginate($perPage);
        return $paginatedData;
    }
}
