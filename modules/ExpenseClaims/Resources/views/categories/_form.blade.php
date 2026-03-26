@csrf
<div class="form-group">
    <label>{{ trans('general.name') }}</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $category->name ?? '') }}" required>
</div>
<div class="form-group">
    <label>{{ trans('general.color') }}</label>
    <input type="text" name="color" class="form-control" value="{{ old('color', $category->color ?? '') }}">
</div>
<div class="form-group">
    <label>{{ trans('general.enabled') }}</label>
    <select name="enabled" class="form-control">
        <option value="1" {{ (string) old('enabled', $category->enabled ?? 1) === '1' ? 'selected' : '' }}>{{ trans('general.yes') }}</option>
        <option value="0" {{ (string) old('enabled', $category->enabled ?? 1) === '0' ? 'selected' : '' }}>{{ trans('general.no') }}</option>
    </select>
</div>
<button type="submit" class="btn btn-primary">{{ trans('general.save') }}</button>
