<?php

namespace App\Services;

use App\Models\Dataset;
use App\Models\DatasetProfile;

class DatasetCleaningRecommendationService
{
    /**
     * Generate cleaning recommendations based on dataset profile.
     *
     * @param  Dataset  $dataset
     * @return array  List of recommendation strings
     */
    public function getRecommendations(Dataset $dataset): array
    {
        $recommendations = [];

        if (!$dataset->profile) {
            return $recommendations;
        }

        $metrics = $dataset->profile->metrics_json ?? [];
        $qualityScore = $dataset->profile->quality_score ?? 0.0;

        // Overall quality score recommendation
        if ($qualityScore < 0.5) {
            $recommendations[] = 'La qualité globale des données est faible (score: ' . number_format($qualityScore, 2) . '). Envisagez un nettoyage approfondi.';
        } elseif ($qualityScore < 0.7) {
            $recommendations[] = 'La qualité globale des données est modérée (score: ' . number_format($qualityScore, 2) . '). Quelques améliorations sont possibles.';
        } else {
            $recommendations[] = 'La qualité globale des données est bonne (score: ' . number_format($qualityScore, 2) . ').';
        }

        // Analyze each column
        foreach ($metrics as $columnName => $columnMetrics) {
            $type = $columnMetrics['type'] ?? 'unknown';
            $emptyCount = $columnMetrics['empty_count'] ?? 0;
            $distinctCount = $columnMetrics['distinct_count'] ?? 0;
            $outlierCount = $columnMetrics['outlier_count'] ?? 0;
            // Note: we don't have row count here, but we can get from dataset
            $rowCount = $dataset->row_count;
            $colCount = $dataset->column_count;

            if ($rowCount > 0) {
                $emptyRatio = $emptyCount / $rowCount;
                if ($emptyRatio > 0.5) {
                    $recommendations[] = "La colonne '$columnName' contient un pourcentage élevé de valeurs vides (" . number_format($emptyRatio * 100, 1) . "%). Envisagez de supprimer cette colonne ou de remplir les valeurs manquantes avec une valeur appropriée (moyenne, médiane ou mode).";
                } elseif ($emptyRatio > 0.1) {
                    $recommendations[] = "La colonne '$columnName' contient certaines valeurs vides (" . number_format($emptyRatio * 100, 1) . "%). Vous pourriez vouloir les remplir ou supprimer les lignes concernées.";
                }
            }

            if ($type === 'mixed') {
                $recommendations[] = "La colonne '$columnName' contient des types de données mixtes. Envisagez de convertir toute la colonne en un type uniforme (par exemple, tout en texte) ou de séparer la colonne en plusieurs colonnes selon le type.";
            }

            if ($outlierCount > 0 && $rowCount > 0) {
                $outlierRatio = $outlierCount / $rowCount;
                if ($outlierRatio > 0.1) {
                    $recommendations[] = "La colonne '$columnName' contient un pourcentage élevé de valeurs aberrantes (" . number_format($outlierRatio * 100, 1) . "%). Envisagez d'examiner ces valeurs aberrantes : elles pourraient indiquer des erreurs de saisie ou des phénomènes réels intéressants. Vous pouvez choisir de les winsoriser, de les transformer ou de les supprimer selon le contexte.";
                }
            }

            // Duplicate rows are handled at dataset level, not per column
        }

        // Duplicate rows recommendation
        if ($dataset->row_count > 0) {
            // We need duplicate count from metrics? Not stored. We could compute from quality score components? 
            // For simplicity, we can add a generic recommendation if quality score is affected by duplicates.
            // But we don't have duplicate count stored. We'll skip specific duplicate recommendation for now.
            // Alternatively, we could compute duplicate ratio from quality score if we know other ratios? Too complex.
            // We'll leave it out for now, but note that the quality score already factors in duplicates.
        }

        // If no specific recommendations, add a general one
        if (empty($recommendations)) {
            $recommendations[] = 'Aucune recommandation de nettoyage spécifique n\'a été générée. Les données semblent relativement propres.';
        }

        return $recommendations;
    }
}