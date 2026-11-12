$(document).ready(function () {

   alertify.set('notifier','position', 'top-right');

   var addCustomerPhoneMode = 'new';

   function showCustomerDetailsModal(phoneMode, phone) {
        addCustomerPhoneMode = phoneMode;
        $('#existingCustomerPhoneGroup').toggleClass('d-none', phoneMode !== 'existing');
        $('#newCustomerPhoneGroup').toggleClass('d-none', phoneMode !== 'new');
        $('#c_phone').prop('required', phoneMode === 'new');

        if (phoneMode === 'existing') {
            $('#customerPhoneDisplay').text(phone);
            $('#c_phone').val('');
        } else {
            $('#customerPhoneDisplay').text('');
            $('#c_phone').val('');
        }

        $('#addCustomerModal').modal('show');
   }

   function continueOrderWithoutCustomer(paymentMode, phone) {
        var $proceedButton = $('.proceedToPlace').prop('disabled', true);
        $.ajax({
            type: 'POST',
            url: 'orders-code.php',
            data: {
                'continueAsGuestBtn': true,
                'payment_mode': paymentMode,
                'cphone': $.trim(phone || '')
            },
            success: function (response) {
                var res = JSON.parse(response);
                if (res.status == 200) {
                    window.location.href = 'order-summary.php';
                } else {
                    swal(res.message, res.message, res.status_type);
                }
            },
            error: function () {
                swal('Order Could Not Be Prepared', 'Please try again.', 'error');
            },
            complete: function () {
                $proceedButton.prop('disabled', false);
            }
        });
   }

   function updateCustomerPhoneRequirement() {
        var isOnlinePayment = $('#payment_mode').val() === 'Online Payment';
        $('#cphone')
            .prop('required', isOnlinePayment)
            .attr('aria-required', isOnlinePayment ? 'true' : 'false');
        $('#cphoneRequirement')
            .text(isOnlinePayment ? '(required)' : '(optional)')
            .toggleClass('text-danger', isOnlinePayment)
            .toggleClass('text-muted', !isOnlinePayment);
   }

   $(document).on('change', '#payment_mode', updateCustomerPhoneRequirement);
   updateCustomerPhoneRequirement();

   $(document).on('click', '.increment', function () {

    var $quantityInput = $(this).closest('.qtyBox').find('.qty');
    var prodId = $(this).closest('.qtyBox').find('.prodId').val();
    var currentValue = parseInt ($quantityInput .val());

    if(!isNaN (currentValue)){
        var qtyVal = currentValue + 1;
        $quantityInput.val(qtyVal);
        quantityIncDec(prodId, qtyVal)
    }
   });

    $(document).on('click', '.decrement', function () {

    var $quantityInput = $(this).closest('.qtyBox').find('.qty');
    var prodId = $(this).closest('.qtyBox').find('.prodId').val();
    var currentValue = parseInt($quantityInput.val());

    if(!isNaN (currentValue) && currentValue > 1){
        var qtyVal = currentValue - 1;
        $quantityInput.val(qtyVal);
        quantityIncDec(prodId, qtyVal)
    }
   });

   function quantityIncDec(prodId, qty){

      $.ajax({
        type: "POST",
        url: "orders-code.php",
        data: {
            'productIncDec': true,
            'product_id': prodId,
            'quantity': qty
        },
        success: function (response) {
            var res = JSON.parse(response);

            if(res.status == 200){
                $('#productArea').load(' #productContent');
                alertify.success(res.message);
            }else{
                $('#productArea').load(' #productContent');
                alertify.error(res.message);
            }
          }
      });
   }

     // proceed to place order button click
     $(document).on('click', '.proceedToPlace', function (e) {

        e.preventDefault();

        var cphone = $('#cphone').val();
        var payment_mode = $('#payment_mode').val();

        if (payment_mode == '') {
            swal("Select Payment Mode", "Select your payment mode", "warning");
            return false;
        }

        if ($.trim(cphone) === '') {
            if (payment_mode === 'Online Payment') {
                swal("Mobile Number Required", "Enter the customer's mobile number to place an online payment order.", "warning");
                $('#cphone').trigger('focus');
                return false;
            }

            continueOrderWithoutCustomer(payment_mode);
            return false;
        }

        // Accept common local/international phone formatting.
        if (!/^[0-9+().\s-]{7,30}$/.test($.trim(cphone))) {
            swal("Enter Phone Number", "Enter Valid Phone Number", "warning");
            return false;
        }

        var data = {
            'proceedToPlaceBtn': true,
            'cphone': cphone,
            'payment_mode': payment_mode
        };

        $.ajax({
            type: "POST",
            url: "orders-code.php",
            data: data,
            success: function (response) {
                var res = JSON.parse(response);
                if (res.status == 200) {
                    window.location.href = "order-summary.php";

                } else if (res.status == 404) {
                    swal("Customer Not Registered", "Add this number as a customer, continue without contact info, or cancel to continue without saving this contact.", res.status_type, {
                        buttons: {
                            catch: {
                                text: "Add Customer",
                                value: "save"
                            },
                            guest: {
                                text: "Continue Without Contact Info",
                                value: "guest"
                            },
                            cancel: {
                                text: "Cancel",
                                value: "cancel",
                                visible: true
                            }
                        }
                    })
                        .then((value) => {
                            if (value === 'save') {
                                showCustomerDetailsModal('existing', $.trim(cphone));
                            } else if (value === 'guest' || value === 'cancel') {
                                continueOrderWithoutCustomer(payment_mode, cphone);
                            }
                        });

                } else {
                    swal(res.message, res.message, res.status_type);
                }
            }
        });

    });


    // Add Customer to customer table
    $(document).on('submit', '#addCustomerForm', function (e) {
        e.preventDefault();

        var form = this;
        if (!form.reportValidity()) {
            return false;
        }

        var $saveButton = $('.saveCustomer');
        var data = {
            'saveCustomerBtn': true,
            'name': $.trim($('#c_name').val()),
            'phone': addCustomerPhoneMode === 'existing' ? $.trim($('#cphone').val()) : $.trim($('#c_phone').val()),
            'email': $.trim($('#c_email').val()),
            'payment_mode': $('#payment_mode').val(),
            'phone_mode': addCustomerPhoneMode
        };

        $saveButton.prop('disabled', true).text('Saving...');
        $.ajax({
            type: "POST",
            url: "orders-code.php",
            data: data,
            success: function (response) {
                var res = JSON.parse(response);
                if (res.status == 200) {
                    $('#addCustomerModal').modal('hide');
                    window.location.href = "order-summary.php";
                } else {
                    swal(res.message, res.message, res.status_type);
                }
            },
            error: function () {
                swal("Customer Could Not Be Saved", "Please check the details and try again.", "error");
            },
            complete: function () {
                $saveButton.prop('disabled', false).text('Save Customer');
            }
        });
    });
});
