<?php


class PagaConnectClient {

    var $test_server = "https://qa1.mypaga.com"; //"http://localhost:8080"
    var $live_server = "https://www.mypaga.com";


    /**
     * @param string principal
     *            Business public ID from paga
     * @param string credential
     *            Business password from paga
     * @param string redirectUri
     *             Business web site page
     * @param boolean test
     *            flag to set testing or live(true for test,false for live)
     * @param String scope
     *             the operation types eg. MERCHANT_PAYMENT,USER_DETAILS_REQUEST
     * @param String userData
     *              userData eg. FIRST_NAME, LAST_NAME, USERNAME
     */
    function __construct($builder) {
        $this->principal = $builder->principal;
        $this->credential = $builder->credential;
        $this->redirectUri = $builder->redirectUri;
        $this->test = $builder->test;
        $this->scope = $builder->scope;
        $this->userData = $builder->userData;
    }

   var  $access_token = "";

    public static function builder(){
        return new Builder();
    }


    /**
     * @param string url
     *            Authorization code url 
     * @param string credntial_prefix 
     *            The value could be either Authorization or Bearer  
     *  @param string credntial 
     *            64 byte encoding of both public ID and password with ':' in between them
     */
    public function buildRequest($url, $credential, $data = null) {

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
        )); 
        if ($data != null) {
            $data_string = json_encode($data);
            curl_setopt($curl, CURLOPT_POSTFIELDS,$data_string);
        }
        return $curl;
    }

    /**
     * @param string authorization_code
     *            The code gotten from user's approval of Merchant's access to their Paga account
     * @param string redirect_uri
     *            Where Merchant would like the user to the redirected to after receiving the access token
     * @param boolean scope
     *            List of activities to be performed with the access token
     * @param boolean user_data
     *            List of user data data to be collected
     * @return JSON Object with access token inside
     *
     */
    function getAccessToken($authorization_code){
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
     * @param string reference_number
     *            A unique reference number provided by the client to uniquely identify the transaction
     * @param string amount
     *            Amount to charge the user
     * @param string user_id
     *            A unique identifier for merchant's customer 
     * @param string product_code
     *            Optional identifier for the product to be bought 
     * @param string currency
     *            The currency code of the transaction
     * @return JSON Object
     *
     */
    function merchantPayment($reference_number, $amount, $user_id, $product_code, $currency){
        
        $server = ($this->test) ? $this->test_server : $this->live_server;
        $merchantPaymentUrl = $server."/paga-webservices/oauth2/secure/merchantPayment";
        $credential = "Bearer ". $this->access_token;
        $payment_link = "";

        if($currency == null){
            $payment_link = $merchantPaymentUrl."/referenceNumber/".$reference_number."/amount/".
            $amount."/merchantCustomerReference/".$user_id."/merchantProductCode/".$product_code;
        }
        elseif ($currency == null && $product_code ==null){
            $payment_link = $merchantPaymentUrl."/referenceNumber/".$reference_number."/amount/".
            $amount."/merchantCustomerReference/".$user_id;
        }
        else{
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
    * @param string reference_number
     *            A unique reference number provided by the client to uniquely identify the transaction
     * @param string amount
     *            Amount to charge the user
     * @param String recipientPhoneNumber
      *            recipientPhoneNumber
     * @param boolean skipMessaging 
     *            Turn off Notification of User about payment made to their account.
     * @return JSON Object
     *
     */
    function moneyTransfer($referenceNumber, $amount, $recipientPhoneNumber, $skipMessaging){

        $server = ($this->test) ? $this->test_server : $this->live_server;
        $moneyTransferUrl = $server."/paga-webservices/oauth2/secure/moneyTransfer";
        $credential = "Bearer ". $this->access_token;
        $transfer_link = "";

        if($skipMessaging != null){
            $transfer_link = $moneyTransferUrl."/amount/".$amount."/destinationPhoneNumber/".$recipientPhoneNumber."/skipMessaging/".$skipMessaging."/referenceNumber/".$referenceNumber;
        }
        else {
            $transfer_link = $moneyTransferUrl."/amount/".$amount."/destinationPhoneNumber/".$recipientPhoneNumber."/referenceNumber/".$referenceNumber;
        }
        
        $curl = $this->buildRequest($transfer_link, $credential);
        $response = curl_exec($curl);

        $this->checkCURL($curl);

        return $response;

    }

    /**
     * @param string reference_number
     *            A unique reference number provided by the client to uniquely identify the transaction
     * @param string amount
     *            Amount to charge the user
     * @return JSON Object
     *
     */
    function validateMoneyTransfer($reference_number, $amount, $recipientPhoneNumber){

        $server = ($this->test) ? $this->test_server : $this->live_server;
        $validateMoneyTransferUrl = $server."/paga-webservices/oauth2/secure/validateMoneyTransfer";
        $credential = "Bearer ". $this->access_token;

        $validateMoneyTransfer_link = $validateMoneyTransferUrl."/amount/".$amount."/destinationPhoneNumber/".$recipientPhoneNumber."/referenceNumber/".$reference_number;

        $curl = $this->buildRequest($validateMoneyTransfer_link, $credential);
        $response = curl_exec($curl);

        $this->checkCURL($curl);

        return $response;

    }

    /**
     * @param string reference_number
     *            A unique reference number provided by the client to uniquely identify the transaction
     * @return JSON Object
     *
     */
    function getOneTimeToken($reference_number){

        $server = ($this->test) ? $this->test_server : $this->live_server;
        $oneTimeTokenUrl = $server."/paga-webservices/oauth2/secure/getOneTimeToken";
        $credential = "Bearer ". $this->access_token;
        $oneTimeToken_link = $oneTimeTokenUrl."/referenceNumber/".$reference_number;

        $curl = $this->buildRequest($oneTimeToken_link, $credential);
        $response = curl_exec($curl);

        $this->checkCURL($curl);

        return $response;

    }


  /**
     * @return JSON Object
     *
     */
    function getBanksList(){

        $server = ($this->test) ? $this->test_server : $this->live_server;
        $banksUrl = $server."/paga-webservices/oauth2/secure/banksList";
        $banks_link = $banksUrl;
        $credential = "Bearer ". $this->access_token;

        $curl = $this->buildRequest($banks_link, $credential);
        $response = curl_exec($curl);

        $this->checkCURL($curl);

        return $response;

    }

    /**
     * @return JSON Object
     *
     */
    function getMobileOperatorsList(){

        $server = ($this->test) ? $this->test_server : $this->live_server;
        $mobileOperatorUrl = $server."/paga-webservices/oauth2/secure/mobileOperatorsList";
        $mobileOperator_link = $mobileOperatorUrl;
        $credential = "Bearer ". $this->access_token;

        $curl = $this->buildRequest($mobileOperator_link, $credential);
        $response = curl_exec($curl);

        $this->checkCURL($curl);

        return $response;

    }

    /**
     * @param string $reference_number
     *            A unique reference number provided by the client to uniquely identify the transaction
     * @return string JSON Object
     */
    function getUserDetails($reference_number){

        $server = ($this->test) ? $this->test_server : $this->live_server;
        $getUserDetailUrl = $server."/paga-webservices/oauth2/secure/getUserDetails";
        $userDetail_link = $getUserDetailUrl."/referenceNumber/".$reference_number;
        $credential = "Bearer ". $this->access_token;

        $curl = $this->buildRequest($userDetail_link, $credential);
        $response = curl_exec($curl);

        $this->checkCURL($curl);

        return $response;

    }

    /**
     * @param string bankPublicId
     *            public id of bank
     * @param string $bankAccountNumber
     *            Account number in the bank
     * @param string reference_number
     *            A unique reference number provided by the client to uniquely identify the transaction
     * @return JSON Object
     *
     */
    function validateBankAccountNumber($bankPublicId, $bankAccountNumber, $reference_number){

        $server = ($this->test) ? $this->test_server : $this->live_server;
        $validateBankAccountNumberUrl = $server."/paga-webservices/oauth2/secure/validateBankAccountNumber";
        $credential = "Bearer ". $this->access_token;

        $validate_link = $validateBankAccountNumberUrl."/bankPublicId/".$bankPublicId."/accountNumber/".
            $bankAccountNumber."/referenceNumber/".$reference_number;

        $curl = $this->buildRequest($validate_link, $credential);
        $response = curl_exec($curl);

        $this->checkCURL($curl);

        return $response;

    }
    /*
     * @param String $amount
     * @param String $bankPublicId
     * @param String $bankAccountNumber
     * @param String $recipientPhoneNumber
     * @param String $recipientFirstName
     * @param String $recipientLastName
     * @param String $recipientEmail
     * @param String $remarks
     * @param String $recipientMobileOperatorPublicId
     * @param String $externalReferenceNumber
     */

    function moneyTransferToBank($amount, $bankPublicId, $bankAccountNumber, $recipientPhoneNumber, $recipientFirstName, $recipientLastName,$recipientEmail,$remarks,$recipientMobileOperatorPublicId,$externalReferenceNumber){

        $server = ($this->test) ? $this->test_server : $this->live_server;
        $moneyTransferToBankUrl = $server."/paga-webservices/oauth2/secure/moneyTransferToBank";
        $credential = "Bearer ". $this->access_token;

        $moneyTransferToBank = $moneyTransferToBankUrl."/amount/".$amount."/destinationBankPublicId/".$bankPublicId."/destinationBankAccountNumber/".$bankAccountNumber."/recipientPhoneNumber/".$recipientPhoneNumber."/recipientFirstName/".$recipientFirstName."/recipientLastName/".$recipientLastName."/recipientEmail/".$recipientEmail."/remarks/".$remarks."/recipientMobileOperatorPublicId/".$recipientMobileOperatorPublicId."/externalReferenceNumber/".$externalReferenceNumber;

        $curl = $this->buildRequest($moneyTransferToBank, $credential);
        $response = curl_exec($curl);

        $this->checkCURL($curl);

        return $response;

    }

    /*
     * @param String $amount,
     * @param String $bankPublicId
     * @param String $bankAccountNumber
     * @param String $recipientPhoneNumber
     * @param String $recipientFirstName
     * @param String $recipientLastName
     * @param String $recipientEmail
     * @param String $remarks
     * @param String $recipientMobileOperatorPublicId
     * @param String $externalReferenceNumber
     */
    function validateMoneyTransferToBank($amount, $bankPublicId, $bankAccountNumber, $recipientPhoneNumber, $recipientFirstName, $recipientLastName,$recipientEmail,$remarks,$recipientMobileOperatorPublicId,$externalReferenceNumber){

        $server = ($this->test) ? $this->test_server : $this->live_server;
        $validateMoneyTransferToBankUrl = $server."/paga-webservices/oauth2/secure/validateMoneyTransferToBank";
        $credential = "Bearer ". $this->access_token;

        $validateMoneyTransferToBank = $validateMoneyTransferToBankUrl."/amount/".$amount."/destinationBankPublicId/".$bankPublicId."/destinationBankAccountNumber/".$bankAccountNumber."/recipientPhoneNumber/".$recipientPhoneNumber."/recipientFirstName/".$recipientFirstName."/recipientLastName/".$recipientLastName."/recipientEmail/".$recipientEmail."/remarks/".$remarks."/recipientMobileOperatorPublicId/".$recipientMobileOperatorPublicId."/externalReferenceNumber/".$externalReferenceNumber;

        $curl = $this->buildRequest($validateMoneyTransferToBank, $credential);
        $response = curl_exec($curl);

        $this->checkCURL($curl);

        return $response;
    }


    /**
     * @param $curl
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

class Builder {
    function __construct() {
        
    }
    public function setPrincipal($principal) {
       $this->principal = $principal;
       return $this;
    }
    public function setCredential($credential){
        $this->credential =$credential;
       return $this;
    }
    public function setRedirectUri($redirect){
        $this->redirectUri = $redirect;
        return $this;
    }
    public function setScope($scopeArr){
        $this->scope = implode("+", $scopeArr);
       return $this;
    }
    public function setUserData($userDataArr){
        $this->userData = implode("+", $userDataArr);
       return $this;
    }
    public function setTest($flag){
        $this->test = $flag;
       return $this;
    }
    public function build() {
       return new PagaConnectClient($this);
    }
 }
 


?>


