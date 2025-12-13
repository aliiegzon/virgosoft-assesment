<?php

namespace App\Exceptions;

use App\Http\CustomResponse\CustomResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * @param $request
     * @param Throwable $e
     * @return Response|JsonResponse|RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $e): Response|JsonResponse|RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $customResponse = new CustomResponse();

        return match (true) {
            $e instanceof UnprocessableEntityHttpException => $customResponse->exception($e),
            $e instanceof ModelNotFoundException => $customResponse->modelNotFoundException($e),
            $e instanceof ValidationException => parent::render($request, $e),
            $e instanceof AuthenticationException => $customResponse->unAuthenticated(),
            $e instanceof AuthorizationException, $e instanceof PermissionDoesNotExist => $customResponse->unAuthorized(),
            default => $this->renderExceptionResponse($request, $e)
        };
    }
}
