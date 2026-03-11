<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QuoteService;
use Illuminate\Http\JsonResponse;

class QuoteController extends Controller
{
    public function __construct(
        protected QuoteService $quoteService
    ) {}

    /**
     * Get the motivational quote for today.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $quote = $this->quoteService->getDailyQuote();

        return response()->json([
            'status' => true,
            'data' => [
                'quote' => $quote,
            ],
        ]);
    }
}
