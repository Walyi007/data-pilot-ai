@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dataset Details') }}</div>

                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">{{ __('Name') }}</dt>
                        <dd class="col-sm-9">{{ $dataset->name }}</dd>

                        <dt class="col-sm-3">{{ __('Original Filename') }}</dt>
                        <dd class="col-sm-9">{{ $dataset->original_filename }}</dd>

                        <dt class="col-sm-3">{{ __('File Type') }}</dt>
                        <dd class="col-sm-9">{{ ucfirst($dataset->file_type) }}</dd>

                        <dt class="col-sm-3">{{ __('Row Count') }}</dt>
                        <dd class="col-sm-9">{{ number_format($dataset->row_count) }}</dd>

                        <dt class="col-sm-3">{{ __('Column Count') }}</dt>
                        <dd class="col-sm-9">{{ number_format($dataset->column_count) }}</dd>

                        <dt class="col-sm-3">{{ __('Status') }}</dt>
                        <dd class="col-sm-9">
                            <span class="badge bg-{{ $dataset->status === 'processed' ? 'success' : ($dataset->status === 'failed' ? 'danger' : 'warning') }}">
                                {{ ucfirst($dataset->status) }}
                            </span>
                        </dd>

                        <dt class="col-sm-3">{{ __('Created At') }}</dt>
                        <dd class="col-sm-9">{{ $dataset->created_at->toDateTimeString() }}</dd>
                    </dl>

                    @if($recommendations && count($recommendations) > 0)
                    <div class="mt-4">
                        <h4 class="mb-3">{{ __('Cleaning Recommendations') }}</h4>
                        <ul class="list-group">
                            @foreach($recommendations as $recommendation)
                            <li class="list-group-item">{{ $recommendation }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="mt-4">
                        <a href="{{ route('dataset.import') }}" class="btn btn-outline-secondary me-2">{{ __('Import Another') }}</a>
                        @if($dataset->status === 'processed' && $dataset->profile)
                        <a href="{{ route('dataset.clean', $dataset) }}" class="btn btn-primary me-2">{{ __('Clean Dataset') }}</a>
                        <a href="{{ route('dataset.analytics', $dataset) }}" class="btn btn-outline-info me-2">{{ __('Analytics Dashboard') }}</a>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ __('Export Report') }}
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('dataset.report.profile.pdf', $dataset) }}">{{ __('Profile Report (PDF)') }}</a></li>
                                <li><a class="dropdown-item" href="{{ route('dataset.report.profile.excel', $dataset) }}">{{ __('Profile Report (Excel)') }}</a></li>
                                <li><a class="dropdown-item" href="{{ route('dataset.report.analytics.pdf', $dataset) }}>{{ __('Analytics Report (PDF)') }}</a></li>
                                <li><a class="dropdown-item" href="{{ route('dataset.report.analytics.excel', $dataset) }}>{{ __('Analytics Report (Excel)') }}</a></li>
                                @if($dataset->profile)
                                <li><a class="dropdown-item" href="{{ route('dataset.report.cleaning.pdf', $dataset) }}">{{ __('Cleaning Report (PDF)') }}</a></li>
                                @endif
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection