(function($) {
    'use strict';

    $(document).ready(function() {
        initWizard();
        initPhones();
        initAccessPersons();
    });

    // ─── Contract Wizard ───

    function initWizard() {
        var $form = $('#purplebox-contract-form');
        if (!$form.length) return;

        var currentStep = 1;

        $form.on('click', '.purplebox-next-step', function() {
            if (!validateStep(currentStep)) return;
            goToStep(currentStep + 1);
        });

        $form.on('click', '.purplebox-prev-step', function() {
            goToStep(currentStep - 1);
        });


        function goToStep(step) {
            currentStep = step;

            $('.wizard-panel').removeClass('active');
            $('.wizard-panel[data-step="' + step + '"]').addClass('active');

            $('.wizard-step').removeClass('active done');
            $('.wizard-step').each(function() {
                var s = parseInt($(this).data('step'));
                if (s < step) $(this).addClass('done');
                if (s === step) $(this).addClass('active');
            });

            if (step === 4) {
                updateReview();
            }
        }

        function validateStep(step) {
            if (step === 1) {
                if (!$('#purplebox-tenant-select').val()) {
                    alert('Please select a tenant.');
                    return false;
                }
            }
            if (step === 2) {
                var anyChecked = $('.purplebox-unit-checkbox:checked').length > 0;
                if (!anyChecked) {
                    $('#units-required-msg').show();
                    return false;
                }
                $('#units-required-msg').hide();
            }
            if (step === 3) {
                if (!$('#move_in_date').val()) {
                    alert('Please enter a move-in date.');
                    return false;
                }
            }
            return true;
        }

        function updateReview() {
            var tenantText = $('#purplebox-tenant-select option:selected').text().trim();

            var unitLabels = [];
            $('.purplebox-unit-checkbox:checked').each(function() {
                var $row = $(this).closest('tr');
                var num  = $row.find('td:nth-child(2)').text().trim();
                var size = $row.find('td:nth-child(3)').text().trim();
                var floor = $row.find('td:nth-child(4)').text().trim();
                unitLabels.push(num + ' (' + size + ', ' + floor + ')');
            });

            var moveIn      = $('#move_in_date').val();
            var firstPay    = $('#first_payment_date').val();
            var moveOut     = $('#move_out_date').val();
            var payment     = $('#payment_method option:selected').text().trim();
            var nextPay     = $('#next_payment_date').val();
            var autoRenew   = $('input[name="auto_renew"]').is(':checked');

            $('#review-tenant').text(tenantText);
            $('#review-units').html(unitLabels.length ? unitLabels.join('<br>') : '—');
            $('#review-move-in').text(moveIn || '—');
            $('#review-first-payment').text(firstPay || '—');
            $('#review-move-out').text(moveOut || 'Open-ended');
            $('#review-payment').text(payment || '—');
            $('#review-next-payment').text(nextPay || '—');
            $('#review-renew').text(autoRenew ? 'Yes' : 'No');
        }
    }

    // ─── Dynamic Phone Fields ───

    function initPhones() {
        var $list = $('#purplebox-phones-list');
        if (!$list.length) return;

        $('#purplebox-add-phone').on('click', function() {
            var $row = $('<div class="purplebox-phone-row" style="display:flex; align-items:center; gap:8px; margin-bottom:6px;"></div>');
            var $input = $('<input type="tel" name="phones[]" class="regular-text" placeholder="+971 5X XXX XXXX">');
            var $btn   = $('<button type="button" class="button purplebox-remove-phone" title="Remove">✕</button>');
            $row.append($input).append($btn);
            $list.append($row);
            $input.focus();
        });

        $list.on('click', '.purplebox-remove-phone', function() {
            $(this).closest('.purplebox-phone-row').remove();
        });
    }

    // ─── Authorized Access Persons ───

    function initAccessPersons() {
        var $list = $('#purplebox-access-list');
        if (!$list.length) return;

        $('#purplebox-add-access').on('click', function() {
            var $template = $('#purplebox-access-row-template tr').first().clone();
            $list.append($template);
            $template.find('input').first().focus();
        });

        $list.on('click', '.purplebox-remove-access', function() {
            $(this).closest('tr').remove();
        });
    }

})(jQuery);
