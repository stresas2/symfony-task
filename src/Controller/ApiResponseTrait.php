<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;


trait ApiResponseTrait
{
    private function errorResponse(
        string $message,
        array $errors = [],
        int $status = Response::HTTP_BAD_REQUEST
    ): JsonResponse {
        $body = [
            'message' => $message,
            'errors' => $errors,
        ];

        return new JsonResponse($body, $status);
    }
}
