<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RegisterService;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;

class RegisterController extends Controller
{
    protected RegisterService $registerService;

    public function __construct(RegisterService $registerService)
    {
        $this->registerService = $registerService;
    }

    public function store(RegisterRequest $request): JsonResponse
{
    try {
        $this->registerService->store($request->all());

        return response()->json([
            'message' => 'Pendaftaran berhasil'
        ], 201);
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan server',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
