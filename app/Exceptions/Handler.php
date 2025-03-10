<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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

    public function render($request, Throwable $exception)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage()
            ], 500);
        }

        if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            return response()->json(['success' => false,'error' => 'Invalid Details. Please log in.'], 401);
        }
        
        return parent::render($request, $exception);
    }

}
