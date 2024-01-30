<?php 
namespace _29kPayments;
require_once __DIR__ . '/PaymentGateway.php';
require_once __DIR__ . '/TransactionObject.php';
require_once __DIR__ . '/RefundObject.php';
class PhonePeTransactionObject extends TransactionObject {
    /** 
        *@var string 
    */
    private $merchantId;
    /** 
        *@var string 
    */
    private $merchantTransactionId;
    /** 
        *@var int 
    */
    private $amount;
    /** 
        *@var string 
    */
    private $merchantUserId;
        /** 
        *@var string 
    */
    private $redirectUrl;
    /** 
        *@var string 
    */
    private $redirectMode = 'POST';
    /** 
        *@var string 
    */
    private $callbackUrl;
    /** 
        *@var string 
    */
    private $paymentInstrumentType = 'PAY_PAGE';
        /** 
        *@var string 
    */
    private $saltKey;
    private $saltIndex = 1;
    public function __construct(string $merchantUserId, string $merchantTransactionId, int $amount, string $redirectUrl) {
        parent::__construct();
        // what the hell is this callback url doing
        $this->callbackUrl = '#';
        $this->redirectUrl = $redirectUrl;
        $this->merchantId = get_option('29k_pg_phonepe_merchant_id', '');
        $this->amount = $amount;
        // $this->merchantTransactionId = $merchantTransactionId;
        $this->merchantTransactionId = '1';
        $this->merchantUserId = $merchantUserId;
        $this->saltKey = get_option('29k_pg_phonepe_salt_key', '');
    }
    public function getRequestBody() {
        $request = base64_encode(json_encode([
            'merchantId' => $this->merchantId,
            'merchantTransactionId' => $this->merchantTransactionId,
            'amount' => $this->amount,
            'merchantUserId' => $this->merchantUserId,
            'redirectMode' => $this->redirectMode,
            'redirectUrl' => $this->redirectUrl,
            'callbackUrl' => $this->callbackUrl,
            'paymentInstrument' => [
                'type' => $this->paymentInstrumentType
            ]
        ]));
        error_log(json_encode(['request' => $request]));
        return $request;
    }
    public function getXVerifyHeader(): string {
        $requestBody = $this->getRequestBody();
        $headerString = hash('sha256', $requestBody . "/pg/v1/pay" . $this->saltKey) . '###' . $this->saltIndex;
        error_log(json_encode(['request' => $headerString]));
        return $headerString;
    }
}
class PhonePayRefundObject extends RefundObject {
    /** 
        *@var string 
    */
    private $merchantId;
    /**
        *@var string  
    */
    private $merchantUserId;
    /** 
        *@var string 
    */
    private $merchantTransactionId;
    /** 
        *@var string 
    */
    private $originalTransactionId;
    /** 
        *@var int 
    */
    private $amount;
    /** 
        *@var string 
    */
    private $callbackUrl;
    /** 
        *@var string 
    */
    private $saltKey;
    private $saltIndex = 1;
    public function __construct(string $merchantUserId, string $originalTransactionId, int $amount) {
        $this->merchantTransactionId = md5(time());
        $this->originalTransactionId = $originalTransactionId;
        $this->amount = $amount;
        $this->callbackUrl = site_url() . '/wp-json/_29kreativ/pg/refundcb/phonepe';
        $this->merchantId = get_option('29k_pg_phonepe_merchant_id', '');
        $this->merchantUserId = $merchantUserId;
        $this->saltKey = get_option('29k_pg_phonepe_salt_key', '');
    }
    public function getRequestBody() {
        $payload = base64_encode(json_encode([
            'merchantId' => $this->merchantId,
            'merchantTransactionId' => $this->merchantTransactionId,
            'amount' => $this->amount,
            'merchantUserId' => $this->merchantUserId,
            'originalTransactionId' => $this->originalTransactionId,
            'callbackUrl' => $this->callbackUrl,
        ]));
        error_log('base64_payload: ' . $payload);
        return $payload;
    }
    public function getXVerifyHeader(): string {
        $requestBody = $this->getRequestBody();
        $headerString = hash('sha256', $requestBody . "/pg/v1/refund" . $this->saltKey) . '###' . $this->saltIndex;
        error_log('header: ' . $headerString);
        return $headerString;
    }
}

class PhonePe extends PaymentGateway {
    /** 
        *@var string 
    */
    private $checkoutServiceUrl = 'https://api-preprod.phonepe.com/apis/pg-sandbox';
    private $checkoutEndpoint = '/pg/v1/pay';
    private $statusUrl = '/pg/v1/status';
    private $refundUrl = '/pg/v1/refund';
    public function __construct() {
        parent::__construct();
    }
    public function redirectToCheckout(string $merchantUserId, string $merchantTransactionId, int $amount, string $redirectUrl) {
        $pto = new PhonePeTransactionObject($merchantUserId, $merchantTransactionId, $amount, $redirectUrl);
        $options = [
            'body' => json_encode(['request' => $pto->getRequestBody()]),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-VERIFY' => $pto->getXVerifyHeader(),
            ),
        ];
        $response = wp_remote_post($this->checkoutServiceUrl . $this->checkoutEndpoint, $options);
        $resBody = json_decode(wp_remote_retrieve_body($response));
        $redirectUrl = $resBody->data->instrumentResponse->redirectInfo->url;
        wp_redirect($redirectUrl);
        exit();
    }
    public function checkStatus(string $merchantId, string $merchantTransactionId) {
        $getXVerifyheader = function () use($merchantId, $merchantTransactionId): string {
            $saltIndex = 1;
            $xvh = hash('sha256', '/pg/v1/status/' . $merchantId . '/' . $merchantTransactionId . get_option('29k_pg_phonepe_salt_key', '')) . '###' . $saltIndex;
            return $xvh;
        };
        $getQueryString = function () use($merchantId, $merchantTransactionId): string {
            $requestUrl = $this->checkoutServiceUrl . $this->statusUrl;
            $qs = $requestUrl . '/' . $merchantId . '/' . $merchantTransactionId;
            return $qs;
        };
        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
                'accept' => 'application/json',
                'X-VERIFY' => $getXVerifyheader(),
                'X-MERCHANT-ID' => $merchantId
            ]
        ];
        $response = wp_remote_get($getQueryString(), $options);
        wp_send_json(json_decode(wp_remote_retrieve_body($response)));
    }
    public function refund(string $merchantUserId, string $transactionId, int $amount) {
        $refundObject = new PhonePayRefundObject($merchantUserId, $transactionId, $amount);
        $options = [
            'body' => json_encode(['request' => $refundObject->getRequestBody()]),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-VERIFY' => $refundObject->getXVerifyHeader(),
            ),
        ];
        $response = wp_remote_post($this->checkoutServiceUrl . $this->refundUrl, $options);
        $resBody = json_decode(wp_remote_retrieve_body($response))->data;
        $response = wp_remote_post(site_url() . '/wp-json/_29kreativ/v1/pg/pay/phonepe', [
            'body' => [
                'merchantId' => $resBody->merchantId,
                'transactionId' => $resBody->merchantTransactionId
            ]
        ]);
        $resBody = wp_remote_retrieve_body($response);
        wp_send_json(json_decode($resBody));
    }
}
?>