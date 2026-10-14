<!DOCTYPE html>
<html>
<head>
    <title>Cleaning Report - {{ $originalDataset->name }} → {{ $cleanedDataset->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1 { color: #2c3e50; text-align: center; }
        h2 { color: #3498db; border-bottom: 2px solid #ecf0f1; padding-bottom: 5px; }
        .info-box { background: #f8f9fa; border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin: 20px 0; }
        .info-box h3 { margin-top: 0; color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .improvement { color: #2ecc71; font-weight: bold; }
        .footer { text-align: center; margin-top: 30px; font-size: 0.9em; color: #7f8c8d; }
    </style>
</head>
<body>
    <h1>Data Cleaning Report</h1>
    
    <div class="info-box">
        <h3>Cleaning Summary</h3>
        <p><strong>Original Dataset:</strong> {{ $originalDataset->name }} ({{ $originalDataset->original_filename }})</p>
        <p><strong>Cleaned Dataset:</strong> {{ $cleanedDataset->name }} ({{ $cleanedDataset->original_filename }})</p>
        <p><strong>Cleaning Date:</strong> {{ now()->toDateTimeString() }}</p>
    </div>

    <div class="info-box">
        <h3>Quality Score Comparison</h3>
        <table>
            <tr>
                <th>Dataset</th>
                <th>Quality Score</th>
                <th>Improvement</th>
            </tr>
            <tr>
                <td>Original</td>
                <td>
                    <span style="background: {{ $originalProfile->quality_score >= 0.7 ? '#2ecc71' : ($originalProfile->quality_score >= 0.4 ? '#f1c40f' : '#e74c3c') }}; 
                            color: white; padding: 2px 6px; border-radius: 3px;">
                        {{ number_format($originalProfile->quality_score ?? 0, 2) }}
                    </span>
                </td>
                <td rowspan="2">@if($cleanedProfile->quality_score > $originalProfile->quality_score)
                        <span class="improvement">+{{ number_format($cleanedProfile->quality_score - $originalProfile->quality_score, 2) }}</span>
                    @elseif($cleanedProfile->quality_score < $originalProfile->quality_score)
                        <span style="color: #e74c3c;">{{ number_format($originalProfile->quality_score - $cleanedProfile->quality_score, 2) }}</span>
                    @else
                        <span>0.00</span>
                    @endif</td>
            </tr>
            <tr>
                <td>Cleaned</td>
                <td>
                    <span style="background: {{ $cleanedProfile->quality_score >= 0.7 ? '#2ecc71' : ($cleanedProfile->quality_score >= 0.4 ? '#f1c40f' : '#e74c3c') }}; 
                            color: white; padding: 2px 6px; border-radius: 3px;">
                        {{ number_format($cleanedProfile->quality_score ?? 0, 2) }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div class="info-box">
        <h3>Row Count Comparison</h3>
        <table>
            <tr>
                <th>Dataset</th>
                <th>Row Count</th>
                <th>Change</th>
            </tr>
            <tr>
                <td>Original</td>
                <td>{{ number_format($originalDataset->row_count) }}</td>
                <td rowspan="2">
                    @if($cleanedDataset->row_count < $originalDataset->row_count)
                        <span class="improvement">-{{ number_format($originalDataset->row_count - $cleanedDataset->row_count) }} rows removed</span>
                    @elseif($cleanedDataset->row_count > $originalDataset->row_count)
                        <span style="color: #e74c3c;">+{{ number_format($cleanedDataset->row_count - $originalDataset->row_count) }} rows added</span>
                    @else
                        <span>No change</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td>Cleaned</td>
                <td>{{ number_format($cleanedDataset->row_count) }}</td>
            </tr>
        </table>
    </div>

    <div class="info-box">
        <h3>Column-by-Column Improvements</h3>
        @if(count($originalProfile->metrics_json) > 0)
        <table>
            <thead>
                <tr>
                    <th>Column</th>
                    <th>Metric</th>
                    <th>Original</th>
                    <th>Cleaned</th>
                    <th>Improvement</th>
                </tr>
            </thead>
            <tbody>
                @foreach($originalProfile->metrics_json as $columnName => $originalMetrics)
                @php
                    $cleanedMetrics = $cleanedProfile->metrics_json[$columnName] ?? [];
                @endphp
                <tr>
                    <td>{{ $columnName }}</td>
                    <td>Empty Count</td>
                    <td>{{ $originalMetrics['empty_count'] ?? 0 }}</td>
                    <td>{{ $cleanedMetrics['empty_count'] ?? 0 }}</td>
                    <td>
                        @if(($cleanedMetrics['empty_count'] ?? 0) < ($originalMetrics['empty_count'] ?? 0))
                            <span class="improvement">-{{ ($originalMetrics['empty_count'] ?? 0) - ($cleanedMetrics['empty_count'] ?? 0) }}</span>
                        @elseif(($cleanedMetrics['empty_count'] ?? 0) > ($originalMetrics['empty_count'] ?? 0))
                            <span style="color: #e74c3c;">+{{ ($cleanedMetrics['empty_count'] ?? 0) - ($originalMetrics['empty_count'] ?? 0) }}</span>
                        @else
                            <span>0</span>
                        @endif
                    </td>
                </tr>
                @if(isset($originalMetrics['outlier_count']) || isset($cleanedMetrics['outlier_count']))
                <tr>
                    <td></td>
                    <td>Outlier Count</td>
                    <td>{{ $originalMetrics['outlier_count'] ?? 0 }}</td>
                    <td>{{ $cleanedMetrics['outlier_count'] ?? 0 }}</td>
                    <td>
                        @if(($cleanedMetrics['outlier_count'] ?? 0) < ($originalMetrics['outlier_count'] ?? 0))
                            <span class="improvement">-{{ ($originalMetrics['outlier_count'] ?? 0) - ($cleanedMetrics['outlier_count'] ?? 0) }}</span>
                        @elseif(($cleanedMetrics['outlier_count'] ?? 0) > ($originalMetrics['outlier_count'] ?? 0))
                            <span style="color: #e74c3c;">+{{ ($cleanedMetrics['outlier_count'] ?? 0) - ($originalMetrics['outlier_count'] ?? 0) }}</span>
                        @else
                            <span>0</span>
                        @endif
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
        @else
        <p>No column metrics available for comparison.</p>
        @endif
    </div>

    <div class="footer">
        Generated by DataPilot AI on {{ now()->toDateTimeString() }}
    </div>
</body>
</html>