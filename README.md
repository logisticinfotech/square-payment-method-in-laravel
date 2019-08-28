# square-payment-method-in-laravel
square payment method in laravel

## Front End

### Square’s Payment Form

Integrate SqPaymentForm, while integrating SqPaymentForm you can customize the look and feel of input fields.This form directly take card details from  customer on square server and  the payment form  provides method for getting none or one time card token. SqPaymentForm provide many method, you can know from [here](https://developer.squareup.com/docs/api/paymentform)

**Form Component**

Main point to note is the div element with id's.
```
    <div id="form-container" class="add-card-section">
        <div id="sq-card-number"></div>
        <div id="sq-expiration-date"></div>
        <div id="sq-cvv"></div>
        <div id="sq-postal-code"></div>
        <button id="sq-creditcard" class="button-credit-card" onclick="onGetCardNonce(event)">Pay $1.00</button>
    </div>
```

>Load Form from square script

```
    <script src=”https://js.squareup.com/v2/paymentform”></script>
```

>For sandbox testing

```
    <script src="https://js.squareupsandbox.com/v2/paymentform">
```

```
    const paymentForm = new SqPaymentForm({
        // Initialize the payment form elements

        //Replace with your application ID or sandbox application Id for testing
        applicationId: "xxxxxxxxxx",
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
                alert('here is your card token ' + nonce);
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
```

#Back End

here we get nonce(card-token), now furthur process we have to use Square Connect PHP SDK.
For more information, you can visit [here](https://github.com/square/connect-php-sdk)
The PHP SDK is available on Packagist. To add it to Composer, simply run:

```
    composer require square/connect
```

>now for configure square you required

```
    $accessToken = 'xxxxx'; // from square dashboard
    $locationId = 'xxxxx'; // from square dashboard
    $defaultApiConfig = new \SquareConnect\Configuration();

    //$defaultApiConfig->setHost("https://connect.squareupsandbox.com"); // for testing in sandbox
    $defaultApiConfig->setHost("https://connect.squareup.com");


    $defaultApiConfig->setAccessToken($accessToken);
    $defaultApiClient = new \SquareConnect\ApiClient($defaultApiConfig); //need to pass in all api
```

**add customer with card at Square server**

```
        $name = 'xxxxx';
        $email = 'xxx@gmail.com';
        $streetAddress ='xxxx';

         $customerAddress = new \SquareConnect\Model\Address();
        $customerAddress->setAddressLine1($streetAddress);

        $customer = new \SquareConnect\Model\CreateCustomerRequest();
        $customer->setGivenName($name);
        $customer->setEmailAddress($email);
        $customer->setAddress($customerAddress);


        $customersApi = new \SquareConnect\Api\CustomersApi($defaultApiClient);
        $result = $customersApi->createCustomer($customer);
        $customerId = $result->getCustomer()->getId(); //save this customerId

```
**now add card with customer**

```
    $customerId = 'xxxx';
    $body = new \SquareConnect\Model\CreateCustomerCardRequest();
    $body->setCardNonce($cardNonce); // nonce get from SqPaymentForm one time token

    $customersApi = new \SquareConnect\Api\CustomersApi($this->defaultApiClient);
    $result = $customersApi->createCustomerCard($customerId, $body);

    $card_id = $result->getCard()->getId(); // save this card_id for take payment
    $card_brand = $result->getCard()->getCardBrand();
    $card_last_four = $result->getCard()->getLast4();
    $card_exp_month = $result->getCard()->getExpMonth();
    $card_exp_year = $result->getCard()->getExpYear();
```

**charge customer**

```

    $payment_body = new \SquareConnect\Model\CreatePaymentRequest();

    $amountMoney = new \SquareConnect\Model\Money();

    # Monetary amounts are specified in the smallest unit of the applicable currency.
    # This amount is in cents. It's also hard-coded for $1.00, which isn't very useful.
    $amountMoney->setAmount(100);
    $amountMoney->setCurrency("USD");
    $payment_body->setCustomerId($customerId);
    $payment_body->setSourceId($cardId);
    $payment_body->setAmountMoney($amountMoney);
    $payment_body->setLocationId($this->locationId);

    # Every payment you process with the SDK must have a unique idempotency key.
    # If you're unsure whether a particular payment succeeded, you can reattempt
    # it with the same idempotency key without worrying about double charging
    # the buyer.
    $payment_body->setIdempotencyKey(uniqid());

    $paymentsApi = new \SquareConnect\Api\PaymentsApi($this->defaultApiClient);
    $result = $paymentsApi->createPayment($payment_body);
    $transactionId = $result->getPayment()->getId(); //save this transactionId
```

**refund customer**

```

    $body = new \SquareConnect\Model\RefundPaymentRequest();
    $amountMoney = new \SquareConnect\Model\Money();

    # Monetary amounts are specified in the smallest unit of the applicable currency.
    # This amount is in cents. It's also hard-coded for $1.00, which isn't very useful.
    $amountMoney->setAmount(100);
    $amountMoney->setCurrency("USD");

    $body->setPaymentId($paymentId);
    $body->setAmountMoney($amountMoney);
    $body->setIdempotencyKey(uniqid());
    $body->setReason('wrong order');

    $refundApi = new \SquareConnect\Api\RefundsApi($this->defaultApiClient);
    $result = $refundApi->refundPayment($body);
```

From [here](https://developer.squareup.com/apps),you can test and view sandbox dashboard