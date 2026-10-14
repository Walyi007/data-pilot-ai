@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">{{ __('Analytics Dashboard') }}: {{ $dataset->name }}</h4>
                </div>

                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">{{ __('Quality Score') }}</h5>
                                    <p class="card-text display-4">
                                        <span class="badge bg-{{ $qualityScore >= 0.7 ? 'success' : ($qualityScore >= 0.4 ? 'warning' : 'danger') }}">
                                            {{ number_format($qualityScore, 2) }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">{{ __('Rows') }}</h5>
                                    <p class="card-text display-4">{{ number_format($rowCount) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">{{ __('Columns') }}</h5>
                                    <p class="card-text display-4">{{ number_format($columnCount) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">{{ __('File Type') }}</h5>
                                    <p class="card-text display-4">{{ ucfirst($dataset->file_type) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">{{ __('Missing Values per Column') }}</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="missingChart" height="80"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">{{ __('Data Types Distribution') }}</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="typeChart" height="80"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(count($numericColumns) > 0)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">{{ __('Numeric Columns Statistics') }}</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>{{ __('Column') }}</th>
                                        <th>{{ __('Min') }}</th>
                                        <th>{{ __('Max') }}</th>
                                        <th>{{ __('Mean') }}</th>
                                        <th>{{ __('Outliers') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($numericColumns as $columnName => $stats)
                                    <tr>
                                        <td>{{ $columnName }}</td>
                                        <td>{{ $stats['min'] ?? 'N/A' }}</td>
                                        <td>{{ $stats['max'] ?? 'N/A' }}</td>
                                        <td>{{ number_format($stats['mean'] ?? 0, 2) }}</td>
                                        <td>{{ $stats['outlier_count'] }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <div class="mt-4">
                        <a href="{{ route('dataset.show', $dataset) }}" class="btn btn-outline-secondary me-2">{{ __('Back to Dataset') }}</a>
                        <a href="{{ route('dataset.clean', $dataset) }}" class="btn btn-primary">{{ __('Clean Dataset') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Missing values chart
        const missingCtx = document.getElementById('missingChart');
        if (missingCtx) {
            new Chart(missingCtx, {
                type: 'bar',
                data: {
                    labels: {{ json_encode($missingLabels) }},
                    datasets: [{
                        label: {{ __('Missing Values') }},
                        data: {{ json_encode($missingData) }},
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: {{ __('Count') }}
                            }
                        },
                        x: {
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45
                            }
                        }
                    }
                }
            });
        }

        // Data types pie chart
        const typeCtx = document.getElementById('typeChart');
        if (typeCtx) {
            new Chart(typeCtx, {
                type: 'pie',
                data: {
                    labels: {{ json_encode($typeLabels) }},
                    datasets: [{
                        label: {{ __('Data Types') }},
                        data: {{ json_encode($typeData) }},
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.5)',
                            'rgba(54, 162, 235, 0.5)',
                            'rgba(255, 206, 86, 0.5)',
                            'rgba(75, 192, 192, 0.5)',
                            'rgba(153, 102, 255, 0.5)',
                            'rgba(255, 159, 64, 0.5)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        }
    });
</script>
@endpush