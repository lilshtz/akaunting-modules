@extends('layouts.admin')

@section('title', trans('general.title.edit', ['type' => trans('double-entry::general.journal_entry')]))

@section('content')
    <x-form.container>
        <x-form id="journal" method="PATCH" :route="['double-entry.journals.update', $journal->id]" :model="$journal">
            @include('double-entry::journals.partials.form')
        </x-form>
    </x-form.container>
@endsection
