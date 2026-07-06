<?php

namespace Whilesmart\Forms\ResponseFormatters;

use Illuminate\Http\JsonResponse;
use Whilesmart\Forms\Interfaces\ResponseFormatterInterface;

class DefaultResponseFormatter implements ResponseFormatterInterface
{
    public function success(
        mixed $data = null,
        string $message = 'Operation successful',
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public function failure(
        string $message = 'Operation failed',
        int $statusCode = 400,
        array $errors = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }
}
