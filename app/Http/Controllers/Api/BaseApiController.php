<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

abstract class BaseApiController extends Controller
{
    /**
     * Success response format
     */
    protected function successResponse($data = null, $message = 'Success', $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ], $statusCode);
    }

    /**
     * Error response format
     */
    protected function errorResponse($message = 'Error', $errors = [], $statusCode = 400): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
            'timestamp' => now()->toISOString(),
        ], $statusCode);
    }

    /**
     * Validation error response
     */
    protected function validationErrorResponse($errors): JsonResponse
    {
        return $this->errorResponse('Validation failed', $errors, 422);
    }

    /**
     * Not found response
     */
    protected function notFoundResponse($message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, [], 404);
    }

    /**
     * Unauthorized response
     */
    protected function unauthorizedResponse($message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, [], 401);
    }

    /**
     * Forbidden response
     */
    protected function forbiddenResponse($message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, [], 403);
    }

    /**
     * Validate request data
     */
    protected function validateRequest(Request $request, array $rules): array
    {
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
        
        return $validator->validated();
    }

    /**
     * Get pagination meta
     */
    protected function getPaginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'has_more_pages' => $paginator->hasMorePages(),
        ];
    }

    /**
     * Handle exceptions
     */
    protected function handleException(\Exception $e): JsonResponse
    {
        \Log::error('API Error: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return $this->validationErrorResponse($e->errors());
        }

        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return $this->notFoundResponse();
        }

        return $this->errorResponse('Internal server error', [], 500);
    }
}
