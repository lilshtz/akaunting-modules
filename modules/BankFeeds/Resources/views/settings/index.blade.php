@extends('layouts.admin')

@section('title', trans('bank-feeds::general.settings'))

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0">{{ trans('bank-feeds::general.settings') }}</h3>
        </div>

        <div class="card-body">
            {{-- Saved Column Mappings --}}
            <h4>{{ trans('bank-feeds::general.column_mapping') }}s</h4>
            <p class="text-muted">Saved column mappings per bank account for CSV re-imports.</p>

            @if(count($mappings) > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ trans('bank-feeds::general.fields.bank_account') }} ID</th>
                                <th>{{ trans('bank-feeds::general.fields.date') }} Col</th>
                                <th>{{ trans('bank-feeds::general.fields.description') }} Col</th>
                                <th>{{ trans('bank-feeds::general.fields.amount') }} Col</th>
                                <th class="text-center">{{ trans('general.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mappings as $accountId => $mapping)
                                <tr>
                                    <td>{{ $accountId }}</td>
                                    <td>{{ $mapping['date'] ?? '-' }}</td>
                                    <td>{{ $mapping['description'] ?? '-' }}</td>
                                    <td>{{ $mapping['amount'] ?? '-' }}</td>
                                    <td class="text-center">
                                        <form action="{{ route('bank-feeds.settings.delete-mapping', $accountId) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ trans('general.delete_confirm') }}')">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No saved column mappings. Mappings are saved when you import a CSV and check "Save mapping for this account".</p>
            @endif
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0">Quick Links</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <a href="{{ route('bank-feeds.imports.create') }}" class="btn btn-outline-primary btn-block mb-2">
                        <i class="fa fa-upload mr-2"></i> {{ trans('bank-feeds::general.import_file') }}
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('bank-feeds.rules.index') }}" class="btn btn-outline-primary btn-block mb-2">
                        <i class="fa fa-list mr-2"></i> {{ trans('bank-feeds::general.rules') }}
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="{{ route('bank-feeds.transactions.index') }}" class="btn btn-outline-primary btn-block mb-2">
                        <i class="fa fa-exchange-alt mr-2"></i> {{ trans('bank-feeds::general.transactions') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
