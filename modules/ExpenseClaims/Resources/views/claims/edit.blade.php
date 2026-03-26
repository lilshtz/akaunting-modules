@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <h1>{{ trans('general.edit') }} {{ $claim->claim_number ?: $claim->id }}</h1>

        <form action="{{ route('expense-claims.claims.update', $claim->id) }}" method="post" enctype="multipart/form-data">
            @method('PATCH')
            @include('expense-claims::claims._form')
        </form>
    </div>
@endsection
