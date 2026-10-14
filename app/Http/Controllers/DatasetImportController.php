<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\DatasetImportService;
use App\Services\DatasetCleaningRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\Dataset;
use Illuminate\Http\Exceptions\PostTooLargeException;

class DatasetImportController extends Controller
{
    /**
     * Show the import form.
     *
     * @return View
     */
    public function create(): View
    {
        return view('dataset.import');
    }

    /**
     * Handle the file upload.
     *
     * @param  Request  $request
     * @param  DatasetImportService  $service
     * @return RedirectResponse
     */
    public function store(Request $request, DatasetImportService $service): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'file' => 'required|file|max:10240|mimes:csv,xls,xlsx',
            ]);
        } catch (PostTooLargeException $e) {
            return back()
                ->withInput()
                ->with('error', 'The file size must not exceed 10MB.');
        }

        try {
            $dataset = $service->processUpload($request->file('file'), Auth::id());

            return redirect()
                ->route('dataset.show', $dataset)
                ->with('success', 'File uploaded and processed successfully.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to process file: ' . $e->getMessage());
        }
    }

    /**
     * Show the dataset.
     *
     * @param  Dataset  $dataset
     * @param  DatasetCleaningRecommendationService  $cleaningService
     * @return View
     */
    public function show(Dataset $dataset, DatasetCleaningRecommendationService $cleaningService): View
    {
        // Ensure the authenticated user owns the dataset
        if ($dataset->user_id !== Auth::id()) {
            abort(403);
        }

        // Get cleaning recommendations
        $recommendations = $cleaningService->getRecommendations($dataset);

        // For now, just show a simple view with dataset information
        return view('dataset.show', [
            'dataset' => $dataset,
            'recommendations' => $recommendations,
        ]);
    }
}