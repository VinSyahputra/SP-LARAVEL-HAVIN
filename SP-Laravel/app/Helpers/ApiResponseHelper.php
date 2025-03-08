<?php

namespace App\Helpers;

use Illuminate\Http\Response;

class ApiResponseHelper
{
    public static function responseSuccess($data, $message = '', $status = Response::HTTP_OK)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function responseError($data = null, $message = '', $status = Response::HTTP_BAD_REQUEST)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
