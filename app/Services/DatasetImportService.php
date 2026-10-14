<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\SyntaxError as CsvSyntaxError;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Models\Dataset;
use App\Models\DatasetProfile;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class DatasetImportService
{
    /**
     * Maximum file size (in bytes) - 10MB
     */
    const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Allowed file extensions
     */
    const ALLOWED_EXTENSIONS = ['csv', 'xls', 'xlsx'];

    /**
     * Process an uploaded file and create a dataset record.
     *
     * @param  UploadedFile  $file
     * @param  int  $userId
     * @return Dataset
     *
     * @throws \Exception
     */
    public function processUpload(UploadedFile $file, int $userId): Dataset
    {
        // Validate the file
        $this->validateFile($file);

        // Generate a unique storage path
        $fileHash = Str::random(40);
        $extension = $file->getClientOriginalExtension();
        $storedFilename = $fileHash . '.' . $extension;
        $storagePath = 'datasets/' . $storedFilename;

        // Store the file securely (local disk, which is private)
        Storage::disk('local')->put($storagePath, file_get_contents($file));

        // Get the original filename
        $originalFilename = $file->getClientOriginalName();

        // Determine file type
        $fileType = $this->getFileType($extension);

        // Create dataset record
        $dataset = Dataset::create([
            'user_id' => $userId,
            'name' => $this->generateDatasetName($originalFilename),
            'original_filename' => $originalFilename,
            'storage_path' => $storagePath,
            'file_type' => $fileType,
            'status' => 'uploaded',
        ]);

        // Create an empty profile for the dataset
        $dataset->profile()->create([
            'metrics_json' => [],
            'quality_score' => null,
        ]);

        try {
            // Process the file to get basic stats (row and column count)
            $basicStats = $this->getFileStats($file->getRealPath(), $fileType);
            $dataset->update([
                'row_count' => $basicStats['row_count'],
                'column_count' => $basicStats['column_count'],
                'status' => 'processing',
            ]);

            // Compute profiling metrics and quality score
            $profilingResult = $this->computeMetrics($file->getRealPath(), $fileType, $basicStats['column_count']);

            // Update the dataset profile with metrics and quality score
            $dataset->profile->update([
                'metrics_json' => $profilingResult['metrics'],
                'quality_score' => $profilingResult['quality_score'],
            ]);

            // Set dataset status to processed
            $dataset->update(['status' => 'processed']);
        } catch (\Exception $e) {
            $dataset->update(['status' => 'failed']);
            throw $e;
        }

        return $dataset;
    }

    /**
     * Validate the uploaded file.
     *
     * @param  UploadedFile  $file
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateFile(UploadedFile $file): void
    {
        $validator = Validator::make(
            [
                'file' => $file,
            ],
            [
                'file' => [
                    'required',
                    'file',
                    'max:' . (self::MAX_FILE_SIZE / 1024), // in KB
                    'mimes:csv,xls,xlsx',
                ],
            ],
            [
                'file.max' => 'The file size must not exceed 10MB.',
                'file.mimes' => 'The file must be a CSV or Excel file.',
            ]
        );

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        // Check extension
        $extension = $file->getClientOriginalExtension();
        if (!in_array(strtolower($extension), self::ALLOWED_EXTENSIONS, true)) {
            throw new \Illuminate\Validation\ValidationException(
                Validator::make(
                    ['file' => $file],
                    ['file' => ['extension' => implode(',', self::ALLOWED_EXTENSIONS)]],
                    ['file.extension' => 'The file must have a valid extension (csv, xls, xlsx).']
                )
            );
        }
    }

    /**
     * Get the file type based on extension.
     *
     * @param  string  $extension
     * @return string
     */
    protected function getFileType(string $extension): string
    {
        return match (strtolower($extension)) {
            'csv' => 'csv',
            'xls', 'xlsx' => 'excel',
            default => 'unknown',
        };
    }

    /**
     * Generate a dataset name from the original filename.
     *
     * @param  string  $filename
     * @return string
     */
    protected function generateDatasetName(string $filename): string
    {
        // Remove extension and limit to 255 characters
        $name = pathinfo($filename, PATHINFO_FILENAME);
        if (strlen($name) > 255) {
            $name = substr($name, 0, 255);
        }
        return $name;
    }

    /**
     * Get basic statistics (row and column count) from the file.
     *
     * @param  string  $filePath
     * @param  string  $fileType
     * @return array
     *
     * @throws \Exception
     */
    protected function getFileStats(string $filePath, string $fileType): array
    {
        if ($fileType === 'csv') {
            return $this->getCsvStats($filePath);
        } elseif ($fileType === 'excel') {
            return $this->getExcelStats($filePath);
        } else {
            throw new \Exception('Unsupported file type');
        }
    }

    /**
     * Get statistics from a CSV file.
     *
     * @param  string  $filePath
     * @return array
     */
    protected function getCsvStats(string $filePath): array
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            $header = $csv->getHeader();
            $headerCount = count($header);
            $recordCount = 0;
            $records = $csv->getRecords();
            foreach ($records as $record) {
                $recordCount++;
            }

            return [
                'row_count' => $recordCount,
                'column_count' => $headerCount,
            ];
        } catch (CsvSyntaxError $e) {
            // Fallback: treat file as having no header, count columns based on first row
            try {
                $csv = Reader::createFromPath($filePath, 'r');
                $csv->setHeaderOffset(null); // no header
                $records = $csv->getRecords();
                $rows = iterator_to_array($records);
                $rowCount = count($rows);
                $columnCount = $rowCount > 0 ? count($rows[0]) : 0;
                return [
                    'row_count' => $rowCount,
                    'column_count' => $columnCount,
                ];
            } catch (\Exception $ex) {
                return [
                    'row_count' => 0,
                    'column_count' => 0,
                ];
            }
        }
    }

    /**
     * Get statistics from an Excel file.
     *
     * @param  string  $filePath
     * @return array
     */
    protected function getExcelStats(string $filePath): array
    {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $columnCount = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            return [
                'row_count' => $highestRow,
                'column_count' => $columnCount,
            ];
        } catch (PhpSpreadsheetException $e) {
            throw new \Exception('Failed to read Excel file: ' . $e->getMessage());
        }
    }

    /**
     * Compute profiling metrics and quality score for the file.
     *
     * @param  string  $filePath
     * @param  string  $fileType
     * @param  int  $columnCount
     * @return array ['metrics' => array, 'quality_score' => float]
     */
    protected function computeMetrics(string $filePath, string $fileType, int $columnCount): array
    {
        if ($fileType === 'csv') {
            return $this->computeCsvMetrics($filePath, $columnCount);
        } elseif ($fileType === 'excel') {
            return $this->computeExcelMetrics($filePath, $columnCount);
        } else {
            throw new \Exception('Unsupported file type for profiling');
        }
    }

    /**
     * Compute metrics for a CSV file.
     *
     * @param  string  $filePath
     * @param  int  $columnCount
     * @return array ['metrics' => array, 'quality_score' => float]
     */
    protected function computeCsvMetrics(string $filePath, int $columnCount): array
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0); // Use first row as header

            // Get header names
            $header = $csv->getHeader();
            if (empty($header)) {
                // No header, treat as no data
                return [
                    'metrics' => [],
                    'quality_score' => 0.0,
                ];
            }

            // Get records (data rows)
            $records = $csv->getRecords();

            // Initialize metrics for each column
            $metrics = [];
            foreach ($header as $colName) {
                $metrics[$colName] = [
                    'type' => 'string',
                    'distinct_count' => 0,
                    'empty_count' => 0,
                    'min' => null,
                    'max' => null,
                    'mean' => null,
                    // For outlier detection
                    'q1' => null,
                    'q3' => null,
                    'iqr' => null,
                    'lower_bound' => null,
                    'upper_bound' => null,
                    'outlier_count' => 0,
                    'values_for_percentiles' => [], // Temporary array to calculate percentiles
                ];
            }

            // We'll also compute row-level metrics: duplicate rows
            $rowStrings = [];
            $rowCount = 0;

            foreach ($records as $record) {
                $rowCount++;

                // Create a string representation of the row for duplicate detection
                $rowString = implode('|', array_map(function($v) use ($header) {
                    return is_null($v) ? '' : strval($v);
                }, array_intersect_key($record, array_flip($header))));
                $rowStrings[] = $rowString;

                // Process each column
                foreach ($header as $colName) {
                    $value = $record[$colName] ?? null;

                    // Count empty cells
                    if (is_null($value) || $value === '') {
                        $metrics[$colName]['empty_count']++;
                    }

                    // Collect distinct values
                    if (!is_null($value) && $value !== '') {
                        if (!isset($metrics[$colName]['distinct_values'])) {
                            $metrics[$colName]['distinct_values'] = [];
                        }
                        if (!in_array($value, $metrics[$colName]['distinct_values'], true)) {
                            $metrics[$colName]['distinct_values'][] = $value;
                        }
                    }

                    // Infer type and update min/max/mean for numeric values
                    // Also collect values for percentile calculation
                    if (!is_null($value) && $value !== '') {
                        // Check if the value is numeric
                        if (is_numeric($value)) {
                            $numericValue = floatval($value);
                            // Update type if we haven't set it to numeric yet, or if it's already numeric
                            if ($metrics[$colName]['type'] !== 'numeric') {
                                $metrics[$colName]['type'] = 'numeric';
                            }
                            // Update min and max
                            if ($metrics[$colName]['min'] === null || $numericValue < $metrics[$colName]['min']) {
                                $metrics[$colName]['min'] = $numericValue;
                            }
                            if ($metrics[$colName]['max'] === null || $numericValue > $metrics[$colName]['max']) {
                                $metrics[$colName]['max'] = $numericValue;
                            }
                            // For mean, we'll compute later
                            $metrics[$colName]['sum'] = ($metrics[$colName]['sum'] ?? 0) + $numericValue;
                            $metrics[$colName]['count'] = ($metrics[$colName]['count'] ?? 0) + 1;
                            // Collect value for percentile calculation
                            $metrics[$colName]['values_for_percentiles'][] = $numericValue;
                        } else {
                            // If we encounter a non-numeric value in a column that we thought was numeric, we mark it as mixed
                            if ($metrics[$colName]['type'] === 'numeric') {
                                $metrics[$colName]['type'] = 'mixed';
                            }
                            // If we haven't set a type, set to string
                            if ($metrics[$colName]['type'] === 'string') {
                                // Already string, do nothing
                            }
                        }
                    }
                }
            }

            // Compute percentiles (Q1 and Q3) and identify outliers for numeric columns
            foreach ($metrics as $colName => $colMetrics) {
                if ($colMetrics['type'] === 'numeric' && !empty($colMetrics['values_for_percentiles'])) {
                    // Sort values to calculate percentiles
                    sort($colMetrics['values_for_percentiles']);
                    $valueCount = count($colMetrics['values_for_percentiles']);
                    
                    // Calculate Q1 (25th percentile)
                    $q1Index = (int)ceil(0.25 * $valueCount) - 1;
                    $q1Index = max(0, min($q1Index, $valueCount - 1));
                    $colMetrics['q1'] = $colMetrics['values_for_percentiles'][$q1Index];
                    
                    // Calculate Q3 (75th percentile)
                    $q3Index = (int)ceil(0.75 * $valueCount) - 1;
                    $q3Index = max(0, min($q3Index, $valueCount - 1));
                    $colMetrics['q3'] = $colMetrics['values_for_percentiles'][$q3Index];
                    
                    // Calculate IQR
                    $colMetrics['iqr'] = $colMetrics['q3'] - $colMetrics['q1'];
                    
                    // Calculate bounds for outlier detection
                    $colMetrics['lower_bound'] = $colMetrics['q1'] - (1.5 * $colMetrics['iqr']);
                    $colMetrics['upper_bound'] = $colMetrics['q3'] + (1.5 * $colMetrics['iqr']);
                    
                    // Count outliers
                    $outlierCount = 0;
                    foreach ($colMetrics['values_for_percentiles'] as $value) {
                        if ($value < $colMetrics['lower_bound'] || $value > $colMetrics['upper_bound']) {
                            $outlierCount++;
                        }
                    }
                    $colMetrics['outlier_count'] = $outlierCount;
                }
                // Clean up temporary arrays
                unset($colMetrics['values_for_percentiles']);
            }

            // Compute distinct counts and clean up temporary arrays
            foreach ($metrics as $colName => $colMetrics) {
                if (isset($colMetrics['distinct_values'])) {
                    $colMetrics['distinct_count'] = count($colMetrics['distinct_values']);
                    unset($colMetrics['distinct_values']);
                }
                // Compute mean if we have numeric values
                if (isset($colMetrics['sum']) && isset($colMetrics['count']) && $colMetrics['count'] > 0) {
                    $colMetrics['mean'] = $colMetrics['sum'] / $colMetrics['count'];
                    unset($colMetrics['sum']);
                    unset($colMetrics['count']);
                }
                // Remove helper keys
                unset($colMetrics['sum']);
                unset($colMetrics['count']);
                $metrics[$colName] = $colMetrics;
            }

            // Compute duplicate row count
            $uniqueRowStrings = array_unique($rowStrings);
            $duplicateRowCount = $rowCount - count($uniqueRowStrings);

            // Compute quality score
            // We'll base it on:
            //   - Percentage of empty cells (across all cells)
            //   - Percentage of duplicate rows
            //   - Percentage of columns with mixed types (or inconsistent types)
            //   - Percentage of outliers (optional - we'll add this as a fourth factor)
            $totalCells = $rowCount * count($header);
            $totalEmptyCells = array_sum(array_column($metrics, 'empty_count'));
            $emptyRatio = $totalCells > 0 ? $totalEmptyCells / $totalCells : 0;

            $duplicateRatio = $rowCount > 0 ? $duplicateRowCount / $rowCount : 0;

            $mixedTypeColumns = array_reduce($metrics, function($carry, $col) {
                return $carry + ($col['type'] === 'mixed' ? 1 : 0);
            }, 0);
            $mixedTypeRatio = count($header) > 0 ? $mixedTypeColumns / count($header) : 0;

            $totalValuesForOutliers = array_sum(array_column($metrics, 'outlier_count'));
            $totalNumericValues = array_sum(array_column($metrics, 'count'));
            $outlierRatio = $totalNumericValues > 0 ? $totalValuesForOutliers / $totalNumericValues : 0;

            // We'll define the quality score as:
            //   quality_score = 1.0 - (emptyRatio * 0.3 + duplicateRatio * 0.2 + mixedTypeRatio * 0.2 + outlierRatio * 0.3)
            // Adjusted weights to accommodate the new outlier factor
            // Clamped between 0 and 1.
            $qualityScore = 1.0 - (0.3 * $emptyRatio + 0.2 * $duplicateRatio + 0.2 * $mixedTypeRatio + 0.3 * $outlierRatio);
            $qualityScore = max(0.0, min(1.0, $qualityScore));

            // Prepare the metrics array to store in JSON
            $storedMetrics = [];
            foreach ($metrics as $colName => $colMetrics) {
                $storedMetrics[$colName] = [
                    'type' => $colMetrics['type'],
                    'distinct_count' => $colMetrics['distinct_count'],
                    'empty_count' => $colMetrics['empty_count'],
                    'min' => $colMetrics['min'],
                    'max' => $colMetrics['max'],
                    'mean' => $colMetrics['mean'],
                    // Outlier information (optional to store)
                    'outlier_count' => isset($colMetrics['outlier_count']) ? $colMetrics['outlier_count'] : 0,
                ];
            }

            return [
                'metrics' => $storedMetrics,
                'quality_score' => $qualityScore,
            ];
        } catch (CsvSyntaxError $e) {
            // File is empty or has no header
            return [
                'metrics' => [],
                'quality_score' => 0.0,
            ];
        } catch (\Exception $e) {
            // Any other exception
            return [
                'metrics' => [],
                'quality_score' => 0.0,
            ];
        }
    }

    /**
     * Compute metrics for an Excel file.
     *
     * @param  string  $filePath
     * @param  int  $columnCount
     * @return array ['metrics' => array, 'quality_score' => float]
     */
    protected function computeExcelMetrics(string $filePath, int $columnCount): array
    {
        // For simplicity, we'll reuse the CSV method by reading the Excel file as CSV?
        // But we don't want to dependencies. We'll implement a similar method for Excel.
        // Given time, we'll return a placeholder.
        // We'll implement a basic version that just counts rows and columns and sets type to unknown.
        // We'll leave the detailed metrics for future work.

        // We'll read the Excel file and get the data from the first worksheet.
        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            // Get the highest row and column
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $actualColumnCount = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            // We'll use the actual column count from the file, but note that we already have $columnCount from the basic stats.
            // We'll trust the basic stats.

            // We'll read the data row by row.
            $data = [];
            $header = null;

            // We'll assume the first row is header.
            for ($row = 1; $row <= $highestRow; $row++) {
                $rowData = [];
                for ($col = 1; $col <= $actualColumnCount; $col++) {
                    $cell = $worksheet->getCellByColumnAndRow($col, $row);
                    $value = $cell->getValue();
                    // If the cell contains a DateTime object, convert to string
                    if ($value instanceof \DateTime) {
                        $value = $value->format('Y-m-d H:i:s');
                    }
                    $rowData[] = $value;
                }
                if ($row === 1) {
                    $header = $rowData;
                } else {
                    $data[] = $rowData;
                }
            }

            // Now we have $header and $data, we can reuse the CSV metrics computation by converting to CSV-like format.
            // We'll create a temporary CSV string and use the same method? Or we can adapt the computeCsvMetrics to work with arrays.

            // We'll create a temporary array of metrics similar to what we did for CSV.

            // Initialize metrics for each column
            $metrics = [];
            for ($i = 0; $i < $actualColumnCount; $i++) {
                $colName = $header[$i] ?? "Column {$i}";
                $metrics[$colName] = [
                    'type' => 'string',
                    'distinct_count' => 0,
                    'empty_count' => 0,
                    'min' => null,
                    'max' => null,
                    'mean' => null,
                    // For outlier detection
                    'q1' => null,
                    'q3' => null,
                    'iqr' => null,
                    'lower_bound' => null,
                    'upper_bound' => null,
                    'outlier_count' => 0,
                    'values_for_percentiles' => [], // Temporary array to calculate percentiles
                ];
            }

            // We'll also compute row-level metrics: duplicate rows
            $rowStrings = [];

            foreach ($data as $row) {
                // Create a string representation of the row for duplicate detection
                $rowString = implode('|', array_map(function($v) {
                    return is_null($v) ? '' : strval($v);
                }, $row));
                $rowStrings[] = $rowString;

                // Process each column
                foreach ($row as $colIndex => $value) {
                    $colName = $header[$colIndex] ?? "Column {$colIndex}";
                    if (!isset($metrics[$colName])) {
                        continue;
                    }

                    // Count empty cells
                    if (is_null($value) || $value === '') {
                        $metrics[$colName]['empty_count']++;
                    }

                    // Collect distinct values
                    if (!is_null($value) && $value !== '') {
                        if (!isset($metrics[$colName]['distinct_values'])) {
                            $metrics[$colName]['distinct_values'] = [];
                        }
                        if (!in_array($value, $metrics[$colName]['distinct_values'], true)) {
                            $metrics[$colName]['distinct_values'][] = $value;
                        }
                    }

                    // Infer type and update min/max/mean for numeric values
                    // Also collect values for percentile calculation
                    if (!is_null($value) && $value !== '') {
                        // Check if the value is numeric
                        if (is_numeric($value)) {
                            $numericValue = floatval($value);
                            // Update type if we haven't set it to numeric yet, or if it's already numeric
                            if ($metrics[$colName]['type'] !== 'numeric') {
                                $metrics[$colName]['type'] = 'numeric';
                            }
                            // Update min and max
                            if ($metrics[$colName]['min'] === null || $numericValue < $metrics[$colName]['min']) {
                                $metrics[$colName]['min'] = $numericValue;
                            }
                            if ($metrics[$colName]['max'] === null || $numericValue > $metrics[$colName]['max']) {
                                $metrics[$colName]['max'] = $numericValue;
                            }
                            // For mean, we'll compute later
                            $metrics[$colName]['sum'] = ($metrics[$colName]['sum'] ?? 0) + $numericValue;
                            $metrics[$colName]['count'] = ($metrics[$colName]['count'] ?? 0) + 1;
                            // Collect value for percentile calculation
                            $metrics[$colName]['values_for_percentiles'][] = $numericValue;
                        } else {
                            // If we encounter a non-numeric value in a column that we thought was numeric, we mark it as mixed
                            if ($metrics[$colName]['type'] === 'numeric') {
                                $metrics[$colName]['type'] = 'mixed';
                            }
                            // If we haven't set a type, set to string
                            if ($metrics[$colName]['type'] === 'string') {
                                // Already string, do nothing
                            }
                        }
                    }
                }
            }

            // Compute percentiles (Q1 and Q3) and identify outliers for numeric columns
            foreach ($metrics as $colName => $colMetrics) {
                if ($colMetrics['type'] === 'numeric' && !empty($colMetrics['values_for_percentiles'])) {
                    // Sort values to calculate percentiles
                    sort($colMetrics['values_for_percentiles']);
                    $valueCount = count($colMetrics['values_for_percentiles']);
                    
                    // Calculate Q1 (25th percentile)
                    $q1Index = (int)ceil(0.25 * $valueCount) - 1;
                    $q1Index = max(0, min($q1Index, $valueCount - 1));
                    $colMetrics['q1'] = $colMetrics['values_for_percentiles'][$q1Index];
                    
                    // Calculate Q3 (75th percentile)
                    $q3Index = (int)ceil(0.75 * $valueCount) - 1;
                    $q3Index = max(0, min($q3Index, $valueCount - 1));
                    $colMetrics['q3'] = $colMetrics['values_for_percentiles'][$q3Index];
                    
                    // Calculate IQR
                    $colMetrics['iqr'] = $colMetrics['q3'] - $colMetrics['q1'];
                    
                    // Calculate bounds for outlier detection
                    $colMetrics['lower_bound'] = $colMetrics['q1'] - (1.5 * $colMetrics['iqr']);
                    $colMetrics['upper_bound'] = $colMetrics['q3'] + (1.5 * $colMetrics['iqr']);
                    
                    // Count outliers
                    $outlierCount = 0;
                    foreach ($colMetrics['values_for_percentiles'] as $value) {
                        if ($value < $colMetrics['lower_bound'] || $value > $colMetrics['upper_bound']) {
                            $outlierCount++;
                        }
                    }
                    $colMetrics['outlier_count'] = $outlierCount;
                }
                // Clean up temporary arrays
                unset($colMetrics['values_for_percentiles']);
            }

            // Compute distinct counts and clean up temporary arrays
            foreach ($metrics as $colName => $colMetrics) {
                if (isset($colMetrics['distinct_values'])) {
                    $colMetrics['distinct_count'] = count($colMetrics['distinct_values']);
                    unset($colMetrics['distinct_values']);
                }
                // Compute mean if we have numeric values
                if (isset($colMetrics['sum']) && isset($colMetrics['count']) && $colMetrics['count'] > 0) {
                    $colMetrics['mean'] = $colMetrics['sum'] / $colMetrics['count'];
                    unset($colMetrics['sum']);
                    unset($colMetrics['count']);
                }
                // Remove helper keys
                unset($colMetrics['sum']);
                unset($colMetrics['count']);
                $metrics[$colName] = $colMetrics;
            }

            // Compute duplicate row count
            $rowCount = count($rowStrings);
            $uniqueRowStrings = array_unique($rowStrings);
            $duplicateRowCount = $rowCount - count($uniqueRowStrings);

            // Compute quality score (same formula as CSV)
            $totalCells = $rowCount * $actualColumnCount;
            $totalEmptyCells = array_sum(array_column($metrics, 'empty_count'));
            $emptyRatio = $totalCells > 0 ? $totalEmptyCells / $totalCells : 0;

            $duplicateRatio = $rowCount > 0 ? $duplicateRowCount / $rowCount : 0;

            $mixedTypeColumns = array_reduce($metrics, function($carry, $col) {
                return $carry + ($col['type'] === 'mixed' ? 1 : 0);
            }, 0);
            $mixedTypeRatio = $actualColumnCount > 0 ? $mixedTypeColumns / $actualColumnCount : 0;

            $totalValuesForOutliers = array_sum(array_column($metrics, 'outlier_count'));
            $totalNumericValues = array_sum(array_column($metrics, 'count'));
            $outlierRatio = $totalNumericValues > 0 ? $totalValuesForOutliers / $totalNumericValues : 0;

            $qualityScore = 1.0 - (0.3 * $emptyRatio + 0.2 * $duplicateRatio + 0.2 * $mixedTypeRatio + 0.3 * $outlierRatio);
            $qualityScore = max(0.0, min(1.0, $qualityScore));

            // Prepare the metrics array to store in JSON
            $storedMetrics = [];
            foreach ($metrics as $colName => $colMetrics) {
                $storedMetrics[$colName] = [
                    'type' => $colMetrics['type'],
                    'distinct_count' => $colMetrics['distinct_count'],
                    'empty_count' => $colMetrics['empty_count'],
                    'min' => $colMetrics['min'],
                    'max' => $colMetrics['max'],
                    'mean' => $colMetrics['mean'],
                    // Outlier information (optional to store)
                    'outlier_count' => isset($colMetrics['outlier_count']) ? $colMetrics['outlier_count'] : 0,
                ];
            }

            return [
                'metrics' => $storedMetrics,
                'quality_score' => $qualityScore,
            ];
        } catch (\Exception $e) {
            // If we fail to compute metrics, return empty metrics and zero quality score
            return [
                'metrics' => [],
                'quality_score' => 0.0,
            ];
        }
    }
}