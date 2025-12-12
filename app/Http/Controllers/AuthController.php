<?php

namespace App\Http\Controllers;

use App\Http\CustomResponse\CustomResponse;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Resources\User\UserResource;
use App\Services\UserService;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * @param CustomResponse $response
     * @param UserService $userService
     */
    public function __construct(public CustomResponse $response, public UserService $userService)
    {
    }

    /**
     * @param LoginRequest $request
     * @return JsonResponse
     * @throws GuzzleException
     */
    public function login(LoginRequest $request)
    {
        $validatedData = $request->validated();

        if (!Auth::attempt($validatedData)) {
            return $this->response->invalidLoginCredential();
        }

        $this->revokeAccessAndRefreshTokens();

        $auth = $this->resolveAuthenticationData($request, [
            'grant_type'    => 'password',
            'client_id'     => config('passport.password_client_id'),
            'client_secret' => config('passport.password_client_secret'),
            'username'      => $validatedData['email'],
            'password'      => $validatedData['password'],
            'scope'         => '*'
        ]);

        if(!is_array($auth)){
            return $auth;
        }

        $user = $this->userService->show(Auth::id());
        $userData = new UserResource($user);

        return $this->response->success(object: $userData, auth: $auth);
    }

    /**
     * @param RefreshTokenRequest $request
     * @return JsonResponse
     * @throws GuzzleException
     */
    public function refreshToken(RefreshTokenRequest $request)
    {
        $auth = $this->resolveAuthenticationData($request, [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $request->get('refresh_token'),
            'client_id'     => config('passport.password_client_id'),
            'client_secret' => config('passport.password_client_secret'),
            'scope'         => ''
        ]);

        return $this->response->success('New tokens generated!', auth: $auth);
    }

    /**
     * @return JsonResponse
     */
    public function logout()
    {
        $this->revokeAccessAndRefreshTokens();

        return $this->response->success('Successfully logged out!');
    }

    /**
     * @return void
     */
    protected function revokeAccessAndRefreshTokens()
    {
        $tokenIds = Auth::user()->tokens()->where('revoked', false)->pluck('id')->toArray();

        DB::table('oauth_access_tokens')
            ->whereIn('id', $tokenIds)
            ->update([
                'revoked' => true
            ]);

        DB::table('oauth_refresh_tokens')
            ->whereIn('access_token_id', $tokenIds)
            ->update([
                'revoked' => true
            ]);
    }

    /**
     * @param $request
     * @param $arguments
     * @return mixed
     * @throws GuzzleException
     */
    public function resolveAuthenticationData($request, $arguments)
    {
        try{
            $client = new Client();

            $headers = $request->headers->all();
            $headers['accept'] = 'application/json';
            $headers['content-type'] = "application/x-www-form-urlencoded";

            $tokenResponseContent = $client->post(config('app.url') . '/oauth/token', [
                'headers' => $headers,
                'form_params' => $arguments,
            ])->getBody()->getContents();

            $responseData = json_decode($tokenResponseContent, true);

            $responseData['expires_in'] = Carbon::parse(now()->addSeconds($responseData['expires_in']))->format('m/d/Y H:i:s');
        }catch (Exception $exception){
            return $this->response->exceptionWithCustomMessage($exception, "Failed to generate a token!");
        }

        return $responseData;
    }
}
