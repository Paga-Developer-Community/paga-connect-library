# PagaConnect PHP API Library v1.0.0

## Connect Services exposed by the library

- getAccessToken
- merchantPayment
- moneyTransfer
- getUserDetails

For more information on the services listed above, visit the [Paga DEV website](https://developer-docs.paga.com/docs/php-library)

## How to use

`composer require paga/paga-connect`

 
```
require_once __DIR__ .'/vendor/autoload.php'

$pagaConnect = PagaConnectClient::builder()
                    ->setPrincipal("<Paga-Client-ID>")
                    ->setCredential("<Paga-Secret-Key>")
                    ->setRedirectUri("<Your-Redirect-URL>")
                    ->setScope(array('USER_DEPOSIT_FROM_CARD','MERCHANT_PAYMENT','USER_DETAILS_REQUEST'))
                    ->setUserData(array('FIRST_NAME','LAST_NAME','USERNAME','EMAIL'))
                    ->setIsTest(true)
                    ->build();
```

As shown above, you set the principal and credential given to you by Paga, If you pass true as the value for setIsTest(), the library will use the test url as the base for all calls. Otherwise setting it to false will use the live url value you **pass** as the base. 

### Connect Service Functions

**Merchant Payments**

This is the operation executed to make a payment to you on behalf of the customer. To make use of this function, call the getAccessToken inside the ConnectClient which will return a JSONObject with the access token which will be used to complete payment.

To get Access tokens, Use the authorization code gotten from the call to the backend :

```
$token_data = $pagaConnect->getAccessToken($authorization_code);

```
Access Token is used in making merchant payment like this:

```
$payment_data = $pagaConnect->merchantPayment( $token_data, "ref-12345", 500, 7101, "1wxew", "NGN");
```

**Money Transfer**

This operation allows you to credit a user's paga account. To make use of this function, call the moneyTransfer inside the ConnectClient which will return a JSONObject.


```
$result = $pagaConnect ->moneyTransfer( $token_data, "ref123", "2200", "08184361000", "yes");
```
**Get User Details**

This Operation allows the client to get the user's personal details. The data requested is included in the authentication and authorization request in the data parameter. Additionally, the scope parameter must contain the USER_DETAILS_REQUEST option. To make use of this function, call the getUserDetails inside the ConnectClient which will return a JSONObject with user details.


```
$result = $pagaConnect ->getUserDetails( $token_data, "ref123");
```