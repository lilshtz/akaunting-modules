@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb-3">
            <h1>{{ trans('expense-claims::general.categories') }}</h1>
            <a href="{{ route('expense-claims.categories.create') }}" class="btn btn-primary">{{ trans('general.add_new') }}</a>
        </div>

        <table class="table table-striped">
            <thead>
            <tr>
                <th>{{ trans('general.name') }}</th>
                <th>{{ trans('general.enabled') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @foreach($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->enabled ? trans('general.yes') : trans('general.no') }}</td>
                    <td>
                        <a href="{{ route('expense-claims.categories.edit', $category->id) }}">{{ trans('general.edit') }}</a>
                        <form action="{{ route('expense-claims.categories.destroy', $category->id) }}" method="post" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-link p-0">{{ trans('general.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        {{ $categories->links() }}
    </div>
@endsection
