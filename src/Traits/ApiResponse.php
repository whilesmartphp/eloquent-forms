<?php

namespace Whilesmart\Forms\Traits;

use Illuminate\Http\JsonResponse;
use Whilesmart\Forms\Interfaces\ResponseFormatterInterface;

trait ApiResponse
{
    protected function success(
        mixed $data = null,
        string $message = 'Operation successful',
        int $statusCode = 200
    ): JsonResponse {
        return app(ResponseFormatterInterface::class)->success($data, $message, $statusCode);
    }

    protected function failure(
        string $message = 'Operation failed',
        int $statusCode = 400,
        array $errors = []
    ): JsonResponse {
        return app(ResponseFormatterInterface::class)->failure($message, $statusCode, $errors);
    }
}
