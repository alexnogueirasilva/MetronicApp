<?php declare(strict_types = 1);

namespace App\Http\Controllers\ACL;

use App\Http\Controllers\Controller;
use App\Http\Resources\ACL\PermissionCollection;
use App\Models\Auth\Permission;
use DevactionLabs\FilterablePackage\Filter;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): PermissionCollection
    {
        $permissions = Permission::query()
            ->filtrable([
                Filter::like('name', 'name'),
            ])
            ->customPaginate();

        return new PermissionCollection($permissions);
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
