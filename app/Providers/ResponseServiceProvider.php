<?php

namespace App\Providers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class ResponseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->descriptiveResponseMethods();
    }

    protected function descriptiveResponseMethods(): void
    {
        $instance = $this;

        Response::macro('ok', function (array|Collection $data = [], string $message = 'Success', $status = 200) {
            $response = [
                'success' => true,
                'message' => $message,
            ];

            if ($data instanceof Collection) {
                $data = $data->toArray();
            }

            return Response::json(count($data) ? array_merge($response, $data) : $response, $status);
        });

        Response::macro('notifyOk', function (array $data = [], string $message = 'Success', string $notifyType = 'positive', int $status = 200) use ($instance) {
            return $instance->createNotifyResponse(
                success: true,
                message: $message,
                status: $status,
                notifyType: $notifyType,
                data: $data
            );
        });

        Response::macro('forbidden', function ($message = 'Access denied', $errors = []) use ($instance) {
            return $instance->handleErrorResponse($message, $errors, 403);
        });

        Response::macro('notifyForbidden', function (string $message = 'Access denied', array $errors = [], string $notifyType = 'negative') use ($instance) {
            return $instance->createNotifyResponse(
                success: false,
                message: $message,
                status: 403,
                notifyType: $notifyType,
                errors: $errors
            );
        });

        Response::macro('error', function ($message = 'Error.', $errors = [], $status = 422) use ($instance) {
            return $instance->handleErrorResponse($message, $errors, $status);
        });

        Response::macro('notifyError', function ($message = 'Error.', $errors = [], $notifyType = 'negative', $status = 422) use ($instance) {
            return $instance->createNotifyResponse(
                success: false,
                message: $message,
                status: $status,
                notifyType: $notifyType,
                errors: $errors
            );
        });

        Response::macro('notFound', function ($message = 'Not Found.', $errors = []) use ($instance) {
            return $instance->handleErrorResponse($message, $errors, 404);
        });

        Response::macro('notifyNotFound', function ($message = 'Not Found.', $errors = [], $notifyType = 'negative') use ($instance) {
            return $instance->createNotifyResponse(
                success: false,
                message: $message,
                status: 404,
                notifyType: $notifyType,
                errors: $errors
            );
        });
    }

    public function handleErrorResponse($message, $errors, $status): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (count($errors)) {
            $response['errors'] = $errors;
        }

        return Response::json($response, $status);
    }

    public function createNotifyResponse(
        bool   $success,
        string $message,
        int    $status,
        string $notifyType = 'positive',
        ?array $data = null,
        ?array $errors = null
    ): JsonResponse
    {
        $response = [
            'success' => $success,
            'message' => $message,
            'notify' => [
                'enabled' => true,
                'type' => $notifyType,
                'message' => $message,
            ],
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        if (!empty($data)) {
            $response = array_merge($response, $data);
        }

        return Response::json($response, $status);
    }
}
