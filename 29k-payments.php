<?php 
/*
* Plugin Name: 29K Payments
* Description: Payment Gateway Plugin by 29kreativ
* Version: 1.0
* Author: 29K team
*/
require_once __DIR__ . '/classes/PaymentsPlugin.php';
require_once __DIR__ . '/controllers/PGController.php';
require_once __DIR__ . '/classes/PhonePe.php';

$plugin = _29kPayments\PaymentsPlugin::getPluginInstance();
$plugin->addPluginAdminSettings(
    plugin_basename(__FILE__),
    '29kreativ Payments',
    '29K Payments',
    '29k-pg-plugin-settings',
    [
        '29k_pg_phonepe_merchant_id',
        '29k_pg_phonepe_salt_key'
    ],
    '29k_pg_settings_submit',
    dirname(__FILE__) . '\ui\settings.php',
    'dashicons-money-alt'
);

$plugin->addRestRoute('GET', '_29kreativ/v1/pg/init', 'phonepe', function (WP_REST_Request $request) {
    $pg = new _29kPayments\PGController();
    $phonpe = new _29kPayments\PhonePe();
    $body = $request->get_query_params();
    $userId = $body['user-id'];
    $redirectUrl = $body['redirect-url'];
    // TODO secure this
    $amount = $body['amount'];
    $pg->initPayment($phonpe, $userId, $amount, $redirectUrl);

}, function () {
    if(!is_user_logged_in()) {
        wp_send_json([
            'success' => false,
            'errors' => [
                'auth' => [
                    'Failed to access route. Only logged in users are permitted to access this route.'
                ]
            ]
        ]);
        exit();
    }
    return true;
});

$plugin->addRestRoute('POST', '_29kreativ/v1/pg/pay', 'phonepe', function (WP_REST_Request $request) {
    $params = $request->get_body_params();
    $phonepe = new _29kPayments\PhonePe();
    $pg = new _29kPayments\PGController();
    $pg->checkPaymentStatus($phonepe, $params['merchantId'], $params['transactionId']);
});

$plugin->addRestRoute('GET', '_29kreativ/v1/pg/refund', 'phonepe', function (WP_REST_Request $request) {
    $pg = new _29kPayments\PGController();
    $phonpe = new _29kPayments\PhonePe();
    $pg->initRefund($phonpe, '1', '2f0b56fcad4b4386a97f95f6f354378c', 1000);
});
$plugin->addRestRoute('POST', '_29kreativ/pg/refundcb', 'phonepe', function (WP_REST_Request $request) {
    $response = $request->get_body_params();
    error_log('cb response' . json_encode($response));
});
$plugin->addRestRoute('GET', '_29kreativ/pg/refundcb', 'phonepe', function (WP_REST_Request $request) {
    wp_send_json(['hi' => 'arjun']);
});
?>