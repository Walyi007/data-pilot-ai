<?php

namespace App\Services;

use App\Models\Dataset;
use App\Models\DatasetProfile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportGenerationService
{
    /**
     * Generate a PDF report of the dataset profile.
     *
     * @param  Dataset  $dataset
     * @return string  Path to the generated PDF file
     */
    public function generateProfilePdf(Dataset $dataset): string
    {
        $profile = $dataset->profile;
        $metrics = $profile->metrics_json ?? [];
        $qualityScore = $profile->quality_score ?? 0.0;

        $data = [
            'dataset' => $dataset,
            'profile' => $profile,
            'metrics' => $metrics,
            'qualityScore' => $qualityScore,
        ];

        $pdf = Pdf::loadView('reports.profile.pdf', $data);

        // Generate a unique filename
        $fileName = 'dataset_profile_' . $dataset->id . '_' . now()->timestamp . '.pdf';
        $filePath = 'reports/' . $fileName;

        // Store the PDF
        Storage::put($filePath, $pdf->output());

        return $filePath;
    }

    /**
     * Generate an Excel report of the dataset profile.
     *
     * @param  Dataset  $dataset
     * @return string  Path to the generated Excel file
     */
    public function generateProfileExcel(Dataset $dataset): string
    {
        $profile = $dataset->profile;
        $metrics = $dataset->profile->metrics_json ?? [];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Dataset Profile');

        // Set headers
        $sheet->setCellValue('A1', 'Property');
        $sheet->setCellValue('B1', 'Value');

        $row = 2;
        $sheet->setCellValue('A'.$row, 'Dataset Name');
        $sheet->setCellValue('B'.$row, $dataset->name);
        $row++;

        $sheet->setCellValue('A'.$row, 'Original Filename');
        $sheet->setCellValue('B'.$row, $dataset->original_filename);
        $row++;

        $sheet->setCellValue('A'.$row, 'File Type');
        $sheet->setCellValue('B'.$row, ucfirst($dataset->file_type));
        $row++;

        $sheet->setCellValue('A'.$row, 'Row Count');
        $sheet->setCellValue('B'.$row, $dataset->row_count);
        $row++;

        $sheet->setCellValue('A'.$row, 'Column Count');
        $sheet->setCellValue('B'.$row, $dataset->column_count);
        $row++;

        $sheet->setCellValue('A'.$row, 'Quality Score');
        $sheet->setCellValue('B'.$row, number_format($qualityScore, 2));
        $row++;

        $row++; // Empty row

        $sheet->setCellValue('A'.$row, 'Column Name');
        $sheet->setCellValue('B'.$row, 'Data Type');
        $sheet->setCellValue('C'.$row, 'Distinct Count');
        $sheet->setCellValue('D'.$row, 'Empty Count');
        $sheet->setCellValue('E'.$row, 'Min');
        $sheet->setCellValue('F'.$row, 'Max');
        $sheet->setCellValue('G'.$row, 'Mean');
        $sheet->setCellValue('H'.$row, 'Outlier Count');
        $row++;

        foreach ($metrics as $columnName => $columnMetrics) {
            $sheet->setCellValue('A'.$row, $columnName);
            $sheet->setCellValue('B'.$row, $columnMetrics['type'] ?? 'unknown');
            $sheet->setCellValue('C'.$row, $columnMetrics['distinct_count'] ?? 0);
            $sheet->setCellValue('D'.$row, $columnMetrics['empty_count'] ?? 0);
            $sheet->setCellValue('E'.$row, $columnMetrics['min'] ?? '');
            $sheet->setCellValue('F'.$row, $columnMetrics['max'] ?? '');
            $sheet->setCellValue('G'.$row, number_format($columnMetrics['mean'] ?? 0, 2));
            $sheet->setCellValue('H'.$row, $columnMetrics['outlier_count'] ?? 0);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Generate a unique filename
        $fileName = 'dataset_profile_' . $dataset->id . '_' . now()->timestamp . '.xlsx';
        $filePath = 'reports/' . $fileName;

        // Save the spreadsheet
        $writer = new Xlsx($spreadsheet);
        $writer->save(Storage::path($filePath));

        return $filePath;
    }

    /**
     * Generate a PDF report of the dataset analytics.
     *
     * @param  Dataset  $dataset
     * @return string  Path to the generated PDF file
     */
    public function generateAnalyticsPdf(Dataset $dataset): string
    {
        // This would generate a more comprehensive analytics report
        // For now, we'll reuse the profile PDF or create a simple one
        return $this->generateProfilePdf($dataset);
    }

    /**
     * Generate an Excel report of the dataset analytics.
     *
     * @param  Dataset  $dataset
     * @return string  Path to the generated Excel file
     */
    public function generateAnalyticsExcel(Dataset $dataset): string
    {
        // This would generate a more comprehensive analytics report
        // For now, we'll reuse the profile Excel or create a simple one
        return $this->generateProfileExcel($dataset);
    }

    /**
     * Generate a PDF report of the cleaning results.
     *
     * @param  Dataset  $originalDataset
     * @param  Dataset  $cleanedDataset
     * @return string  Path to the generated PDF file
     */
    public function generateCleaningReportPdf(Dataset $originalDataset, Dataset $cleanedDataset): string
    {
        $originalProfile = $originalDataset->profile;
        $cleanedProfile = $cleanedDataset->profile;

        $data = [
            'originalDataset' => $originalDataset,
            'cleanedDataset' => $cleanedDataset,
            'originalProfile' => $originalProfile,
            'cleanedProfile' => $cleanedProfile,
        ];

        $pdf = Pdf::loadView('reports.cleaning.pdf', $data);

        // Generate a unique filename
        $fileName = 'cleaning_report_' . $originalDataset->id . '_to_' . $cleanedDataset->id . '_' . now()->timestamp . '.pdf';
        $filePath = 'reports/' . $fileName;

        // Store the PDF
        Storage::put($filePath, $pdf->output());

        return $filePath;
    }
}