@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <h1>{{ trans('general.import') }} {{ trans('expense-claims::general.claims') }}</h1>

        <form action="{{ route('expense-claims.claims.import.store') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label>CSV</label>
                <input type="file" name="file" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">{{ trans('general.import') }}</button>
        </form>
    </div>
@endsection
