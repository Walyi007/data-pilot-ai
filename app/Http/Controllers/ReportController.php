<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Services\ReportGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    /**
     * Generate and download a PDF profile report.
     *
     * @param  Request  $request
     * @param  Dataset  $dataset
     * @param  ReportGenerationService  $reportService
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function profilePdf(Request $request, Dataset $dataset, ReportGenerationService $reportService)
    {
        // Ensure the authenticated user owns the dataset
        if ($dataset->user_id !== $request->user()->id) {
            abort(403);
        }

        // Generate the report
        $filePath = $reportService->generateProfilePdf($dataset);

        // Return the file for download
        return Storage::download($filePath, 'dataset_profile_' . $dataset->id . '.pdf');
    }

    /**
     * Generate and download an Excel profile report.
     *
     * @param  Request  $request
     * @param  Dataset  $dataset
     * @param  ReportGenerationService  $reportService
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function profileExcel(Request $request, Dataset $dataset, ReportGenerationService $reportService)
    {
        // Ensure the authenticated user owns the dataset
        if ($dataset->user_id !== $request->user()->id) {
            abort(403);
        }

        // Generate the report
        $filePath = $reportService->generateProfileExcel($dataset);

        // Return the file for download
        return Storage::download($filePath, 'dataset_profile_' . $dataset->id . '.xlsx');
    }

    /**
     * Generate and download a PDF analytics report.
     *
     * @param  Request  $request
     * @param  Dataset  $dataset
     * @param  ReportGenerationService  $reportService
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function analyticsPdf(Request $request, Dataset $dataset, ReportGenerationService $reportService)
    {
        // Ensure the authenticated user owns the dataset
        if ($dataset->user_id !== $request->user()->id) {
            abort(403);
        }

        // Generate the report
        $filePath = $reportService->generateAnalyticsPdf($dataset);

        // Return the file for download
        return Storage::download($filePath, 'dataset_analytics_' . $dataset->id . '.pdf');
    }

    /**
     * Generate and download an Excel analytics report.
     *
     * @param  Request  $request
     * @param  Dataset  $dataset
     * @param  ReportGenerationService  $reportService
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function analyticsExcel(Request $request, Dataset $dataset, ReportGenerationService $reportService)
    {
        // Ensure the authenticated user owns the dataset
        if ($dataset->user_id !== $request->user()->id) {
            abort(403);
        }

        // Generate the report
        $filePath = $reportService->generateAnalyticsExcel($dataset);

        // Return the file for download
        return Storage::download($filePath, 'dataset_analytics_' . $dataset->id . '.xlsx');
    }

    /**
     * Generate and download a PDF cleaning report.
     *
     * @param  Request  $request
     * @param  Dataset  $originalDataset
     * @param  ReportGenerationService  $reportService
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function cleaningPdf(Request $request, Dataset $originalDataset, ReportGenerationService $reportService)
    {
        // Ensure the authenticated user owns the dataset
        if ($originalDataset->user_id !== $request->user()->id) {
            abort(403);
        }

        // Get the most recent cleaned dataset for this user and original dataset
        // For simplicity, we'll look for a cleaned dataset with the same user_id and 
        // original_filename ending with '_cleaned.' plus the original extension
        $cleanedDataset = $request->user()->datasets()
            ->where('original_filename', 'like', '%_cleaned.' . $originalDataset->file_type)
            ->where('name', 'like', $originalDataset->name . ' (Cleaned)%')
            ->latest()
            ->first();

        if (!$cleanedDataset) {
            return back()->with('error', 'No cleaned dataset found for this original dataset.');
        }

        // Generate the report
        $filePath = $reportService->generateCleaningReportPdf($originalDataset, $cleanedDataset);

        // Return the file for download
        return Storage::download($filePath, 'cleaning_report_' . $originalDataset->id . '.pdf');
    }
}