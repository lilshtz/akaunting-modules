@extends('layouts.admin')

@section('title', trans('bank-feeds::general.import_history'))

@section('new_button')
    <a href="{{ route('bank-feeds.imports.create') }}" class="btn btn-success btn-sm">
        <span class="fa fa-plus"></span> &nbsp;{{ trans('bank-feeds::general.import_file') }}
    </a>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0">{{ trans('bank-feeds::general.import_history') }}</h3>
        </div>

        <div class="table-responsive">
            <table class="table table-flush table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>{{ trans('bank-feeds::general.fields.filename') }}</th>
                        <th>{{ trans('bank-feeds::general.fields.format') }}</th>
                        <th>{{ trans('bank-feeds::general.fields.bank_account') }}</th>
                        <th class="text-center">{{ trans('bank-feeds::general.fields.row_count') }}</th>
                        <th class="text-center">{{ trans('bank-feeds::general.fields.status') }}</th>
                        <th>{{ trans('bank-feeds::general.fields.imported_at') }}</th>
                        <th class="text-center">{{ trans('general.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($imports as $import)
                        <tr>
                            <td>{{ $import->filename }}</td>
                            <td>
                                <span class="badge badge-info">{{ strtoupper($import->format) }}</span>
                            </td>
                            <td>{{ $import->bank_account_id }}</td>
                            <td class="text-center">{{ $import->row_count }}</td>
                            <td class="text-center">
                                @php
                                    $statusClass = match($import->status) {
                                        'complete' => 'success',
                                        'failed' => 'danger',
                                        'processing' => 'warning',
                                        default => 'secondary',
                                    };
                                @endphp
                                <span class="badge badge-{{ $statusClass }}">
                                    {{ trans('bank-feeds::general.statuses.' . $import->status) }}
                                </span>
                            </td>
                            <td>{{ $import->imported_at ? $import->imported_at->format('M d, Y H:i') : '-' }}</td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <a class="btn btn-neutral btn-sm" href="#" role="button" data-toggle="dropdown">
                                        <i class="fa fa-ellipsis-h"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item" href="{{ route('bank-feeds.transactions.index', ['import_id' => $import->id]) }}">
                                            <i class="fa fa-list"></i> {{ trans('bank-feeds::general.view_transactions') }}
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <form action="{{ route('bank-feeds.imports.destroy', $import->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger" onclick="return confirm('{{ trans('general.delete_confirm') }}')">
                                                <i class="fa fa-trash"></i> {{ trans('general.delete') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">
                                <p class="my-4">{{ trans('general.no_records') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $imports->links() }}
        </div>
    </div>
@endsection
