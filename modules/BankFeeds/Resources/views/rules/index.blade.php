@extends('layouts.admin')

@section('title', trans('bank-feeds::general.rules'))

@section('new_button')
    <a href="{{ route('bank-feeds.rules.create') }}" class="btn btn-success btn-sm">
        <span class="fa fa-plus"></span> &nbsp;{{ trans('general.add_new', ['type' => trans('bank-feeds::general.rule')]) }}
    </a>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="mb-0">{{ trans('bank-feeds::general.rules') }}</h3>
            <p class="text-muted mb-0">{{ trans('bank-feeds::general.help.rules') }}</p>
        </div>

        <div class="table-responsive">
            <table class="table table-flush table-hover" id="rules-table">
                <thead class="thead-light">
                    <tr>
                        <th class="text-center" style="width: 60px;">{{ trans('bank-feeds::general.fields.priority') }}</th>
                        <th>{{ trans('bank-feeds::general.fields.field') }}</th>
                        <th>{{ trans('bank-feeds::general.fields.operator') }}</th>
                        <th>{{ trans('bank-feeds::general.fields.value') }}</th>
                        <th>{{ trans('bank-feeds::general.fields.category') }}</th>
                        <th class="text-center">{{ trans('bank-feeds::general.fields.enabled') }}</th>
                        <th class="text-center">{{ trans('general.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rules as $rule)
                        <tr data-id="{{ $rule->id }}">
                            <td class="text-center">
                                <span class="badge badge-primary">{{ $rule->priority }}</span>
                            </td>
                            <td>{{ trans('bank-feeds::general.rule_fields.' . $rule->field) }}</td>
                            <td>{{ trans('bank-feeds::general.operators.' . $rule->operator) }}</td>
                            <td><code>{{ $rule->value }}</code></td>
                            <td>
                                @if($rule->category)
                                    {{ $rule->category->name }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($rule->enabled)
                                    <span class="badge badge-success">{{ trans('general.yes') }}</span>
                                @else
                                    <span class="badge badge-secondary">{{ trans('general.no') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <a class="btn btn-neutral btn-sm" href="#" role="button" data-toggle="dropdown">
                                        <i class="fa fa-ellipsis-h"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item" href="{{ route('bank-feeds.rules.edit', $rule->id) }}">
                                            <i class="fa fa-edit"></i> {{ trans('general.edit') }}
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <form action="{{ route('bank-feeds.rules.destroy', $rule->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger" onclick="return confirm('{{ trans('general.delete_confirm') }}')">
                                                <i class="fa fa-trash"></i> {{ trans('general.delete') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">
                                <p class="my-4">{{ trans('general.no_records') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $rules->links() }}
        </div>
    </div>
@endsection
