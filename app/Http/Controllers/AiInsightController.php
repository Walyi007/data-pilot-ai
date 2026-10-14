<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Services\AiInsightService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class AiInsightController extends Controller
{
    /**
     * Show AI insight for a dataset.
     *
     * @param  Request  $request
     * @param  Dataset  $dataset
     * @param  AiInsightService  $aiService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function show(Request $request, Dataset $dataset, AiInsightService $aiService)
    {
        // Ensure the authenticated user owns the dataset
        if ($dataset->user_id !== Auth::id()) {
            abort(403);
        }

        // Get dataset profile and metrics
        $profile = $dataset->profile;
        if (!$profile) {
            return Redirect::back()->with('error', 'Dataset profile not found.');
        }

        $metrics = $profile->metrics_json ?? [];
        $qualityScore = $profile->quality_score ?? 0.0;

        // Get AI insight
        $insight = $aiService->getInsight($metrics, $qualityScore, $dataset->name);

        // Flash the insight to be displayed in the session
        return Redirect::route('dataset.show', $dataset)
            ->with('ai_insight', $insight);
    }
}