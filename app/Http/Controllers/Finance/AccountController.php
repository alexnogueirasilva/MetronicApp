<?php
declare(strict_types = 1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\AccountCollection;
use App\Models\Finance\Account;
use DevactionLabs\FilterablePackage\Filter;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AccountCollection
    {
        $accounts = Account::query()
            ->filterable([
                Filter::like('name', 'name'),
                Filter::like('description', 'description'),
                Filter::exact('type', 'type'),
            ])
            ->customPaginate();

        return new AccountCollection($accounts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): void
    {
        //
    }
}
