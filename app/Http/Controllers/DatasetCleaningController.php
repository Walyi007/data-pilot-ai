<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DatasetCleaningService;
use App\Services\DatasetCleaningRecommendationService;
use App\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Exceptions\PostTooLargeException;

class DatasetCleaningController extends Controller
{
    /**
     * Show the cleaning form for a dataset.
     *
     * @param  Dataset  $dataset
     * @param  DatasetCleaningRecommendationService  $recommendationService
     * @return View
     */
    public function index(Dataset $dataset, DatasetCleaningRecommendationService $recommendationService): View
    {
        // Ensure the authenticated user owns the dataset
        if ($dataset->user_id !== Auth::id()) {
            abort(403);
        }

        // Get cleaning recommendations
        $recommendations = $recommendationService->getRecommendations($dataset);

        // Get dataset profile for column information
        $metrics = $dataset->profile->metrics_json ?? [];

        return view('dataset.clean', [
            'dataset' => $dataset,
            'recommendations' => $recommendations,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Apply cleaning operations to a dataset.
     *
     * @param  Request  $request
     * @param  Dataset  $dataset
     * @param  DatasetCleaningService  $cleaningService
     * @return RedirectResponse
     */
    public function apply(Request $request, Dataset $dataset, DatasetCleaningService $cleaningService): RedirectResponse
    {
        // Ensure the authenticated user owns the dataset
        if ($dataset->user_id !== Auth::id()) {
            abort(403);
        }

        // Validate the request
        $validated = $request->validate([
            'operations' => 'required|array',
            'operations.*.type' => 'sometimes|required|string|in:remove_duplicates,fill_missing,remove_outliers,convert_mixed_type,trim_whitespace',
            'operations.*.column' => 'sometimes|required_if:operations.*.type,fill_missing,remove_outliers|string',
            'operations.*.method' => 'sometimes|required_if:operations.*.type,fill_missing,remove_outliers|string',
            'operations.*.value' => 'sometimes|required_if:operations.*.type,fill_missing,operations.*.method,custom|string',
            'operations.*.target_type' => 'sometimes|required_if:operations.*.type,convert_mixed_type|string|in:string,numeric,date',
            'operations.*.columns' => 'sometimes|required_if:operations.*.type,trim_whitespace|array',
            'operations.*.columns.*' => 'sometimes|string',
        ]);

        // Convert validated operations to the format expected by the service
        $operations = $validated['operations'];

        try {
            // Clean the dataset
            $cleanedDataset = $cleaningService->cleanDataset($dataset, $operations);

            return redirect()
                ->route('dataset.show', $cleanedDataset)
                ->with('success', 'Dataset cleaned successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to clean dataset: ' . $e->getMessage());
        }
    }
}