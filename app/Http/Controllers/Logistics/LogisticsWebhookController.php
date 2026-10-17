<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Services\Logistics\LogisticsService;
use Illuminate\Http\Request;

/** POST /api/webhooks/logistics — signed delivery updates from the logistics marketplace. */
class LogisticsWebhookController extends Controller
{
    public function __invoke(Request $request, LogisticsService $logistics)
    {
        [$status, $message] = $logistics->handleWebhook($request->getContent(), $request->header('X-Logistics-Signature'));
        return response()->json(['message' => $message], $status);
    }
}
