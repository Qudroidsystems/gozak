<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // Handle API exceptions with JSON response
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions
     */
    protected function handleApiException($request, Throwable $e)
    {
        $statusCode = $this->getStatusCode($e);
        $response = [
            'success' => false,
            'message' => $this->getErrorMessage($e),
        ];

        // Add error details in debug mode
        if (config('app.debug')) {
            $response['error'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ];
        }

        // Add validation errors if available
        if ($e instanceof ValidationException) {
            $response['errors'] = $e->errors();
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Get appropriate status code for exception
     */
    protected function getStatusCode(Throwable $e): int
    {
        if ($e instanceof AuthenticationException) {
            return 401;
        }
        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return 403;
        }
        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return 404;
        }
        if ($e instanceof ValidationException) {
            return 422;
        }
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
            return 405;
        }
        
        return 500;
    }

    /**
     * Get user-friendly error message
     */
    protected function getErrorMessage(Throwable $e): string
    {
        if ($e instanceof ModelNotFoundException) {
            return 'Resource not found.';
        }
        
        if ($e instanceof NotFoundHttpException) {
            return 'Endpoint not found.';
        }
        
        if ($e instanceof AuthenticationException) {
            return 'Unauthenticated.';
        }
        
        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return 'Unauthorized.';
        }

        // Default messages for production
        if (!config('app.debug')) {
            if ($e instanceof \Exception) {
                return 'An error occurred. Please try again.';
            }
        }

        return $e->getMessage();
    }
}