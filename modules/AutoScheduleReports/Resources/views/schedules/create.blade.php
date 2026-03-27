@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <h1 class="mb-3">{{ trans('auto-schedule-reports::general.new_schedule') }}</h1>

        <form action="{{ route('auto-schedule-reports.schedules.store') }}" method="POST">
            @include('auto-schedule-reports::schedules._form')
        </form>
    </div>
@endsection
