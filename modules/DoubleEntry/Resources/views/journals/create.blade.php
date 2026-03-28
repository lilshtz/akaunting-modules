@extends('layouts.admin')

@section('title', trans('general.title.new', ['type' => trans('double-entry::general.journal_entry')]))

@section('content')
    <x-form.container>
        <x-form id="journal" method="POST" route="double-entry.journals.store">
            @include('double-entry::journals.partials.form')
        </x-form>
    </x-form.container>
@endsection
