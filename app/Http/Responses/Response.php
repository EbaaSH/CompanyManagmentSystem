<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class Response
{
    public static function Success($data, $message, $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'data' => $data,
            'message' => $message,
        ], $code);

    }

    public static function Error($data, $message, $code = 500): JsonResponse
    {
        return response()->json([
            'status' => 0,
            'data' => $data,
            'message' => $message,
        ], $code);

    }

    public static function Validation($data, $message, $code = 422): JsonResponse
    {
        return response()->json([
            'status' => 0,
            'data' => $data,
            'message' => $message,
        ], $code);
    }

    public static function Paginate($paginator, $message = 'Success', $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'message' => $message,

            // Main Data
            'data' => $paginator->items(),

            // Pagination Info
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),

            // Optional Range
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),

            // Links Shape
            'links' => collect($paginator->linkCollection())->map(function ($link) {
                return [
                    'url' => $link['url'],
                    'label' => $link['label'],
                    'active' => $link['active'],
                ];
            }),

        ], $code);
    }
}
