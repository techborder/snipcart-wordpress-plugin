<?php
/*
Adds validation for meta-box in admin.
*/

function snipcart_meta_box_validation_script() {
    global $post;
    if (!is_admin()) return;
    if ($post->post_type != 'snipcart_product') return;
    $nonce = wp_create_nonce('snipcart_validation');
    ?>
    <script language="javascript" type="text/javascript">
    var snipcartStrings = {};
    snipcartStrings['containsErrors'] =
        '<?php _e('The form contains errors. The product was not updated.'); ?>';
    jQuery(function($) {
        function showErrors(errors) {
            $('.snipcart-validation-error').remove();
            $('.snipcart-has-error').removeClass('snipcart-has-error');
            $('#message').remove();
            $('<div>')
                .attr('id', 'message')
                .addClass('error')
                .addClass('below-h2')
                .html('<p>' + snipcartStrings['containsErrors'] + '</p>')
                .insertAfter('.wrap h2');
            for (var key in errors) {
                var $input = $('#' + key);
                var $errors = $('<span>')
                    .addClass('snipcart-validation-error')
                    .html(errors[key].join(', '))
                    .insertAfter($input);
                $input
                    .closest('.snipcart-field')
                    .addClass('snipcart-has-error');
            }
            $('body').scrollTop(0);
        }

        $('#post').submit(function(ev, skipValidation) {
            if (skipValidation)
                return true;
            ev.preventDefault();
            var formData = JSON.stringify($('#post').serializeArray());
            var data = {
                action: 'snipcart_meta_box_validation',
                security: '<?php echo $nonce; ?>',
                form: formData
            };
            $.post(ajaxurl, data, function(errors) {
                $('.spinner').hide();
                $('#publish').removeClass('button-primary-disabled');
                if ($.isEmptyObject(errors))
                    $('#post').trigger('submit', true);
                else
                    showErrors(errors);
            });
        });
    });

    </script>
    <?php
}

function snipcart_meta_box_validation() {
    check_ajax_referer('snipcart_validation', 'security');
    $form = json_decode(str_replace('\\"', '"', $_POST['form']), true);
    header('Content-Type: application/json');
    $errors = array();

    $product_id = snipcart_get_form_value($form, 'snipcart-product-id');
    if ($product_id == NULL || trim($product_id) == '') {
        snipcart_add_error($errors, 'snipcart-product-id',
            __('This field is required', 'snipcart-plugin'));
    }

    // TODO check if id is unique

    $price = snipcart_get_form_value($form, 'snipcart-price');
    if ($price == NULL || trim($price) == '') {
        snipcart_add_error($errors, 'snipcart-price',
            __('This field is required', 'snipcart-plugin'));
    } else if (!preg_match('/^\\s*\\d+(.\\d{2})?\\s*$/', $price)) {
        snipcart_add_error($errors, 'snipcart-price',
            __('Must be a number of the form 123 or 123.45',
                'snipcart-plugin'));
    }

    $weight = snipcart_get_form_value($form, 'snipcart-weight');
    if ($weight != NULL && trim($weight) != '' &&
        !preg_match('/^\\s*\\d+(.\\d+)?\\s*$/', $weight)) {
        snipcart_add_error($errors, 'snipcart-weight',
            __('Must be a number', 'snipcart-plugin'));
    }

    if (count($errors) == 0) echo '{}';
    else echo json_encode($errors);
    die(); // or else will append '0' to response body
}

function snipcart_add_error(&$errors, $key, $error) {
    if (!array_key_exists($key, $errors))
        $errors[$key] = array();
    $errors[$key][] = $error;
}

function snipcart_get_form_value($form, $key) {
    $values = snipcart_get_form_values($form, $key);
    if (count($values) == 0) return NULL;
    return $values[0];
}

function snipcart_get_form_values($form, $key) {
    $values = array();
    foreach ($form as $elem) {
        if ($elem['name'] == $key) $values[] = $elem['value'];
    }
    return $values;
}