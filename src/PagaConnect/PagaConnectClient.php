<?php
/**
 * Paga Business Library.
 *
 * PHP version >=5.5
 *
 * @category  PHP
 * @package   PagaMerchant
 * @author    PagaDevComm <devcomm@paga.com>
 * @copyright 2020 Pagatech Financials
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      https://packagist.org/packages/paga/paga-merchant
 */
namespace PagaConnect;
/**
 * PagaConnectClient  class
 * 
 * @category  PHP
 * @package   PagaConnect
 * @author    PagaDevComm <devcomm@paga.com>
 * @copyright 2020 Pagatech Financials
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      https://packagist.org/packages/paga/paga-merchant
 */
class PagaConnectClient
{

    var $test_server = "https://qa1.mypaga.com"; //"http://localhost:8080"
    var $live_server = "https://www.mypaga.com";


    // /**
    //  * @param string principal
    //  *            Business public ID from paga
    //  * @param string credential
    //  *            Business password from paga
    //  * @param string redirectUri
    //  *             Business web site page
    //  * @param boolean test
    //  *            flag to set testing or live(true for test,false for live)
    //  * @param String scope
    //  *             the operation types eg. MERCHANT_PAYMENT,USER_DETAILS_REQUEST
    //  * @param String userData
    //  *              userData eg. FIRST_NAME, LAST_NAME, USERNAME
    //  */
    /**
     * __construct
     *
     * @param mixed[] $builder Builder Object
     */
    function __construct($builder)
    {
        $this->clientId = $builder->clientId;
        $this->password = $builder->password;
        $this->redirectUri = $builder->redirectUri;
        $this->test = $builder->test;
        $this->scope = $builder->scope;
        $this->userData = $builder->userData;
    }


    /**
     * Builder function
     *
     * @return new Builder()
     */
    public static function builder()
    {
        return new Builder();
    }


    /**
     * BuildRequest function
     *
     * @param string  $url        Authorization code url
     * @param string  $credential sha512 encoding of the required parameters 
     *                            and the clientAPI key
     * @param mixed[] $data       request body data
     * 
     * @return $curl
     */
    public function buildRequest($url, $credential, $data = null)
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array("content-type: application/json", "Accept: application/json", "Authorization: $credential"),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_VERBOSE => 1,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT => 120
            )
        ); 
        if ($data != null) {
            $data_string = json_encode($data);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        }
        return $curl;
    }

    /**
     * Get Access Token 
     * 
     * @param string $authorization_code The code gotten from user's approval of Merchant's access to their Paga account
     * 
     * @property string  $redirect_uri  Where Merchant would like the user to the redirected to after receiving the access token
     * @property boolean $scope         List of activities to be performed with the access token
     * @property boolean user_data      List of user data data to be collected
     *            
     * @return JSON Object with access token inside
     */
    function getAccessToken($authorization_code)
    {
        $server = ($this->test) ? $this->test_server : $this->live_server;
        $access_token_url = "/paga-webservices/oauth2/token?";
        $credential = "Basic " . base64_encode("$this->principal:$this->credential");

        $url = $server.$access_token_url."grant_type=authorization_code&redirect_uri=".
        $this->redirectUri."&code=".$authorization_code."&scope=".$this->scope."&user_data=".$this->userData;

        $curl = $this->buildRequest($url, $credential);
 
        $response = curl_exec($curl);

        $this->checkCURL($curl);

        return $response;
    }


    /**
     * Merchant Payment
     * 
     * @param string $access_token     User's access token
     * @param string $reference_number A unique reference number provided by the client to uniquely identify the transaction 
     * @param string $amount           Amount to charge the user        
     * @param string $user_id          A unique identifier for merchant's customer          
     * @param string $product_code     Optional identifier for the product to be bought            
     * @param string $currency         The currency code of the transaction
     *            
     * @return JSON Object
     */
    function merchantPayment($access_token, $reference_number, $amount, $user_id, $product_code, $currency)
    {
        $server = ($this->test) ? $this->test_server : $this->live_server;
        $merchantPaymentUrl = $server."/paga-webservices/oauth2/secure/merchantPayment";
        $credential = "Bearer ". $access_token;
        $payment_link = "";

        if ($currency == null) {
            $payment_link = $merchantPaymentUrl."/referenceNumber/".$reference_number."/amount/".
            $amount."/merchantCustomerReference/".$user_id."/merchantProductCode/".$product_code;
        } elseif ($currency == null && $product_code ==null) {
            $payment_link = $merchantPaymentUrl."/referenceNumber/".$reference_number."/amount/".
            $amount."/merchantCustomerReference/".$user_id;
        } else {
            $payment_link = $merchantPaymentUrl."/referenceNumber/".$reference_number."/amount/".
            $amount."/merchantCustomerReference/".$user_id."/merchantProductCode/".
            $product_code."/currency/".$currency;
        }

        $curl = $this->buildRequest($payment_link, $credential);
        $response = curl_exec($curl);

        $this->checkCURL($curl);

        return $response;

    }


     /** 
      * Money Transfer
      * 
      * @param String  $access_token         User's access token            
      * @param string  $referenceNumber      A unique reference number provided by the client to uniquely identify the transaction           
      * @param string  $amount               Amount to charge the user         
      * @param String  $recipientPhoneNumber recipientPhoneNumber           
      * @param boolean $skipMessaging        Turn off Notification of User about payment made to their account.
      *            
      * @return JSON Object
      */
    function moneyTransfer($access_token, $referenceNumber, $amount, $recipientPhoneNumber, $skipMessaging){

        $server = ($this->test) ? $this->test_server : $this->live_server;
        $moneyTransferUrl = $server."/paga-webservices/oauth2/secure/moneyTransfer";
        $credential = "Bearer ". $access_token;
        $transfer_link = "";

        if ($skipMessaging != null) {
            $transfer_link = $moneyTransferUrl."/amount/".$amount."/destinationPhoneNumber/".$recipientPhoneNumber."/skipMessaging/".$skipMessaging."/referenceNumber/".$referenceNumber;
        } else {
            $transfer_link = $moneyTransferUrl."/amount/".$amount."/destinationPhoneNumber/".$recipientPhoneNumber."/referenceNumber/".$referenceNumber;
        }
        
        $curl = $this->buildRequest($transfer_link, $credential);
        $response = curl_exec($curl);

        $this->checkCURL($curl);

        return $response;

    }

    /**
     * Get One Time Token 
     * 
     * @param String $access_token     User's access token          
     * @param string $reference_number A unique reference number provided by the client to uniquely identify the transaction
     *            
     * @return JSON Object
     */
    function getOneTimeToken($access_token, $reference_number)
    {

        $server = ($this->test) ? $this->test_server : $this->live_server;
        $oneTimeTokenUrl = $server."/paga-webservices/oauth2/secure/getOneTimeToken";
        $credential = "Bearer ". $access_token;
        $oneTimeToken_link = $oneTimeTokenUrl."/referenceNumber/".$reference_number;

        $curl = $this->buildRequest($oneTimeToken_link, $credential);
        $response = curl_exec($curl);

        $this->checkCURL($curl);

        return $response;

    }

    /**
     * Get User Details
     * 
     * @param string $access_token     User's access token.           
     * @param string $reference_number A unique reference number provided by the client to uniquely identify the transaction
     *           
     * @return string JSON Object
     */
    function getUserDetails($access_token,$reference_number)
    {

        $server = ($this->test) ? $this->test_server : $this->live_server;
        $getUserDetailUrl = $server."/paga-webservices/oauth2/secure/getUserDetails";
        $userDetail_link = $getUserDetailUrl."/referenceNumber/".$reference_number;
        $credential = "Bearer ". $access_token;

        $curl = $this->buildRequest($userDetail_link, $credential);
        $response = curl_exec($curl);

        $this->checkCURL($curl);

        return $response;

    }

    /**
     * Cherck  CURL
     * 
     * @param string $curl CURL
     * 
     * @return void
     */
    public function checkCURL($curl)
    {
        if (curl_errno($curl)) {
            echo 'Curl error: ' . curl_error($curl);
        }

        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        printf("<br/>HTTP Code: " . $httpcode);

        if ($httpcode == 200) {
            printf("SUCCESSFUL");
        }
        //var_dump($response);

        curl_close($curl);
    }

}

/**
 * Builder Class
 * 
 * @category  PHP
 * @package   PagaMerchant
 * @author    PagaDevComm <devcomm@paga.com>
 * @copyright 2020 Pagatech Financials
 * @license   http://www.php.net/license/3_01.txt  PHP License 3.01
 * @link      https://packagist.org/packages/paga/paga-merchant
 */
class Builder
{
     /**
      * __construct
      */
    function __construct()
    {
        
    }

     /**
      * Set Principal function
      *
      * @param string $clientId Merchant public ID from paga
      * 
      * @return $this
      */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
        return $this;
    }

     /**
      * Set Credential function
      *
      * @param string $password Merchant password from paga
      * 
      * @return $this
      */
    public function setSecret($password)
    {
        $this->credential =$password;
        return $this;
    }

    /**
     * Set Redirect Url
     *
     * @param string $redirect Redirect Url
     * 
     * @return $this
     */
    public function setRedirectUri($redirect)
    {
        $this->redirectUri = $redirect;
        return $this;
    }

    /**
     * Set Scope
     *
     * @param mixed[] $scopeArr Scope Array
     * 
     * @return $this
     */
    public function setScope($scopeArr)
    {
        $this->scope = implode("+", $scopeArr);
        return $this;
    }

    /**
     * Set User Data
     *
     * @param mixed[] $userDataArr User Data
     * 
     * @return $this
     */
    public function setUserData($userDataArr)
    {
        $this->userData = implode("+", $userDataArr);
        return $this;
    }

    /**
     * Set Test function
     *
     * @param string $flag test to set testing or live(true for test,false for live)
     * 
     * @return $this
     */
    public function setTest($flag)
    {
        $this->test = $flag;
        return $this;
    }

    /**
     * Build function
     *
     * @return $this
     */
    public function build()
    {
        return new PagaConnectClient($this);
    }
}
 
?>


