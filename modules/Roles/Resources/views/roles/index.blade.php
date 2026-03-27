@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1>{{ trans('roles::general.roles') }}</h1>
            <a href="{{ route('roles.roles.create') }}" class="btn btn-primary">{{ trans('roles::general.new_role') }}</a>
        </div>

        <table class="table table-striped">
            <thead>
            <tr>
                <th>{{ trans('general.name') }}</th>
                <th>{{ trans('general.description') }}</th>
                <th>{{ trans('roles::general.assigned_users') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($roles as $role)
                <tr>
                    <td>{{ $role->display_name }}</td>
                    <td>{{ $role->description }}</td>
                    <td>{{ $role->assigned_users_count }}</td>
                    <td class="text-end">
                        <a href="{{ route('roles.roles.edit', $role->id) }}" class="btn btn-sm btn-outline-primary">{{ trans('general.edit') }}</a>
                        <form action="{{ route('roles.roles.destroy', $role->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ trans('general.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">{{ trans('roles::general.empty_state') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
