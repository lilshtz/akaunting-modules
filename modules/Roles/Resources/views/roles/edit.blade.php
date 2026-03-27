@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>{{ trans('roles::general.edit_role') }}: {{ $role->display_name }}</h1>
            <form action="{{ route('roles.roles.duplicate', $role->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">{{ trans('general.duplicate') }}</button>
            </form>
        </div>

        <form action="{{ route('roles.roles.update', $role->id) }}" method="POST">
            @csrf
            @method('PUT')
            @include('roles::roles._form')
        </form>
    </div>
@endsection
