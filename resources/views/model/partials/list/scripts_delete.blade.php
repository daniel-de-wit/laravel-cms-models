
@if ($model->allowDelete())

    <script>

        /**
         * Performs an AJAX request to check if a model may be deleted.
         *
         * @param id        id of the model
         * @param callback  to be called when ajax responds (takes 1 boolean allowed parameter)
         */
        var isDeletable = function(id, callback) {

            var url = '{{ cms_route("{$routePrefix}.deletable", [ 'IDHERE' ]) }}';

            url = url.replace('IDHERE', id);

            $.ajax(url, {
                'headers': {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
                .success(function (data) {

                    var error = null;
                    if (data.hasOwnProperty('error')) {
                        error = data.error;
                    }

                    callback(data.success, error);
                })
                .error(function (xhr, status, error) {
                    console.log('deletable check error: ' + error);
                    callback(false);
                });
        };


        $('.delete-record-action').click(function () {
            var form = $('.delete-modal-form');
            form.attr(
                'action',
                form.attr('data-url').replace('IDHERE', $(this).attr('data-id'))
            );
            $('.delete-modal-title').text(
                '{{ ucfirst(cms_trans('common.action.delete')) }} {{ $model->verbose_name }} #' +
                $(this).attr('data-id')
            );
        });

        // Check if model is deletable when opening modal
        $('#delete-record-modal').on('show.bs.modal', function (event) {

            var trigger  = $(event.relatedTarget);
            var id       = trigger.attr('data-id');
            var modal    = $(this);

            var button            = modal.find('.delete-modal-button');
            var disallowedMessage = $('#delete-record-modal-disallowed-alert');

            // Set initial state
            button.removeAttr('disabled');
            disallowedMessage.hide();
            disallowedMessage.empty();

            isDeletable(id, function(allowed, error) {

                if ( ! allowed) {
                    button.attr('disabled', 'disabled');
                }

                if (error) {
                    disallowedMessage.show('fast');
                    disallowedMessage.text(error);
                }
            });
        });

    </script>

@endif
