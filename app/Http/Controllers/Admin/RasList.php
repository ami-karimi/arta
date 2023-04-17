<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Ras;
use ProtoneMedia\LaravelQueryBuilderInertiaJs\InertiaTable;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RasList extends Controller
{
    public function index(){

        $globalSearch = AllowedFilter::callback('global', function ($query, $value) {
            $query->where(function ($query) use ($value) {
                Collection::wrap($value)->each(function ($value) use ($query) {
                    $query
                        ->orWhere('name', 'LIKE', "%{$value}%")
                        ->orWhere('ipaddress', 'LIKE', "%{$value}%");
                });
            });
        });
        $ras = QueryBuilder::for(Ras::class)->defaultSort('name')
            ->allowedSorts(['name', 'ipaddress','is_enabled','created_at'])
            ->allowedFilters(['name', 'ipaddress', $globalSearch])
            ->paginate(8)
            ->withQueryString();


        return Inertia::render('Admin/RasList',['ras' => $ras])->table(function (InertiaTable $table) {
            $table->column('id', 'ID', searchable: true, sortable: true);
            $table->column('name', 'نام', searchable: true, sortable: true);
            $table->column('ipaddress', 'آدرس آی پی', searchable: true, sortable: true);
            $table->column('is_enabled', 'وضعیت', searchable: true, sortable: true);
            $table->column('created_at', 'تاریخ ایجاد', searchable: true, sortable: true);
            $table->column('actions', 'عملیات', searchable: false, sortable: false);
        });
    }
}
