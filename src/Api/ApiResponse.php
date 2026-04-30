<?php

namespace App\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Standardized API Response Wrapper
 * All API endpoints wrap responses in this format for consistency
 */
class ApiResponse
{
    private string $status = 'success';
    private ?array $data = null;
    private ?array $errors = null;
    private array $metadata = [];
    private int $statusCode = 200;

    /**
     * Success response
     */
    public static function success(?array $data = null, array $metadata = []): JsonResponse
    {
        $response = new self();
        $response->status = 'success';
        $response->data = $data;
        $response->metadata = $metadata;
        $response->statusCode = 200;

        return response()->json($response->toArray(), 200);
    }

    /**
     * Created response (201)
     */
    public static function created(?array $data = null): JsonResponse
    {
        $response = new self();
        $response->status = 'success';
        $response->data = $data;
        $response->statusCode = 201;

        return response()->json($response->toArray(), 201);
    }

    /**
     * Error response
     */
    public static function error(
        string $message,
        array $details = [],
        int $statusCode = 400
    ): JsonResponse {
        $response = new self();
        $response->status = 'error';
        $response->errors = [
            'message' => $message,
            'details' => $details
        ];
        $response->statusCode = $statusCode;

        return response()->json($response->toArray(), $statusCode);
    }

    /**
     * Validation error response
     */
    public static function validationError(array $validationErrors): JsonResponse
    {
        $response = new self();
        $response->status = 'validation_error';
        $response->errors = $validationErrors;
        $response->statusCode = 422;

        return response()->json($response->toArray(), 422);
    }

    /**
     * Paginated response
     */
    public static function paginated(
        array $data,
        int $total,
        int $perPage,
        int $currentPage
    ): JsonResponse {
        $response = new self();
        $response->status = 'success';
        $response->data = $data;
        $response->metadata = [
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => ceil($total / $perPage),
                'from' => ($currentPage - 1) * $perPage + 1,
                'to' => min($currentPage * $perPage, $total)
            ]
        ];

        return response()->json($response->toArray(), 200);
    }

    /**
     * Unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, [], 401);
    }

    /**
     * Forbidden response
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error($message, [], 403);
    }

    /**
     * Not found response
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, [], 404);
    }

    /**
     * Server error response
     */
    public static function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return self::error($message, [], 500);
    }

    /**
     * Convert to array for JSON serialization
     */
    public function toArray(): array
    {
        $response = [
            'status' => $this->status,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($this->data !== null) {
            $response['data'] = $this->data;
        }

        if ($this->errors !== null) {
            $response['errors'] = $this->errors;
        }

        if (!empty($this->metadata)) {
            $response['meta'] = $this->metadata;
        }

        return $response;
    }
}
