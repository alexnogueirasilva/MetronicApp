<?php declare(strict_types = 1);

namespace App\Http\Controllers\ACL;

use App\Http\Controllers\Controller;
use App\Http\Resources\ACL\RoleCollection;
use App\Models\Auth\Role;
use DevactionLabs\FilterablePackage\Filter;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): RoleCollection
    {
        $roles = Role::query()
            ->filtrable([
                Filter::like('name', 'name'),
            ])
            ->customPaginate();

        return new RoleCollection($roles);
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
