@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <h1>{{ trans('general.edit') }} {{ $category->name }}</h1>
        <form action="{{ route('expense-claims.categories.update', $category->id) }}" method="post">
            @method('PATCH')
            @include('expense-claims::categories._form')
        </form>
    </div>
@endsection
