<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class OrderException extends Exception
{
    protected $statusCode;

    public function __construct(string $message, int $statusCode = 400)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->message,
        ], $this->statusCode);
    }
}
