<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>
        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Nunito', sans-serif;
                font-weight: 200;
                height: 100vh;
            }

        </style>
           <link href="{{ asset('css/square.css') }}" rel="stylesheet">
         <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>

    </head>
    <body>
            <div id="form-container" class="sq-payment-form">
                <div class="sq-field">
                    <label class="sq-label">Card Number</label>
                    <div id="sq-card-number"></div>
                </div>
                <div class="sq-field-wrapper">
                    <div class="sq-field sq-field--in-wrapper">
                        <label class="sq-label">CVV</label>
                        <div id="sq-cvv"></div>
                    </div>
                    <div class="sq-field sq-field--in-wrapper">
                        <label class="sq-label">Expiration</label>
                        <div id="sq-expiration-date"></div>
                    </div>
                    <div class="sq-field sq-field--in-wrapper">
                        <label class="sq-label">Postal Code</label>
                        <div id="sq-postal-code"></div>
                    </div>
                </div>
                <button id="sq-creditcard" class="button-credit-card" onclick="onGetCardNonce(event)">Pay 100</button>
                <div id="success">successfully paid</div>
            </div>

    </body>
     <!-- link to the SqPaymentForm library -->
     <script type="text/javascript" src="https://js.squareupsandbox.com/v2/paymentform">
     </script>
     <script>
    $('#success').hide();
     const paymentForm = new SqPaymentForm({
             // Initialize the payment form elements

             //TODO: Replace with your sandbox application ID
             applicationId: "sandbox-sq0idb-uRPaRwn-RV3_rM7EUaHBSg",
             inputClass: 'sq-input',
             autoBuild: false,
             // Customize the CSS for SqPaymentForm iframe elements
             inputStyles: [{
                 fontSize: '16px',
                 lineHeight: '24px',
                 padding: '16px',
                 placeholderColor: '#a0a0a0',
                 backgroundColor: 'transparent',
             }],
             // Initialize the credit card placeholders
             cardNumber: {
                 elementId: 'sq-card-number',
                 placeholder: 'Card Number'
             },
             cvv: {
                 elementId: 'sq-cvv',
                 placeholder: 'CVV'
             },
             expirationDate: {
                 elementId: 'sq-expiration-date',
                 placeholder: 'MM/YY'
             },
             postalCode: {
                 elementId: 'sq-postal-code',
                 placeholder: 'Postal'
             },
             // SqPaymentForm callback functions
             callbacks: {
                 /*
                 * callback function: cardNonceResponseReceived
                 * Triggered when: SqPaymentForm completes a card nonce request
                 */
                 cardNonceResponseReceived: function (errors, nonce, cardData) {
                 if (errors) {
                 // Log errors from nonce generation to the browser developer console.
                 console.error('Encountered errors:');
                 errors.forEach(function (error) {
                     console.error('  ' + error.message);
                 });
                 alert('Encountered errors, check browser developer console for more details');
                 return;
                 }
                 //TODO: Replace alert with code in step 2.1
                //  alert('here is your card token ' + nonce);
                $('#success').hide();
                 $.ajax({
                         headers: {
                             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                         },
                         url: "{{ route('add-card') }}",
                         type: "POST",
                         data: {nonce},
                         success: function(data){
                            $('#success').show();
                             console.log('data', data);
                         },
                         error: function (xhr, status, error) {
                             console.log('error', error)
                         }
                     });
                 }
             }
         });
         paymentForm.build();

         // onGetCardNonce is triggered when the "Pay $1.00" button is clicked
         function onGetCardNonce(event) {
             // Don't submit the form until SqPaymentForm returns with a nonce
             event.preventDefault();
             // Request a nonce from the SqPaymentForm object
             paymentForm.requestCardNonce();
         }
     </script>
</html>
