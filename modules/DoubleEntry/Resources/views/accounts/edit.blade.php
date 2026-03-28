@extends('layouts.admin')

@section('title', trans('general.title.edit', ['type' => trans('double-entry::general.account')]))

@section('content')
    <x-form.container>
        <x-form id="account" method="PATCH" :route="['double-entry.accounts.update', $account->id]" :model="$account">
            @include('double-entry::accounts.partials.form')
        </x-form>
    </x-form.container>
@endsection
