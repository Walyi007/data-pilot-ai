<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiInsightService
{
    /**
     * Get AI-generated insight for a dataset profile.
     *
     * @param  array  $metrics  Dataset metrics (from profiling)
     * @param  float  $qualityScore Quality score (0-1)
     * @param  string  $datasetName Name of the dataset
     * @return string  Insight text
     */
    public function getInsight(array $metrics, float $qualityScore, string $datasetName): string
    {
        $apiKey = config('services.openai.key');

        if (empty($apiKey)) {
            return $this->getFallbackInsight($metrics, $qualityScore, $datasetName);
        }

        $prompt = $this->buildPrompt($metrics, $qualityScore, $datasetName);

        try {
            $response = Http::withToken($apiKey)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'system', 'content' => 'You are a helpful data analyst assistant. Provide clear, concise insights about dataset quality and cleaning recommendations in plain language.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 200,
                ]);

            $data = $response->json();

            if (isset($data['choices'][0]['message']['content'])) {
                return trim($data['choices'][0]['message']['content']);
            }

            return $this->getFallbackInsight($metrics, $qualityScore, $datasetName);
        } catch (\Exception $e) {
            // Log the exception (you can use Log::error($e);)
            return $this->getFallbackInsight($metrics, $qualityScore, $datasetName);
        }
    }

    /**
     * Build the prompt for the AI.
     *
     * @param  array  $metrics
     * @param  float  $qualityScore
     * @param  string  $datasetName
     * @return string
     */
    protected function buildPrompt(array $metrics, float $qualityScore, string $datasetName): string
    {
        $metricLines = [];
        foreach ($metrics as $column => $columnMetrics) {
            $line = "- {$column}: type={$columnMetrics['type']}, empty={$columnMetrics['empty_count']}, distinct={$columnMetrics['distinct_count']}";
            if (!empty($columnMetrics['mean'])) {
                $line .= ", mean=" . number_format($columnMetrics['mean'], 2);
            }
            if (!empty($columnMetrics['outlier_count'])) {
                $line .= ", outliers={$columnMetrics['outlier_count']}";
            }
            $metricLines[] = $line;
        }

        $metricsText = implode(PHP_EOL, $metricLines);

        return <<<PROMPT
Dataset Name: {$datasetName}
Quality Score: {$qualityScore} (0=poor, 1=excellent)

Column Metrics:
{$metricsText}

Based on the above information, please provide:
1. A brief explanation of the overall data quality issues.
2. Specific cleaning recommendations (e.g., handle missing values, outliers, mixed types).
3. Any notable patterns or anomalies you observe.

Keep the response concise and actionable.
PROMPT;
    }

    /**
     * Fallback insight when AI service is not available.
     *
     * @param  array  $metrics
     * @param  float  $qualityScore
     * @param  string  $datasetName
     * @return string
     */
    protected function getFallbackInsight(array $metrics, float $qualityScore, string $datasetName): string
    {
        $insights = [];

        if ($qualityScore < 0.4) {
            $insights[] = "The overall data quality is poor (score: " . number_format($qualityScore, 2) . "). Significant cleaning is recommended.";
        } elseif ($qualityScore < 0.7) {
            $insights[] = "The data quality is moderate (score: " . number_format($qualityScore, 2) . "). Some cleaning will improve usability.";
        } else {
            $insights[] = "The data quality is good (score: " . number_format($qualityScore, 2) . "). Minor cleaning may suffice.";
        }

        $highEmpty = array_filter($metrics, fn($m) => !empty($m['empty_count']) && ($m['empty_count'] / ($m['empty_count'] + ($m['distinct_count'] ?? 1))) > 0.5);
        if (!empty($highEmpty)) {
            $insights[] = "Several columns have high percentages of missing values (>50%). Consider imputation or removal.";
        }

        $mixedTypes = array_filter($metrics, fn($m) => $m['type'] === 'mixed');
        if (!empty($mixedTypes)) {
            $insights[] = "Mixed-type columns detected. Consider converting to a consistent type (e.g., string or numeric).";
        }

        $highOutliers = array_filter($metrics, fn($m) => !empty($m['outlier_count']) && ($m['outlier_count'] / ($m['distinct_count'] ?? 1)) > 0.2);
        if (!empty($highOutliers)) {
            $insights[] = "Some columns have a high proportion of outlier values. Consider winsorizing or removing extreme values.";
        }

        if (empty($insights)) {
            $insights[] = "No major issues detected. The dataset appears ready for analysis.";
        }

        return implode(' ', $insights);
    }
}