<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class DatasetAnalyticsController extends Controller
{
    /**
     * Show analytics dashboard for a dataset.
     *
     * @param  Dataset  $dataset
     * @return View
     */
    public function show(Dataset $dataset): View
    {
        // Ensure the authenticated user owns the dataset
        if ($dataset->user_id !== Auth::id()) {
            abort(403);
        }

        // Get dataset profile
        $profile = $dataset->profile;
        $metrics = $profile->metrics_json ?? [];
        $qualityScore = $profile->quality_score ?? 0.0;

        // Prepare data for analytics
        $columnCount = count($metrics);
        $rowCount = $dataset->row_count;

        // Missing values per column
        $missingLabels = [];
        $missingData = [];
        foreach ($metrics as $columnName => $columnMetrics) {
            $missingLabels[] = $columnName;
            $missingData[] = $columnMetrics['empty_count'] ?? 0;
        }

        // Data types distribution
        $typeCounts = [];
        foreach ($metrics as $columnMetrics) {
            $type = $columnMetrics['type'] ?? 'unknown';
            if (!isset($typeCounts[$type])) {
                $typeCounts[$type] = 0;
            }
            $typeCounts[$type]++;
        }
        $typeLabels = array_keys($typeCounts);
        $typeData = array_values($typeCounts);

        // Numeric columns stats
        $numericColumns = [];
        foreach ($metrics as $columnName => $columnMetrics) {
            if ($columnMetrics['type'] === 'numeric') {
                $numericColumns[$columnName] = [
                    'min' => $columnMetrics['min'],
                    'max' => $columnMetrics['max'],
                    'mean' => $columnMetrics['mean'],
                    'median' => null, // We don't store median, but we could compute if needed
                    'outlier_count' => $columnMetrics['outlier_count'] ?? 0,
                ];
            }
        }

        return view('dataset.analytics', [
            'dataset' => $dataset,
            'qualityScore' => $qualityScore,
            'columnCount' => $columnCount,
            'rowCount' => $rowCount,
            'missingLabels' => $missingLabels,
            'missingData' => $missingData,
            'typeLabels' => $typeLabels,
            'typeData' => $typeData,
            'numericColumns' => $numericColumns,
        ]);
    }
}