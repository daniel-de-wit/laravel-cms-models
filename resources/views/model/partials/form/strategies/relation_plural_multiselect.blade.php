
<select id="field-{{ $key }}"
       name="{{ $name ?: $key }}[]"
       class="form-control select2"
       multiple="multiple"
       @if ($required && ! $translated) required="required" @endif
>
    @foreach ($dropdownOptions as $optionValue => $optionDisplay)

        @if ( ! $required)
            <option></option>
        @endif

        <option value="{{ $optionValue }}" @if (in_array($optionValue, $value)) selected="selected" @endif>
            {{ $optionDisplay }}
        </option>

    @endforeach
</select>

@include('cms-models::model.partials.form.field_errors', [
    'key'        => isset($baseKey) ? $baseKey : $key,
    'errors'     => $errors,
    'translated' => $translated,
])
