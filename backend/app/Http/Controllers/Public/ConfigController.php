<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    // GET /api/config  (public: the cart needs the VAT rate before checkout)
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'vat_rate' => (float) config('eventix.vat_rate', 19),
            'hold_minutes' => intdiv(OrderService::HOLD_SECONDS, 60),
        ]);
    }
}