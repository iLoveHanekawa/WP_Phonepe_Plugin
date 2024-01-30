<?php 
namespace _29kPayments;
class PGController {
    public function __construct() {}
    public function initPayment(PaymentGateway $pg, string $userId, int $amount, string $redirectUrl) {
        $merchantTransactionId = md5(time());
        $pg->redirectToCheckout($userId, $merchantTransactionId, $amount, $redirectUrl);
    }
    public function checkPaymentStatus(PaymentGateway $pg, string $merchantId, string $merchantTransactionId) {
        return $pg->checkStatus($merchantId, $merchantTransactionId);
    }
    public function initRefund(PaymentGateway $pg, string $merchantUserId, string $transactionId, int $amount) {
        $pg->refund($merchantUserId, $transactionId, $amount);
    }
}
?>