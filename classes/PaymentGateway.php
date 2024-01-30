<?php 
namespace _29kPayments;
abstract class PaymentGateway {
    protected function __construct() {}
    abstract public function redirectToCheckout(string $userId, string $merchantTransactionId, int $amount, string $redirectUrl);
    abstract public function checkStatus(string $merchantId, string $merchantTransactionId);
    abstract public function refund(string $merchantUserId, string $transactionId, int $amount);
}
?>