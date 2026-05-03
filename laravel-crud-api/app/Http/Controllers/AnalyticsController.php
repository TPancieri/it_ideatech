<?php

namespace App\Http\Controllers;

use App\Services\Analytics\AnalyticsQueryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request, AnalyticsQueryService $analytics): View
    {
        $options = [
            'grain' => $request->string('grain')->toString() ?: 'day',
            'from' => $request->string('from')->toString() ?: null,
            'to' => $request->string('to')->toString() ?: null,
        ];

        $snapshot = $analytics->snapshot((int) $request->user()->id, $options);

        return view('analytics.index', [
            'snapshot' => $snapshot,
        ]);
    }
}

