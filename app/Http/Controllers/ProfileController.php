<?php

namespace App\Http\Controllers;

use App\Http\CustomResponse\CustomResponse;
use App\Http\Resources\Profile\ProfileCollection;
use App\Http\Resources\Profile\ProfileResource;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends BaseController
{
    /**
     * @param  ProfileService  $service
     * @param  CustomResponse  $response
     */
    public function __construct(ProfileService $service, public CustomResponse $response)
    {
        parent::__construct($service, ProfileResource::class, ProfileCollection::class, 'profile', authorizeResource: false);
    }

    /**
     * @param  Request  $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $profileData = $this->service->showProfile($request->user());

        return $this->customResponse->success(object: new ProfileResource($profileData));
    }
}
