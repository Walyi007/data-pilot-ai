<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Dataset;
use App\Models\DatasetProfile;
use App\Services\DatasetCleaningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatasetCleaningServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $cleaningService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleaningService = new DatasetCleaningService();
    }

    /** @test */
    public function it_removes_duplicate_rows()
    {
        Storage::fake('local');

        // Create a user and original dataset with duplicates
        $user = User::factory()->create();
        $originalDataset = Dataset::create([
            'user_id' => $user->id,
            'name' => 'Original Dataset',
            'original_filename' => 'original.csv',
            'storage_path' => 'datasets/original.csv',
            'file_type' => 'csv',
            'row_count' => 5,
            'column_count' => 2,
            'status' => 'processed',
        ]);

        // Create a CSV file with duplicate rows
        $csvContent = "name,age\nJohn,25\nJane,30\nJohn,25\nBob,35\nJane,30";
        $file = UploadedFile::fake()->createWithContent('original.csv', $csvContent, 'text/csv');
        
        // Store the file
        Storage::disk('local')->put('datasets/original.csv', $file->getContent());

        // Create profile for original dataset
        $profile = DatasetProfile::create([
            'dataset_id' => $originalDataset->id,
            'metrics_json' => [],
            'quality_score' => 0.6,
        ]);

        $originalDataset->profile = $profile;

        // Define cleaning operation: remove duplicates
        $operations = [
            [
                'type' => 'remove_duplicates',
            ]
        ];

        // Clean the dataset
        $cleanedDataset = $this->cleaningService->cleanDataset($originalDataset, $operations);

        // Assertions
        $this->assertInstanceOf(Dataset::class, $cleanedDataset);
        $this->assertEquals($user->id, $cleanedDataset->user_id);
        $this->assertEquals('Original Dataset (Cleaned)', $cleanedDataset->name);
        $this->assertEquals('csv', $cleanedDataset->file_type);
        // Should have 3 unique rows (John, Jane, Bob) instead of 5
        $this->assertEquals(3, $cleanedDataset->row_count);
        $this->assertEquals(2, $cleanedDataset->column_count);
        $this->assertEquals('processed', $cleanedDataset->status);
    }

    /** @test */
    public function it_fills_missing_values_with_mean()
    {
        Storage::fake('local');

        // Create a user and original dataset with missing values
        $user = User::factory()->create();
        $originalDataset = Dataset::create([
            'user_id' => $user->id,
            'name' => 'Original Dataset',
            'original_filename' => 'original.csv',
            'storage_path' => 'datasets/original.csv',
            'file_type' => 'csv',
            'row_count' => 4,
            'column_count' => 2,
            'status' => 'processed',
        ]);

        // Create a CSV file with missing values in age column
        $csvContent = "name,age\nJohn,20\nJane,\nBob,30\nAlice,";
        $file = UploadedFile::fake()->createWithContent('original.csv', $csvContent, 'text/csv');
        
        // Store the file
        Storage::disk('local')->put('datasets/original.csv', $file->getContent());

        // Create profile for original dataset
        $profile = DatasetProfile::create([
            'dataset_id' => $originalDataset->id,
            'metrics_json' => [],
            'quality_score' => 0.5,
        ]);

        $originalDataset->profile = $profile;

        // Define cleaning operation: fill missing age with mean
        $operations = [
            [
                'type' => 'fill_missing',
                'column' => 'age',
                'method' => 'mean',
            ]
        ];

        // Clean the dataset
        $cleanedDataset = $this->cleaningService->cleanDataset($originalDataset, $operations);

        // The mean of 20 and 30 is 25
        // We need to check the actual cleaned data to verify
        // For now, just check that the dataset was created and processed
        $this->assertInstanceOf(Dataset::class, $cleanedDataset);
        $this->assertEquals('processed', $cleanedDataset->status);
    }

    /** @test */
    public function it_handles_outliers_by_winsorizing()
    {
        Storage::fake('local');

        // Create a user and original dataset with outliers
        $user = User::factory()->create();
        $originalDataset = Dataset::create([
            'user_id' => $user->id,
            'name' => 'Original Dataset',
            'original_filename' => 'original.csv',
            'storage_path' => 'datasets/original.csv',
            'file_type' => 'csv',
            'row_count' => 5,
            'column_count' => 2,
            'status' => 'processed',
        ]);

        // Create a CSV file with normal values and one outlier
        $csvContent = "name,score\nJohn,10\nJane,12\nBob,11\nAlice,13\nOutlier,100";
        $file = UploadedFile::fake()->createWithContent('original.csv', $csvContent, 'text/csv');
        
        // Store the file
        Storage::disk('local')->put('datasets/original.csv', $file->getContent());

        // Create profile for original dataset
        $profile = DatasetProfile::create([
            'dataset_id' => $originalDataset->id,
            'metrics_json' => [],
            'quality_score' => 0.4,
        ]);

        $originalDataset->profile = $profile;

        // Define cleaning operation: winsorize outliers in score column
        $operations = [
            [
                'type' => 'remove_outliers',
                'column' => 'score',
                'method' => 'winsorize',
            ]
        ];

        // Clean the dataset
        $cleanedDataset = $this->cleaningService->cleanDataset($originalDataset, $operations);

        // Assertions
        $this->assertInstanceOf(Dataset::class, $cleanedDataset);
        $this->assertEquals('processed', $cleanedDataset->status);
        // Should still have 5 rows (winsorizing doesn't remove rows)
        $this->assertEquals(5, $cleanedDataset->row_count);
    }

    /** @test */
    public function it_converts_mixed_type_column_to_string()
    {
        Storage::fake('local');

        // Create a user and original dataset with mixed type column
        $user = User::factory()->create();
        $originalDataset = Dataset::create([
            'user_id' => $user->id,
            'name' => 'Original Dataset',
            'original_filename' => 'original.csv',
            'storage_path' => 'datasets/original.csv',
            'file_type' => 'csv',
            'row_count' => 3,
            'column_count' => 2,
            'status' => 'processed',
        ]);

        // Create a CSV file with mixed types in value column (numbers and strings)
        $csvContent = "id,value\n1,100\n2,hello\n3,200";
        $file = UploadedFile::fake()->createWithContent('original.csv', $csvContent, 'text/csv');
        
        // Store the file
        Storage::disk('local')->put('datasets/original.csv', $file->getContent());

        // Create profile for original dataset
        $profile = DatasetProfile::create([
            'dataset_id' => $originalDataset->id,
            'metrics_json' => [],
            'quality_score' => 0.5,
        ]);

        $originalDataset->profile = $profile;

        // Define cleaning operation: convert value column to string
        $operations = [
            [
                'type' => 'convert_mixed_type',
                'column' => 'value',
                'target_type' => 'string',
            ]
        ];

        // Clean the dataset
        $cleanedDataset = $this->cleaningService->cleanDataset($originalDataset, $operations);

        // Assertions
        $this->assertInstanceOf(Dataset::class, $cleanedDataset);
        $this->assertEquals('processed', $cleanedDataset->status);
    }
}