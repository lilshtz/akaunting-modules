@extends('layouts.admin')

@section('title', trans('general.add_new', ['type' => trans('bank-feeds::general.rule')]))

@section('content')
    <div class="card">
        <form action="{{ route('bank-feeds.rules.store') }}" method="POST">
            @csrf

            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="field">{{ trans('bank-feeds::general.fields.field') }} <span class="text-danger">*</span></label>
                            <select name="field" id="field" class="form-control" required>
                                @foreach($fields as $field)
                                    <option value="{{ $field }}" {{ old('field') == $field ? 'selected' : '' }}>
                                        {{ trans('bank-feeds::general.rule_fields.' . $field) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('field')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="operator">{{ trans('bank-feeds::general.fields.operator') }} <span class="text-danger">*</span></label>
                            <select name="operator" id="operator" class="form-control" required>
                                @foreach($operators as $op)
                                    <option value="{{ $op }}" {{ old('operator') == $op ? 'selected' : '' }}>
                                        {{ trans('bank-feeds::general.operators.' . $op) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('operator')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="value">{{ trans('bank-feeds::general.fields.value') }} <span class="text-danger">*</span></label>
                            <input type="text" name="value" id="value" class="form-control" value="{{ old('value') }}" required placeholder="e.g., LUMBER or 100,500">
                            <small class="form-text text-muted">{{ trans('bank-feeds::general.help.between_value') }}</small>
                            @error('value')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="category_id">{{ trans('bank-feeds::general.fields.category') }}</label>
                            <input type="number" name="category_id" id="category_id" class="form-control" value="{{ old('category_id') }}" placeholder="Category ID">
                            @error('category_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="vendor_id">{{ trans('bank-feeds::general.fields.vendor') }}</label>
                            <input type="number" name="vendor_id" id="vendor_id" class="form-control" value="{{ old('vendor_id') }}" placeholder="Vendor ID (optional)">
                            @error('vendor_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="priority">{{ trans('bank-feeds::general.fields.priority') }}</label>
                            <input type="number" name="priority" id="priority" class="form-control" value="{{ old('priority', 0) }}" min="0">
                            @error('priority')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group mt-4">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" name="enabled" value="1" class="custom-control-input" id="enabled" {{ old('enabled', true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="enabled">
                                    {{ trans('bank-feeds::general.fields.enabled') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer text-right">
                <a href="{{ route('bank-feeds.rules.index') }}" class="btn btn-secondary">{{ trans('general.cancel') }}</a>
                <button type="submit" class="btn btn-success">
                    <span class="fa fa-check"></span> &nbsp;{{ trans('general.save') }}
                </button>
            </div>
        </form>
    </div>
@endsection
