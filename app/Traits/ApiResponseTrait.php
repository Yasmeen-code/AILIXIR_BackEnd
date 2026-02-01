<?php

namespace App\Traits;

use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponseTrait
{
    /**
     * Success Response
     */
    protected function successResponse(string $message, $data = null, int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Error Response
     */
    protected function errorResponse(string $message, int $code = 400, $errors = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $errors ? ['errors' => $errors] : null
        ], $code);
    }

    /**
     * Paginated Response 
     */
    protected function paginatedResponse(string $message, LengthAwarePaginator $paginator)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'results' => $paginator->items(),
                'pagination' => [
                    'currentPage' => $paginator->currentPage(),
                    'totalPages' => $paginator->lastPage(),
                    'totalResults' => $paginator->total(),
                    'perPage' => $paginator->perPage(),
                    'hasNextPage' => $paginator->hasMorePages(),
                    'hasPrevPage' => !$paginator->onFirstPage()
                ]
            ]
        ]);
    }

    /**
     * List Response 
     */
    protected function listResponse(string $message, $items)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'results' => $items,
                'pagination' => null
            ]
        ]);
    }
}
