
<div class="form-group">
    <div class='input-group date' id="__datetimepicker__{{ $key }}">

        <input id="field-{{ $key }}"
               type="{{ $type ?: 'text' }}"
               name="{{ $name ?: (isset($baseKey) ? $baseKey : $key) }}"
               value="{{ $value }}"
               class="form-control"
               size="{{ array_get($options, 'length', array_get($options, 'size')) }}"
               maxlength="{{ array_get($options, 'length') }}"
               @if ($required && ! $translated) required="required" @endif
        >
        <span class="input-group-addon">
            <span class="glyphicon glyphicon-calendar"></span>
        </span>
    </div>
</div>


@include('cms-models::model.partials.form.field_errors', [
    'key'        => isset($baseKey) ? $baseKey : $key,
    'errors'     => $errors,
    'translated' => $translated,
])


@push('javascript-end')
    <!-- form field display strategy: datepicker datetime -->
    <script>
        $(function () {
            $('#__datetimepicker__{{ $key }}').datetimepicker({
                format: '{{ array_get($options, 'moment_format', 'YYYY-MM-DD HH:mm') }}'
            });
        });
    </script>
@endpush
