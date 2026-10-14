<?php

namespace App\Services;

use App\Models\Dataset;
use App\Models\DatasetProfile;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Writer;
use League\Csv\Statement;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Str;

class DatasetCleaningService
{
    /**
     * Apply cleaning operations to a dataset and create a new cleaned dataset.
     *
     * @param  Dataset  $originalDataset
     * @param  array  $operations List of operations to apply
     * @return Dataset New cleaned dataset
     *
     * @throws \Exception
     */
    public function cleanDataset(Dataset $originalDataset, array $operations): Dataset
    {
        // Get the original file path
        $storagePath = $originalDataset->storage_path;
        $fileType = $originalDataset->file_type;
        
        // Read the original data
        $data = $this->readFileToArray($storagePath, $fileType);
        
        if (empty($data)) {
            throw new \Exception('Could not read data from file');
        }
        
        // Extract header and rows
        $header = $data[0] ?? [];
        $rows = array_slice($data, 1);
        
        // Apply each operation
        foreach ($operations as $operation) {
            switch ($operation['type']) {
                case 'remove_duplicates':
                    [$header, $rows] = $this->removeDuplicateRows($header, $rows);
                    break;
                    
                case 'fill_missing':
                    $column = $operation['column'];
                    $method = $operation['method'] ?? 'mean';
                    $customValue = $operation['value'] ?? null;
                    [$header, $rows] = $this->fillMissingValues($header, $rows, $column, $method, $customValue);
                    break;
                    
                case 'remove_outliers':
                    $column = $operation['column'];
                    $method = $operation['method'] ?? 'winsorize'; // winsorize, remove, or cap
                    [$header, $rows] = $this->handleOutliers($header, $rows, $column, $method);
                    break;
                    
                case 'convert_mixed_type':
                    $column = $operation['column'];
                    $targetType = $operation['target_type'] ?? 'string'; // string, numeric, date
                    [$header, $rows] = $this->convertColumnType($header, $rows, $column, $targetType);
                    break;
                    
                case 'trim_whitespace':
                    $columns = $operation['columns'] ?? []; // empty means all columns
                    [$header, $rows] = $this->trimWhitespace($header, $rows, $columns);
                    break;
                    
                default:
                    // Unknown operation type - skip
                    continue 2;
            }
        }
        
        // Reconstruct full data with header
        $cleanedData = array_merge([$header], $rows);
        
        // Generate a filename for the cleaned dataset
        $originalName = $originalDataset->original_filename;
        $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $cleanedFilename = $nameWithoutExt . '_cleaned.' . $extension;
        
        // Store the cleaned file
        $fileHash = Str::random(40);
        $storedFilename = $fileHash . '.' . $extension;
        $cleanedStoragePath = 'datasets_cleaned/' . $storedFilename;
        
        $this->saveArrayToFile($cleanedData, $cleanedStoragePath, $fileType);
        
        // Process the cleaned dataset through the import service to get profiling
        $importService = new \App\Services\DatasetImportService();
        $processedDataset = $importService->processUpload(
            new \Illuminate\Http\UploadedFile(
                Storage::path($cleanedStoragePath),
                $cleanedFilename,
                null,
                null,
                true // test mode
            ),
            $originalDataset->user_id
        );
        
        // Update the name to be more descriptive
        $processedDataset->update([
            'name' => $originalDataset->name . ' (Cleaned)',
        ]);
        
        return $processedDataset;
    }
    
    /**
     * Read file into array format (header + rows)
     *
     * @param  string  $storagePath
     * @param  string  $fileType
     * @return array
     */
    private function readFileToArray(string $storagePath, string $fileType): array
    {
        if (!Storage::exists($storagePath)) {
            return [];
        }
        
        $contents = Storage::get($storagePath);
        
        if ($fileType === 'csv') {
            $csv = Reader::createFromString($contents);
            $csv->setHeaderOffset(0); // First row is header
            
            $records = $csv->getRecords();
            $data = [];
            
            // Add header
            $header = $csv->getHeader();
            if ($header) {
                $data[] = $header;
            }
            
            // Add rows
            foreach ($records as $record) {
                $row = [];
                foreach ($header as $col) {
                    $row[] = $record[$col] ?? null;
                }
                $data[] = $row;
            }
            
            return $data;
        } elseif ($fileType === 'excel') {
            $spreadsheet = IOFactory::load($storagePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            
            $data = [];
            
            // Read header (first row)
            $header = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $cell = $worksheet->getCellByColumnAndRow($col, 1);
                $header[] = $cell->getValue();
            }
            $data[] = $header;
            
            // Read data rows
            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = [];
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $cell = $worksheet->getCellByColumnAndRow($col, $row);
                    $value = $cell->getValue();
                    // Convert DateTime to string
                    if ($value instanceof \DateTime) {
                        $value = $value->format('Y-m-d H:i:s');
                    }
                    $rowData[] = $value;
                }
                $data[] = $rowData;
            }
            
            return $data;
        }
        
        return [];
    }
    
    /**
     * Save array data to file
     *
     * @param  array  $data
     * @param  string  $storagePath
     * @param  string  $fileType
     * @return void
     */
    private function saveArrayToFile(array $data, string $storagePath, string $fileType): void
    {
        if ($fileType === 'csv') {
            $csv = Writer::createFromString('');
            $csv->insertAll($data);
            Storage::put($storagePath, $csv->toString());
        } elseif ($fileType === 'excel') {
            $spreadsheet = new Spreadsheet();
            $worksheet = $spreadsheet->getActiveSheet();
            
            foreach ($data as $rowIndex => $row) {
                foreach ($row as $colIndex => $value) {
                    $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
                    $worksheet->setCellValue($column . ($rowIndex + 1), $value);
                }
            }
            
            $writer = new Xlsx($spreadsheet);
            $writer->save(Storage::path($storagePath));
        }
    }
    
    /**
     * Remove duplicate rows
     *
     * @param  array  $header
     * @param  array  $rows
     * @return array [$header, $rows]
     */
    private function removeDuplicateRows(array $header, array $rows): array
    {
        // Convert rows to strings for deduplication
        $rowStrings = [];
        $uniqueRows = [];
        
        foreach ($rows as $row) {
            $rowString = implode('|', array_map(function($v) {
                return is_null($v) ? '' : strval($v);
            }, $row));
            
            if (!in_array($rowString, $rowStrings)) {
                $rowStrings[] = $rowString;
                $uniqueRows[] = $row;
            }
        }
        
        return [$header, $uniqueRows];
    }
    
    /**
     * Fill missing values in a column
     *
     * @param  array  $header
     * @param  array  $rows
     * @param  string  $columnName
     * @param  string  $method (mean, median, mode, custom)
     * @param  mixed  $customValue
     * @return array [$header, $rows]
     */
    private function fillMissingValues(array $header, array $rows, string $columnName, string $method, $customValue = null): array
    {
        $colIndex = array_search($columnName, $header);
        if ($colIndex === false) {
            return [$header, $rows]; // Column not found
        }
        
        // Extract column values
        $columnValues = array_column($rows, $colIndex);
        
        // Determine fill value based on method
        $fillValue = null;
        
        switch ($method) {
            case 'mean':
                $numericValues = array_filter($columnValues, function($v) {
                    return !is_null($v) && $v !== '' && is_numeric($v);
                });
                if (!empty($numericValues)) {
                    $fillValue = array_sum($numericValues) / count($numericValues);
                }
                break;
                
            case 'median':
                $numericValues = array_filter($columnValues, function($v) {
                    return !is_null($v) && $v !== '' && is_numeric($v);
                });
                if (!empty($numericValues)) {
                    sort($numericValues);
                    $count = count($numericValues);
                    $middle = floor($count / 2);
                    if ($count % 2 === 0) {
                        $fillValue = ($numericValues[$middle - 1] + $numericValues[$middle]) / 2;
                    } else {
                        $fillValue = $numericValues[$middle];
                    }
                }
                break;
                
            case 'mode':
                $values = array_filter($columnValues, function($v) {
                    return !is_null($v) && $v !== '';
                });
                if (!empty($values)) {
                    $valueCounts = array_count_values($values);
                    arsort($valueCounts);
                    $fillValue = key($valueCounts);
                }
                break;
                
            case 'custom':
                $fillValue = $customValue;
                break;
                
            default:
                $fillValue = null;
        }
        
        // Apply fill value to missing cells
        if ($fillValue !== null) {
            foreach ($rows as &$row) {
                if (is_null($row[$colIndex]) || $row[$colIndex] === '') {
                    $row[$colIndex] = $fillValue;
                }
            }
            unset($row); // Remove reference
        }
        
        return [$header, $rows];
    }
    
    /**
     * Handle outliers in a column
     *
     * @param  array  $header
     * @param  array  $rows
     * @param  string  $columnName
     * @param  string  $method (winsorize, remove, cap)
     * @return array [$header, $rows]
     */
    private function handleOutliers(array $header, array $rows, string $columnName, string $method): array
    {
        $colIndex = array_search($columnName, $header);
        if ($colIndex === false) {
            return [$header, $rows]; // Column not found
        }
        
        // Extract numeric column values
        $numericValues = [];
        $nonNumericRows = []; // Keep track of non-numeric rows to preserve them
        
        foreach ($rows as $rowIndex => $row) {
            $value = $row[$colIndex];
            if (!is_null($value) && $value !== '' && is_numeric($value)) {
                $numericValues[] = (float)$value;
            } else {
                // Non-numeric or empty - preserve as is
                $nonNumericRows[$rowIndex] = $row;
            }
        }
        
        if (empty($numericValues)) {
            return [$header, $rows]; // No numeric values to process
        }
        
        // Calculate quartiles and IQR
        sort($numericValues);
        $count = count($numericValues);
        
        $q1Index = (int)ceil(0.25 * $count) - 1;
        $q1Index = max(0, min($q1Index, $count - 1));
        $q1 = $numericValues[$q1Index];
        
        $q3Index = (int)ceil(0.75 * $count) - 1;
        $q3Index = max(0, min($q3Index, $count - 1));
        $q3 = $numericValues[$q3Index];
        
        $iqr = $q3 - $q1;
        $lowerBound = $q1 - (1.5 * $iqr);
        $upperBound = $q3 + (1.5 * $iqr);
        
        // Process rows based on method
        $processedRows = [];
        
        foreach ($rows as $rowIndex => $row) {
            $value = $row[$colIndex];
            
            // Skip non-numeric values
            if (is_null($value) || $value === '' || !is_numeric($value)) {
                $processedRows[] = $row;
                continue;
            }
            
            $numericValue = (float)$value;
            
            switch ($method) {
                case 'remove':
                    // Remove rows with outliers
                    if ($numericValue >= $lowerBound && $numericValue <= $upperBound) {
                        $processedRows[] = $row;
                    }
                    break;
                    
                case 'winsorize':
                    // Cap values at bounds
                    $newValue = max($lowerBound, min($upperBound, $numericValue));
                    $row[$colIndex] = $newValue;
                    $processedRows[] = $row;
                    break;
                    
                case 'cap':
                    // Same as winsorize for now
                    $newValue = max($lowerBound, min($upperBound, $numericValue));
                    $row[$colIndex] = $newValue;
                    $processedRows[] = $row;
                    break;
                    
                default:
                    // Keep original
                    $processedRows[] = $row;
            }
        }
        
        return [$header, $processedRows];
    }
    
    /**
     * Convert column to target type
     *
     * @param  array  $header
     * @param  array  $rows
     * @param  string  $columnName
     * @param  string  $targetType (string, numeric, date)
     * @return array [$header, $rows]
     */
    private function convertColumnType(array $header, array $rows, string $columnName, string $targetType): array
    {
        $colIndex = array_search($columnName, $header);
        if ($colIndex === false) {
            return [$header, $rows]; // Column not found
        }
        
        foreach ($rows as &$row) {
            $value = $row[$colIndex];
            
            if (is_null($value) || $value === '') {
                // Leave empty values as is
                continue;
            }
            
            switch ($targetType) {
                case 'numeric':
                    // Try to convert to numeric
                    if (is_numeric($value)) {
                        $row[$colIndex] = floatval($value);
                    } else {
                        // Try to extract numeric part
                        if (preg_match('/[\d,]+\.?[\d]*/', $value, $matches)) {
                            $numStr = str_replace(',', '', $matches[0]);
                            if (is_numeric($numStr)) {
                                $row[$colIndex] = floatval($numStr);
                            }
                        }
                        // If conversion fails, leave as is (or could set to null)
                    }
                    break;
                    
                case 'string':
                    // Convert to string
                    if ($value instanceof \DateTime) {
                        $row[$colIndex] = $value->format('Y-m-d H:i:s');
                    } else {
                        $row[$colIndex] = strval($value);
                    }
                    break;
                    
                case 'date':
                    // Try to convert to date
                    if ($value instanceof \DateTime) {
                        // Already a DateTime
                    } elseif (is_string($value) && strtotime($value) !== false) {
                        $row[$colIndex] = new \DateTime($value);
                    } elseif (is_numeric($value)) {
                        // Treat as timestamp
                        $row[$colIndex] = new \DateTime('@' . $value);
                    }
                    // If conversion fails, leave as is
                    break;
            }
        }
        unset($row);
        
        return [$header, $rows];
    }
    
    /**
     * Trim whitespace from string values
     *
     * @param  array  $header
     * @param  array  $rows
     * @param  array  $columnNames (empty array means all columns)
     * @return array [$header, $rows]
     */
    private function trimWhitespace(array $header, array $rows, array $columnNames): array
    {
        $columnsToTrim = $columnNames;
        if (empty($columnNames)) {
            // Trim all columns
            $columnsToTrim = $header;
        }
        
        foreach ($rows as &$row) {
            foreach ($columnsToTrim as $columnName) {
                $colIndex = array_search($columnName, $header);
                if ($colIndex !== false && !is_null($row[$colIndex]) && is_string($row[$colIndex])) {
                    $row[$colIndex] = trim($row[$colIndex]);
                }
            }
        }
        unset($row);
        
        return [$header, $rows];
    }
}
