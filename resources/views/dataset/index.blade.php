@extends('layouts.app')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Mes jeux de données</h2>
        <a href="{{ route('dataset.import') }}" class="btn btn-primary">Importer un nouveau dataset</a>
    </div>
    <div class="card-body">
        @if($datasets->isEmpty())
            <p class="text-center text-gray-500">Vous n'avez pas encore importé de jeu de données.</p>
        @else
            <table class="table min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lignes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Colonnes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Créé le</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($datasets as $dataset)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $dataset->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($dataset->row_count) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($dataset->column_count) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $dataset->status === 'processed' ? 'bg-green-100 text-green-800' : ($dataset->status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}
                                ">
                                    {{ ucfirst($dataset->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $dataset->created_at->toFormattedDateString() }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('dataset.show', $dataset) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Voir</a>
                                @if($dataset->status === 'processed' && $dataset->profile)
                                    <a href="{{ route('dataset.analytics', $dataset) }}" class="text-blue-600 hover:text-blue-900 mr-2">Analytics</a>
                                    <a href="{{ route('dataset.clean', $dataset) }}" class="text-green-600 hover:text-green-900">Nettoyer</a>
                                @else
                                    <span class="text-gray-400">Analytics</span> <span class="text-gray-400 mr-2">Nettoyer</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection