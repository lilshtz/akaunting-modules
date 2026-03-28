@extends('layouts.admin')

@section('title', trans('general.title.edit', ['type' => trans('bank-feeds::general.rule')]))

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">{{ trans('general.title.edit', ['type' => trans('bank-feeds::general.rule')]) }}</h1>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('bank-feeds.rules.update', $rule->id) }}" class="space-y-6">
                @csrf
                @method('PATCH')
                @include('bank-feeds::rules._form', ['rule' => $rule])

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('bank-feeds.rules.index') }}" class="inline-flex rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ trans('general.cancel') }}</a>
                    <button type="submit" class="inline-flex rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">{{ trans('general.save') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
