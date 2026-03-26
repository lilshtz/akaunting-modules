@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <h1>{{ trans('expense-claims::general.new_claim') }}</h1>

        <form action="{{ route('expense-claims.claims.store') }}" method="post" enctype="multipart/form-data">
            @include('expense-claims::claims._form')
        </form>
    </div>
@endsection
