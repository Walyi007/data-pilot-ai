<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Dataset;
use App\Models\DatasetProfile;
use App\Services\DatasetCleaningRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatasetCleaningRecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DatasetCleaningRecommendationService();
    }

    /** @test */
    public function it_generates_recommendations_for_high_empty_columns()
    {
        // Create a user and dataset
        $user = User::factory()->create();
        $dataset = Dataset::create([
            'user_id' => $user->id,
            'name' => 'Test Dataset',
            'original_filename' => 'test.csv',
            'storage_path' => 'datasets/test.csv',
            'file_type' => 'csv',
            'row_count' => 100,
            'column_count' => 5,
            'status' => 'processed',
        ]);

        // Create a profile with metrics indicating high empty ratio in one column
        $profile = DatasetProfile::create([
            'dataset_id' => $dataset->id,
            'metrics_json' => [
                'col1' => [
                    'type' => 'string',
                    'distinct_count' => 50,
                    'empty_count' => 60, // 60% empty
                    'min' => null,
                    'max' => null,
                    'mean' => null,
                ],
                'col2' => [
                    'type' => 'numeric',
                    'distinct_count' => 100,
                    'empty_count' => 5,
                    'min' => 10,
                    'max' => 100,
                    'mean' => 55,
                ],
            ],
            'quality_score' => 0.6,
        ]);

        $dataset->profile = $profile;

        $recommendations = $this->service->getRecommendations($dataset);

        // Check that we have recommendations about high empty values
        $this->assertTrue(
            count(array_filter($recommendations, function($rec) {
                return strpos($rec, 'col1') !== false && strpos($rec, 'vide') !== false;
            })) > 0
        );

        // Check overall quality message
        $this->assertTrue(
            count(array_filter($recommendations, function($rec) {
                return strpos($rec, 'qualité globale') !== false;
            })) > 0
        );
    }

    /** @test */
    public function it_generates_recommendations_for_mixed_type_columns()
    {
        $user = User::factory()->create();
        $dataset = Dataset::create([
            'user_id' => $user->id,
            'name' => 'Test Dataset 2',
            'original_filename' => 'test2.csv',
            'storage_path' => 'datasets/test2.csv',
            'file_type' => 'csv',
            'row_count' => 50,
            'column_count' => 3,
            'status' => 'processed',
        ]);

        $profile = DatasetProfile::create([
            'dataset_id' => $dataset->id,
            'metrics_json' => [
                'mixed_col' => [
                    'type' => 'mixed',
                    'distinct_count' => 30,
                    'empty_count' => 0,
                    'min' => null,
                    'max' => null,
                    'mean' => null,
                ],
                'numeric_col' => [
                    'type' => 'numeric',
                    'distinct_count' => 20,
                    'empty_count' => 2,
                    'min' => 5,
                    'max' => 50,
                    'mean' => 25,
                ],
            ],
            'quality_score' => 0.8,
        ]);

        $dataset->profile = $profile;

        $recommendations = $this->service->getRecommendations($dataset);

        $this->assertTrue(
            count(array_filter($recommendations, function($rec) {
                return strpos($rec, 'mixed_col') !== false && strpos($rec, 'types de données mixtes') !== false;
            })) > 0
        );
    }

    /** @test */
    public function it_generates_recommendations_for_outliers()
    {
        $user = User::factory()->create();
        $dataset = Dataset::create([
            'user_id' => $user->id,
            'name' => 'Test Dataset 3',
            'original_filename' => 'test3.csv',
            'storage_path' => 'datasets/test3.csv',
            'file_type' => 'csv',
            'row_count' => 200,
            'column_count' => 4,
            'status' => 'processed',
        ]);

        $profile = DatasetProfile::create([
            'dataset_id' => $dataset->id,
            'metrics_json' => [
                'outlier_col' => [
                    'type' => 'numeric',
                    'distinct_count' => 150,
                    'empty_count' => 0,
                    'min' => 0,
                    'max' => 1000,
                    'mean' => 50,
                    'outlier_count' => 30, // 15% outliers
                ],
                'normal_col' => [
                    'type' => 'numeric',
                    'distinct_count' => 100,
                    'empty_count' => 0,
                    'min' => 10,
                    'max' => 100,
                    'mean' => 55,
                    'outlier_count' => 0,
                ],
            ],
            'quality_score' => 0.7,
        ]);

        $dataset->profile = $profile;

        $recommendations = $this->service->getRecommendations($dataset);

        $this->assertTrue(
            count(array_filter($recommendations, function($rec) {
                return strpos($rec, 'outlier_col') !== false && strpos($rec, 'aberrantes') !== false;
            })) > 0
        );
    }

    /** @test */
    public function it_returns_general_message_when_no_issues()
    {
        $user = User::factory()->create();
        $dataset = Dataset::create([
            'user_id' => $user->id,
            'name' => 'Test Dataset 4',
            'original_filename' => 'test4.csv',
            'storage_path' => 'datasets/test4.csv',
            'file_type' => 'csv',
            'row_count' => 100,
            'column_count' => 5,
            'status' => 'processed',
        ]);

        $profile = DatasetProfile::create([
            'dataset_id' => $dataset->id,
            'metrics_json' => [
                'col1' => [
                    'type' => 'numeric',
                    'distinct_count' => 80,
                    'empty_count' => 2,
                    'min' => 10,
                    'max' => 100,
                    'mean' => 55,
                ],
                'col2' => [
                    'type' => 'string',
                    'distinct_count' => 90,
                    'empty_count' => 1,
                    'min' => null,
                    'max' => null,
                    'mean' => null,
                ],
            ],
            'quality_score' => 0.95,
        ]);

        $dataset->profile = $profile;

        $recommendations = $this->service->getRecommendations($dataset);

        // Should have at least the quality message
        $this->assertGreaterThanOrEqual(1, count($recommendations));
        $this->assertTrue(
            count(array_filter($recommendations, function($rec) {
                return strpos($rec, 'qualité globale') !== false;
            })) > 0
        );
    }
}