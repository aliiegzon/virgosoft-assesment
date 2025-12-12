<?php

namespace App\Http\Controllers;

use App\Http\CustomResponse\CustomResponse;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\User\UserCollection;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class UserController extends BaseController
{
    /**
     * @param UserService $service
     * @param CustomResponse $response
     */
    public function __construct(UserService $service, public CustomResponse $response)
    {
        parent::__construct($service, UserResource::class, UserCollection::class, 'user');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->indexHelp();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        return $this->storeHelp($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return $this->showHelp($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        return $this->updateHelp($request, $user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        return $this->destroyHelp($user);
    }

    /**
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function resendInvitation(User $user)
    {
        $this->authorize('update', $user);

        $this->service->resendInvitation($user);

        return $this->customResponse->success('Resent invitation successfully!');
    }
}
