@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <h1>{{ trans('general.add_new') }} {{ trans('expense-claims::general.category') }}</h1>
        <form action="{{ route('expense-claims.categories.store') }}" method="post">
            @include('expense-claims::categories._form')
        </form>
    </div>
@endsection
