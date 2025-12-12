<?php

namespace App\Http\Controllers;

use App\Http\CustomResponse\CustomResponse;
use App\Http\Requests\Password\ResetPasswordRequest;
use App\Http\Requests\Password\SendResetPasswordEmailRequest;
use App\Http\Requests\Password\SetPasswordRequest;
use App\Http\Requests\Password\ValidateTokenRequest;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class PasswordController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * @param CustomResponse $response
     */
    public function __construct(public CustomResponse $response)
    {
    }

    /**
     * @param SendResetPasswordEmailRequest $request
     * @return JsonResponse
     */
    public function sendEmail(SendResetPasswordEmailRequest $request)
    {
        $validatedData = $request->validated();
        $email = $validatedData['email'];

        $token = Str::random(60);
        DB::table('password_reset_tokens')->where('email', $email)->delete();
        DB::table('password_reset_tokens')->insert([
            'email'      => $email,
            'token'      => bcrypt($token),
            'created_at' => now()
        ]);

        $user = User::query()->where('email', '=', $email)->first();

        $user->notify(new ResetPasswordNotification($token, $email));

        return $this->response->success('We have e-mailed your password reset link!');
    }

    /**
     * @param ResetPasswordRequest $request
     * @return JsonResponse|RedirectResponse
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $data = $request->validated();

        $tokenData = DB::table('password_reset_tokens')->where('email', $data['email'])->first();

        if (!$tokenData || !password_verify($data['token'], $tokenData->token)) {
            return redirect()->back()->withErrors(['email' => 'Invalid token!']);
        }

        $user = User::query()->whereEmail($data['email'])->first();
        $user->password = bcrypt($data['password']);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $tokenData->email)->delete();

        return $this->response->success('Password has been reset');
    }

    /**
     * @param SetPasswordRequest $request
     * @return JsonResponse|RedirectResponse
     */
    public function setPassword(SetPasswordRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        $tokenData = DB::table('password_set_tokens')->where('email', $data['email'])->first();

        if (!$tokenData) {
            return redirect()->back()->withErrors(['error' => 'Token is invalid!']);
        }

        User::query()
            ->where('email', $tokenData->email)
            ->update([
                'password'          => bcrypt($data['password']),
                'email_verified_at' => now(),
            ]);

        DB::table('password_set_tokens')->where('email', $tokenData->email)->delete();

        return $this->response->success('Password set successfully.');
    }

    /**
     * @param ValidateTokenRequest $request
     * @return JsonResponse
     */
    public function validateToken(ValidateTokenRequest $request): JsonResponse
    {
        $user = User::query()->whereEmail($request->get('email'))->first();
        $broker = Password::broker($request->get('password_broker'));

        if (!$broker->tokenExists($user, $request->get('token'))) {
            throw new UnprocessableEntityHttpException("The token is invalid or expired, generate a new token!", code: 401);
        }

        return $this->response->success('Token validated!');
    }
}
