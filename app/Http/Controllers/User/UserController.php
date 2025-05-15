<?php declare(strict_types = 1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\{StoreUserRequest, UpdateUserRequest};
use App\Http\Resources\User\{UserCollection, UserResource};
use App\Models\User;
use DevactionLabs\FilterablePackage\Filter;
use Illuminate\Http\{JsonResponse};
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(): UserCollection
    {
        $users = User::query()
            ->filtrable([
                Filter::like('name', 'name'),
                Filter::like('email', 'email'),
            ])
            ->customPaginate();

        return new UserCollection($users);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified user.
     */
    public function show(string $user): UserResource
    {
        $userModel = User::findOrFail($user);

        return new UserResource($userModel);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, string $user): UserResource
    {
        $userModel = User::findOrFail($user);
        $userModel->update($request->validated());

        return new UserResource($userModel);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(string $user): JsonResponse
    {
        $userModel = User::findOrFail($user);
        $userModel->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
