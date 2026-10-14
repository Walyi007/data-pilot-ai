@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">{{ __('Clean Dataset') }}: {{ $dataset->name }}</h4>
                </div>

                <div class="card-body">
                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    <div class="mb-4">
                        <h5>{{ __('Dataset Information') }}</h5>
                        <dl class="row">
                            <dt class="col-sm-3">{{ __('Original Filename') }}</dt>
                            <dd class="col-sm-9">{{ $dataset->original_filename }}</dd>

                            <dt class="col-sm-3">{{ __('File Type') }}</dt>
                            <dd class="col-sm-9">{{ ucfirst($dataset->file_type) }}</dd>

                            <dt class="col-sm-3">{{ __('Row Count') }}</dt>
                            <dd class="col-sm-9">{{ number_format($dataset->row_count) }}</dd>

                            <dt class="col-sm-3">{{ __('Column Count') }}</dt>
                            <dd class="col-sm-9">{{ number_format($dataset->column_count) }}</dd>

                            <dt class="col-sm-3">{{ __('Quality Score') }}</dt>
                            <dd class="col-sm-9">
                                <span class="badge bg-{{ $dataset->profile->quality_score >= 0.7 ? 'success' : ($dataset->profile->quality_score >= 0.4 ? 'warning' : 'danger') }}">
                                    {{ number_format($dataset->profile->quality_score, 2) }}
                                </span>
                            </dd>
                        </dl>
                    </div>

                    <div class="mb-4">
                        <h5>{{ __('Cleaning Recommendations') }}</h5>
                        @if(count($recommendations) > 0)
                        <div class="alert alert-info">
                            <ul class="mb-0">
                                @foreach($recommendations as $recommendation)
                                <li>{{ $recommendation }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @else
                        <p>{{ __('No specific recommendations. The dataset appears to be clean.') }}</p>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('dataset.clean.apply', $dataset) }}">
                        @csrf
                        <div class="mb-4">
                            <h5>{{ __('Select Cleaning Operations') }}</h5>

                            <!-- Remove Duplicates -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <input type="checkbox" id="remove_duplicates" name="operations[0][type]" value="remove_duplicates" class="form-check-input me-2">
                                    <label for="remove_duplicates" class="form-check-label mb-0">{{ __('Remove Duplicate Rows') }}</label>
                                </div>
                                <div class="card-body">
                                    <p>{{ __('This operation removes rows that are identical across all columns.') }}</p>
                                </div>
                            </div>

                            <!-- Fill Missing Values -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <input type="checkbox" id="fill_missing" name="operations[1][type]" value="fill_missing" class="form-check-input me-2">
                                    <label for="fill_missing" class="form-check-label mb-0">{{ __('Fill Missing Values') }}</label>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label for="fill_column" class="form-label">{{ __('Column') }}</label>
                                            <select id="fill_column" name="operations[1][column]" class="form-select w-64">
                                                <option value="">{{ __('Select a column') }}</option>
                                                @foreach($metrics as $columnName => $columnMetrics)
                                                <option value="{{ $columnName }}" {{ $columnMetrics['empty_count'] > 0 ? 'selected' : '' }}>
                                                    {{ $columnName }} ({!! $columnMetrics['empty_count'] !!} {{ __('empty') }})
                                                </option>
                                                @endforeach
                                            </select>
                                            @error('operations.1.column')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label for="fill_method" class="form-label">{{ __('Method') }}</label>
                                            <select id="fill_method" name="operations[1][method]" class="form-select w-64">
                                                <option value="mean">{{ __('Mean (numeric columns)') }}</option>
                                                <option value="median">{{ __('Median (numeric columns)') }}</option>
                                                <option value="mode">{{ __('Mode') }}</option>
                                                <option value="custom">{{ __('Custom Value') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4" id="custom_value_container" style="display: none;">
                                            <label for="fill_custom_value" class="form-label">{{ __('Custom Value') }}</label>
                                            <input type="text" id="fill_custom_value" name="operations[1][value]" class="form-control" placeholder="{{ __('Enter custom value') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Remove Outliers -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <input type="checkbox" id="remove_outliers" name="operations[2][type]" value="remove_outliers" class="form-check-input me-2">
                                    <label for="remove_outliers" class="form-check-label mb-0">{{ __('Handle Outliers') }}</label>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label for="outlier_column" class="form-label">{{ __('Column') }}</label>
                                            <select id="outlier_column" name="operations[2][column]" class="form-select w-64">
                                                <option value="">{{ __('Select a column') }}</option>
                                                @foreach($metrics as $columnName => $columnMetrics)
                                                @if($columnMetrics['type'] == 'numeric' && isset($columnMetrics['outlier_count']) && $columnMetrics['outlier_count'] > 0)
                                                <option value="{{ $columnName }}" selected>
                                                    {{ $columnName }} ({!! $columnMetrics['outlier_count'] !!} {{ __('outliers') }})
                                                </option>
                                                @endif
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="outlier_method" class="form-label">{{ __('Method') }}</label>
                                            <select id="outlier_method" name="operations[2][method]" class="form-select w-64">
                                                <option value="winsorize">{{ __('Winsorize (cap to bounds)') }}</option>
                                                <option value="remove">{{ __('Remove rows with outliers') }}</option>
                                                <option value="cap">{{ __('Cap to bounds') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <p class="small text-muted">
                                                {{ __('Outliers detected using IQR method (Q1 - 1.5*IQR, Q3 + 1.5*IQR)') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Convert Mixed Type -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <input type="checkbox" id="convert_mixed_type" name="operations[3][type]" value="convert_mixed_type" class="form-check-input me-2">
                                    <label for="convert_mixed_type" class="form-check-label mb-0">{{ __('Convert Mixed Type Columns') }}</label>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label for="convert_column" class="form-label">{{ __('Column') }}</label>
                                            <select id="convert_column" name="operations[3][column]" class="form-select w-64">
                                                <option value="">{{ __('Select a column') }}</option>
                                                @foreach($metrics as $columnName => $columnMetrics)
                                                @if($columnMetrics['type'] == 'mixed')
                                                <option value="{{ $columnName }}" selected>
                                                    {{ $columnName }} ({!! __('mixed type') !!})
                                                </option>
                                                @endif
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="convert_target_type" class="form-label">{{ __('Target Type') }}</label>
                                            <select id="convert_target_type" name="operations[3][target_type]" class="form-select w-64">
                                                <option value="string">{{ __('String') }}</option>
                                                <option value="numeric">{{ __('Numeric') }}</option>
                                                <option value="date">{{ __('Date') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <p class="small text-muted">
                                                {{ __('Converts column to a uniform type. Useful for columns with mixed data types.') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Trim Whitespace -->
                            <div class="card mb-3">
                                <div class="card-header">
                                    <input type="checkbox" id="trim_whitespace" name="operations[4][type]" value="trim_whitespace" class="form-check-input me-2">
                                    <label for="trim_whitespace" class="form-check-label mb-0">{{ __('Trim Whitespace') }}</label>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="trim_columns" class="form-label">{{ __('Columns (leave empty for all)') }}</label>
                                            <select id="trim_columns" name="operations[4][columns][]" class="form-select w-64" multiple size="5">
                                                @foreach($metrics as $columnName => $columnMetrics)
                                                <option value="{{ $columnName }}">{{ $columnName }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="small text-muted">
                                                {{ __('Removes leading and trailing whitespace from string values.') }}
                                            </p>
                                            <button type="button" id="select_all_columns" class="btn btn-sm btn-outline-secondary mt-2">
                                                {{ __('Select All') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('dataset.show', $dataset) }}" class="btn btn-outline-secondary me-2">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary">{{ __('Apply Cleaning') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show custom value input when method is custom
        const fillMethodSelect = document.getElementById('fill_method');
        const customValueContainer = document.getElementById('custom_value_container');
        if (fillMethodSelect && customValueContainer) {
            fillMethodSelect.addEventListener('change', function() {
                if (this.value === 'custom') {
                    customValueContainer.style.display = 'block';
                } else {
                    customValueContainer.style.display = 'none';
                }
            });
        }

        // Select all columns for trim whitespace
        const selectAllButton = document.getElementById('select_all_columns');
        const trimColumnsSelect = document.getElementById('trim_columns');
        if (selectAllButton && trimColumnsSelect) {
            selectAllButton.addEventListener('click', function() {
                // Select all options
                for (let i = 0; i < trimColumnsSelect.options.length; i++) {
                    trimColumnsSelect.options[i].selected = true;
                }
                // Trigger change event if needed
                trimColumnsSelect.dispatchEvent(new Event('change'));
            });
        }
    });
</script>
@endpush