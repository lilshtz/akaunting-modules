@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <h1 class="mb-3">{{ trans('roles::general.assignments') }}</h1>

        <table class="table table-striped">
            <thead>
            <tr>
                <th>{{ trans('general.name') }}</th>
                <th>Email</th>
                <th>{{ trans('roles::general.current_role') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ optional($assignments->get($user->id)?->role)->display_name ?? trans('general.na') }}</td>
                    <td class="text-end">
                        <form action="{{ route('roles.assignments.store') }}" method="POST" class="d-inline-flex gap-2">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $user->id }}">
                            <select name="role_id" class="form-control">
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" @selected((int) ($assignments->get($user->id)?->role_id ?? 0) === (int) $role->id)>{{ $role->display_name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">{{ trans('general.save') }}</button>
                        </form>

                        @if($assignments->has($user->id))
                            <form action="{{ route('roles.assignments.destroy', $user->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">{{ trans('general.delete') }}</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
