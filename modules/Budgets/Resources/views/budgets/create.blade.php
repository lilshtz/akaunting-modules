@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <h1 class="mb-3">{{ trans('budgets::general.new_budget') }}</h1>

        <form method="POST" action="{{ route('budgets.budgets.store') }}">
            @csrf
            @include('budgets::budgets._form')

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">{{ trans('general.save') }}</button>
                <a href="{{ route('budgets.budgets.index') }}" class="btn btn-outline-secondary">{{ trans('general.cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
