@extends('layouts.admin')

@section('title', trans('bank-feeds::general.import_file'))

@section('content')
    <div class="card">
        <form action="{{ route('bank-feeds.imports.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <p class="text-muted">{{ trans('bank-feeds::general.help.csv_upload') }}</p>
                        <p class="text-muted">{{ trans('bank-feeds::general.help.ofx_upload') }}</p>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="bank_account_id">{{ trans('bank-feeds::general.fields.bank_account') }} <span class="text-danger">*</span></label>
                            <input type="number" name="bank_account_id" id="bank_account_id" class="form-control" required placeholder="Enter bank account ID" value="{{ old('bank_account_id') }}">
                            @error('bank_account_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="file">{{ trans('bank-feeds::general.fields.file') }} <span class="text-danger">*</span></label>
                            <input type="file" name="file" id="file" class="form-control" required accept=".csv,.ofx,.qfx">
                            <small class="form-text text-muted">
                                {{ trans('bank-feeds::general.formats.csv') }}, {{ trans('bank-feeds::general.formats.ofx') }}, {{ trans('bank-feeds::general.formats.qfx') }} (max 10MB)
                            </small>
                            @error('file')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer text-right">
                <a href="{{ route('bank-feeds.imports.index') }}" class="btn btn-secondary">{{ trans('general.cancel') }}</a>
                <button type="submit" class="btn btn-success">
                    <span class="fa fa-upload"></span> &nbsp;{{ trans('bank-feeds::general.import_file') }}
                </button>
            </div>
        </form>
    </div>
@endsection
