jQuery(document).ready(function($) {
    $('#connection_id').on('change', function() {
        var connectionId = $(this).val();
        var $orderTypeSelect = $('#order_type');
        var $orderTypeRow = $('#order-type-row');
        var $transactionWrapper = $('#transaction-wrapper');

        $orderTypeSelect.empty().append('<option value="">- Select order type -</option>').prop('disabled', true);
        $orderTypeRow.hide();
        $transactionWrapper.empty();

        if (connectionId) {
            $.post(ebics_api_ajax.ajax_url, {
                action: 'ebics_api_load_order_types',
                connection_id: connectionId,
                nonce: ebics_api_ajax.nonce
            }, function(response) {
                if (response.success) {
                    $.each(response.data, function(index, item) {
                        $orderTypeSelect.append($('<option>', {
                            value: item.value,
                            text: item.label
                        }));
                    });
                    $orderTypeSelect.prop('disabled', false);
                    $orderTypeRow.show();
                } else {
                    alert(ebics_api_ajax.error_loading_order_types + response.data);
                }
            });
        }
    });

    $('#order_type').on('change', function() {
        var orderType = $(this).val();
        var $transactionWrapper = $('#transaction-wrapper');

        $transactionWrapper.empty();

        if (orderType) {
            $.post(ebics_api_ajax.ajax_url, {
                action: 'ebics_api_load_transaction_fields',
                order_type: orderType,
                nonce: ebics_api_ajax.nonce
            }, function(response) {
                if (response.success) {
                    $transactionWrapper.html(response.data);
                } else {
                    alert(ebics_api_ajax.error_loading_fields + response.data);
                }
            });
        }
    });

    $('#ebics-api-transaction-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $form.find('#submit-transaction');
        var $spinner = $form.find('.spinner');
        var $resultWrapper = $('#result-message-wrapper');
        
        $submitButton.prop('disabled', true);
        $spinner.addClass('is-active');
        $resultWrapper.empty();

        var formData = new FormData(this);
        formData.append('action', 'ebics_api_submit_transaction');
        formData.append('nonce', ebics_api_ajax.nonce);

        $.ajax({
            url: ebics_api_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $resultWrapper.html(response.data);
                } else {
                    $resultWrapper.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                }
            },
            error: function() {
                $resultWrapper.html('<div class="notice notice-error"><p>' + ebics_api_ajax.error_generic + '</p></div>');
            },
            complete: function() {
                $submitButton.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
});
