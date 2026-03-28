@extends('layouts.admin')

@section('title', trans('general.title.edit', ['type' => trans('bank-feeds::general.rule')]))

@section('content')
    <x-form.container>
        <x-form id="bank-feed-rule" method="PATCH" :route="['bank-feeds.rules.update', $rule->id]" :model="$rule">
            @include('bank-feeds::rules._form', ['rule' => $rule])
        </x-form>
    </x-form.container>
@endsection
