@extends('layouts.admin')

@section('title', trans('bank-feeds::general.map_columns'))

@section('content')
    <div class="card">
        <form action="{{ route('bank-feeds.imports.map-columns', $import->id) }}" method="POST">
            @csrf

            <input type="hidden" name="path" value="{{ $path }}">

            <div class="card-header">
                <h3 class="mb-0">{{ trans('bank-feeds::general.map_columns') }} — {{ $import->filename }}</h3>
            </div>

            <div class="card-body">
                <p class="text-muted">{{ trans('bank-feeds::general.help.column_mapping') }}</p>

                {{-- Preset selector --}}
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label>Bank Preset (optional)</label>
                        <select id="preset-select" class="form-control">
                            <option value="">-- Select a preset --</option>
                            @foreach(\Modules\BankFeeds\Services\CsvImportService::PRESETS as $key => $preset)
                                <option value="{{ $key }}"
                                    data-date="{{ $preset['date'] }}"
                                    data-description="{{ $preset['description'] }}"
                                    data-amount="{{ $preset['amount'] ?? '' }}"
                                    data-type="{{ $preset['type'] ?? '' }}"
                                    data-credit="{{ $preset['credit'] ?? '' }}"
                                    data-debit="{{ $preset['debit'] ?? '' }}">
                                    {{ $preset['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Column mapping --}}
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="mapping_date">{{ trans('bank-feeds::general.fields.date') }} <span class="text-danger">*</span></label>
                            <select name="mapping[date]" id="mapping_date" class="form-control" required>
                                <option value="">-- Select --</option>
                                @foreach($preview['headers'] as $i => $header)
                                    <option value="{{ $i }}" {{ ($savedMapping['date'] ?? '') == $i ? 'selected' : '' }}>
                                        {{ $i }}: {{ $header }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="mapping_description">{{ trans('bank-feeds::general.fields.description') }} <span class="text-danger">*</span></label>
                            <select name="mapping[description]" id="mapping_description" class="form-control" required>
                                <option value="">-- Select --</option>
                                @foreach($preview['headers'] as $i => $header)
                                    <option value="{{ $i }}" {{ ($savedMapping['description'] ?? '') == $i ? 'selected' : '' }}>
                                        {{ $i }}: {{ $header }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="mapping_amount">{{ trans('bank-feeds::general.fields.amount') }}</label>
                            <select name="mapping[amount]" id="mapping_amount" class="form-control">
                                <option value="">-- Select --</option>
                                @foreach($preview['headers'] as $i => $header)
                                    <option value="{{ $i }}" {{ ($savedMapping['amount'] ?? '') == $i ? 'selected' : '' }}>
                                        {{ $i }}: {{ $header }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="mapping_type">{{ trans('bank-feeds::general.fields.type') }}</label>
                            <select name="mapping[type]" id="mapping_type" class="form-control">
                                <option value="">-- Not applicable --</option>
                                @foreach($preview['headers'] as $i => $header)
                                    <option value="{{ $i }}" {{ ($savedMapping['type'] ?? '') == $i ? 'selected' : '' }}>
                                        {{ $i }}: {{ $header }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="mapping_credit">{{ trans('bank-feeds::general.fields.credit') }}</label>
                            <select name="mapping[credit]" id="mapping_credit" class="form-control">
                                <option value="">-- Not applicable --</option>
                                @foreach($preview['headers'] as $i => $header)
                                    <option value="{{ $i }}" {{ ($savedMapping['credit'] ?? '') == $i ? 'selected' : '' }}>
                                        {{ $i }}: {{ $header }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="mapping_debit">{{ trans('bank-feeds::general.fields.debit') }}</label>
                            <select name="mapping[debit]" id="mapping_debit" class="form-control">
                                <option value="">-- Not applicable --</option>
                                @foreach($preview['headers'] as $i => $header)
                                    <option value="{{ $i }}" {{ ($savedMapping['debit'] ?? '') == $i ? 'selected' : '' }}>
                                        {{ $i }}: {{ $header }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group mt-4">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" name="save_mapping" value="1" class="custom-control-input" id="save_mapping" checked>
                                <label class="custom-control-label" for="save_mapping">
                                    {{ trans('bank-feeds::general.save_mapping') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Preview table --}}
                <h4 class="mt-4 mb-3">Preview (first {{ count($preview['rows']) }} rows)</h4>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light">
                            <tr>
                                @foreach($preview['headers'] as $i => $header)
                                    <th>
                                        <small class="text-muted">Col {{ $i }}</small><br>
                                        {{ $header }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($preview['rows'] as $row)
                                <tr>
                                    @foreach($row as $cell)
                                        <td>{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer text-right">
                <a href="{{ route('bank-feeds.imports.create') }}" class="btn btn-secondary">{{ trans('general.cancel') }}</a>
                <button type="submit" class="btn btn-success">
                    <span class="fa fa-check"></span> &nbsp;Import Transactions
                </button>
            </div>
        </form>
    </div>

    @push('scripts_start')
        <script>
            document.getElementById('preset-select').addEventListener('change', function() {
                var option = this.options[this.selectedIndex];
                if (!option.value) return;

                var fields = ['date', 'description', 'amount', 'type', 'credit', 'debit'];
                fields.forEach(function(field) {
                    var val = option.dataset[field];
                    var select = document.getElementById('mapping_' + field);
                    if (select && val !== '' && val !== undefined) {
                        select.value = val;
                    }
                });
            });
        </script>
    @endpush
@endsection
