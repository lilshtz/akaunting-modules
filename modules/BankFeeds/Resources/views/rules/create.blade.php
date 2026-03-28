@extends('layouts.admin')

@section('title', trans('general.title.new', ['type' => trans('bank-feeds::general.rule')]))

@section('content')
    <x-form.container>
        <x-form id="bank-feed-rule" method="POST" route="bank-feeds.rules.store">
            @include('bank-feeds::rules._form', ['rule' => null])
        </x-form>
    </x-form.container>
@endsection
