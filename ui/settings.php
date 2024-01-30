<div class="wrap">
    <?php 
        $phonePeMerchantId = get_option('29k_pg_phonepe_merchant_id', '');
        $phonePeSaltKey = get_option('29k_pg_phonepe_salt_key', '');
    ?>
    <h1>29kreativ Payment Gateway.</h1>
    <h3>PhonePe</h3>
    <form class="um-settings-form" method="post" action="">
        <table class='form-table'>
            <tr valign="top">
                <th scope="row">
                    <label for="29k_pg_phonepe_merchant_id">Merchant ID:</label>
                </th>
                <td>
                    <input type="text" class="regular-text" id="29k_pg_phonepe_merchant_id" name="29k_pg_phonepe_merchant_id" value="<?php echo esc_attr($phonePeMerchantId); ?>">
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    <label for="29k_pg_phonepe_salt_key">Salt Key: </label>
                </th>
                <td>
                    <input type="text" class="regular-text" id="29k_pg_phonepe_salt_key" name="29k_pg_phonepe_salt_key" value="<?php echo esc_attr($phonePeSaltKey); ?>">
                </td>
            </tr>
        </table>
        <br/>
        <a class="button-secondary" href=<?php echo site_url() . '/wp-json/_29kreativ/v1/pg/init/phonepe?user-id=1&amount=30000&redirect-url=http://localhost/29k-redevelopment/wp-json/_29kreativ/v1/pg/pay/phonepe&_wpnonce=' . wp_create_nonce('wp_rest') ?>>Test payment</a>
        <a class="button-secondary" href=<?php echo site_url() . '/wp-json/_29kreativ/v1/pg/refund/phonepe' ?>>Test refund</a>
        <button type="submit" name="29k_pg_settings_submit" class="button-primary">Save Settings</button>
    </form>
</div>