@extends('layouts.admin')

@section('title', trans('general.title.new', ['type' => trans('double-entry::general.account')]))

@section('content')
    <x-form.container>
        <x-form id="account" method="POST" route="double-entry.accounts.store">
            @include('double-entry::accounts.partials.form')
        </x-form>
    </x-form.container>
@endsection
