@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>{{ __('Dataset Details') }}</h2>
    </div>
    <div class="card-body">
        <div class="input-group">
            <label for="name">{{ __('Name') }}</label>
            <p id="name" class="form-control-plaintext">{{ $dataset->name }}</p>
        </div>

        <div class="input-group">
            <label for="original_filename">{{ __('Original Filename') }}</label>
            <p id="original_filename" class="form-control-plaintext">{{ $dataset->original_filename }}</p>
        </div>

        <div class="input-group">
            <label for="file_type">{{ __('File Type') }}</label>
            <p id="file_type" class="form-control-plaintext">{{ ucfirst($dataset->file_type) }}</p>
        </div>

        <div class="input-group">
            <label for="row_count">{{ __('Row Count') }}</label>
            <p id="row_count" class="form-control-plaintext">{{ number_format($dataset->row_count) }}</p>
        </div>

        <div class="input-group">
            <label for="column_count">{{ __('Column Count') }}</label>
            <p id="column_count" class="form-control-plaintext">{{ number_format($dataset->column_count) }}</p>
        </div>

        <div class="input-group">
            <label for="status">{{ __('Status') }}</label>
            <span class="badge 
                {{ $dataset->status === 'processed' ? 'badge-success' : ($dataset->status === 'failed' ? 'badge-danger' : 'badge-warning') }}
            ">
                {{ ucfirst($dataset->status) }}
            </span>
        </div>

        <div class="input-group">
            <label for="created_at">{{ __('Created At') }}</label>
            <p id="created_at" class="form-control-plaintext">{{ $dataset->created_at->toDateTimeString() }}</p>
        </div>

        @if($recommendations && count($recommendations) > 0)
        <div class="mt-4">
            <h3>{{ __('Cleaning Recommendations') }}</h3>
            <p>{{ __('La qualité globale des données est faible (score : :score). Prévoyez un nettoyage approfondi.', ['score' => number_format($dataset->profile?->quality_score ?? 0, 2)]) }}</p>
            <ul class="mt-2" style="list-style: none; padding-left: 0;">
                @foreach($recommendations as $recommendation)
                <li style="margin-bottom: 8px; padding: 8px; background: rgba(0,0,0,0.02); border-radius: 6px;">
                    {{ $recommendation }}
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="mt-4">
            <a href="{{ route('dataset.import') }}" class="btn btn-outline">{{ __('Import Another') }}</a>
            @if($dataset->status === 'processed' && $dataset->profile)
            <a href="{{ route('dataset.clean', $dataset) }}" class="btn btn-primary">{{ __('Clean Dataset') }}</a>
            <a href="{{ route('dataset.analytics', $dataset) }}" class="btn btn-outline">{{ __('Analytics Dashboard') }}</a>
            <div class="mt-3">
                <button class="btn btn-outline" id="exportDropdownBtn" aria-haspopup="true" aria-expanded="false">
                    {{ __('Export Report') }}
                </button>
                <div id="exportDropdown" style="display: none; position: absolute; background: white; border: 1px solid var(--border); border-radius: var(--radius); box-shadow: var(--shadow-lg); margin-top: 8px; min-width: 200px; z-index: 1000; padding: 12px 0;">
                    <a href="{{ route('dataset.report.profile.pdf', $dataset) }}" class="dropdown-item block px-4 py-2">{{ __('Profile Report (PDF)') }}</a>
                    <a href="{{ route('dataset.report.profile.excel', $dataset) }}" class="dropdown-item block px-4 py-2">{{ __('Profile Report (Excel)') }}</a>
                    <a href="{{ route('dataset.report.analytics.pdf', $dataset) }}" class="dropdown-item block px-4 py-2">{{ __('Analytics Report (PDF)') }}</a>
                    <a href="{{ route('dataset.report.analytics.excel', $dataset) }}" class="dropdown-item block px-4 py-2">{{ __('Analytics Report (Excel)') }}</a>
                    @if($dataset->profile)
                    <a href="{{ route('dataset.report.cleaning.pdf', $dataset) }}" class="dropdown-item block px-4 py-2">{{ __('Cleaning Report (PDF)') }}</a>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('exportDropdownBtn');
        const dropdown = document.getElementById('exportDropdown');
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });
        document.addEventListener('click', function(e) {
            if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    });
</script>
@endsection