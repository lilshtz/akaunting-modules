@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <h1 class="mb-3">{{ trans('roles::general.new_role') }}</h1>

        <form action="{{ route('roles.roles.store') }}" method="POST">
            @csrf
            @include('roles::roles._form')
        </form>
    </div>
@endsection
