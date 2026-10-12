<?php
/**
 * Plugin Name: WatchStore Payments
 * Description: Custom Raast/Bank/JazzCash/EasyPaisa payment gateway for WooCommerce with WhatsApp order verification.
 * Version: 1.2.0
 */
 
if ( ! defined( 'ABSPATH' ) ) exit;

require_once plugin_dir_path( __FILE__ ) . 'footer-links-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'homepage-style-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'homepage-content-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'product-colors-meta.php';


/**
 * Add a country-code dropdown to checkout, next to the phone field,
 * defaulting to Pakistan (+92) but changeable for customers using a
 * foreign SIM/WhatsApp number while living in Pakistan.
 */
add_filter( 'woocommerce_checkout_fields', 'watchstore_add_country_code_field' );
function watchstore_add_country_code_field( $fields ) {
    $fields['billing']['billing_phone_country_code'] = array(
        'type'        => 'select',
        'label'       => 'Country Code',
        'required'    => true,
        'class'       => array( 'form-row-first', 'ws-country-code-field' ),
        'priority'    => 24,
        'options'     => array(
            '92'  => '🇵🇰 +92 (Pakistan)',
            '44'  => '🇬🇧 +44 (UK)',
            '1'   => '🇺🇸 +1 (US/Canada)',
            '971' => '🇦🇪 +971 (UAE)',
            '966' => '🇸🇦 +966 (Saudi Arabia)',
            '974' => '🇶🇦 +974 (Qatar)',
            '968' => '🇴🇲 +968 (Oman)',
            '61'  => '🇦🇺 +61 (Australia)',
        ),
        'default'     => '92',
    );
    return $fields;
}

add_action( 'woocommerce_checkout_update_order_meta', 'watchstore_save_country_code_meta' );
function watchstore_save_country_code_meta( $order_id ) {
    if ( ! empty( $_POST['billing_phone_country_code'] ) ) {
        update_post_meta( $order_id, '_billing_phone_country_code', sanitize_text_field( $_POST['billing_phone_country_code'] ) );
    }
}

add_action( 'wp_footer', 'watchstore_position_country_code_field' );
function watchstore_position_country_code_field() {
    if ( ! is_checkout() ) return;
    ?>
    <style>
    .ws-phone-row {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        margin-bottom: 1em;
        position: relative;
    }
    .ws-phone-row .form-row {
        margin-bottom: 0 !important;
    }
    .ws-country-code-field {
        flex: 0 0 100px;
        position: relative;
    }
    .ws-country-code-field label,
    #billing_phone_field label {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 4px;
        display: block;
    }
    /* Hide the real <select> visually but keep it in the DOM and functional,
       so WooCommerce/PHP still reads $_POST['billing_phone_country_code']
       exactly as before — no backend changes needed. */
    .ws-country-code-field select {
        position: absolute;
        opacity: 0;
        pointer-events: none;
        width: 1px;
        height: 1px;
    }
    .ws-cc-trigger {
        width: 100%;
        height: 46px;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        background: #fff;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 10px;
        cursor: pointer;
        box-sizing: border-box;
    }
    .ws-cc-trigger:after {
        content: "▾";
        font-size: 11px;
        color: #6b7280;
        margin-left: 4px;
    }
    .ws-cc-list {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        margin-top: 4px;
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        z-index: 50;
        min-width: 220px;
        max-height: 260px;
        overflow-y: auto;
    }
    .ws-cc-list.open { display: block; }
    .ws-cc-option {
        padding: 10px 14px;
        font-size: 14px;
        cursor: pointer;
        white-space: nowrap;
    }
    .ws-cc-option:hover { background: #f3f4f6; }
    #billing_phone_field {
        flex: 1;
    }
    #billing_phone_field input {
        width: 100%;
        height: 46px;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        padding: 0 12px;
        box-sizing: border-box;
        font-size: 14px;
    }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var codeField  = document.querySelector('#billing_phone_country_code_field');
        var phoneField = document.querySelector('#billing_phone_field');
        var realSelect = document.querySelector('#billing_phone_country_code');
        if (!codeField || !phoneField || !phoneField.parentNode || !realSelect) return;

        var row = document.createElement('div');
        row.className = 'ws-phone-row';
        phoneField.parentNode.insertBefore(row, phoneField);
        row.appendChild(codeField);
        row.appendChild(phoneField);

        // Build the compact trigger button (shows only "+92" style, closed state)
        var trigger = document.createElement('div');
        trigger.className = 'ws-cc-trigger';
        codeField.appendChild(trigger);

        // Build the dropdown list (shows full "🇵🇰 +92 (Pakistan)" style, open state)
        var list = document.createElement('div');
        list.className = 'ws-cc-list';
        codeField.appendChild(list);

        function shortLabel(fullText) {
            // Full text looks like "🇵🇰 +92 (Pakistan)" — keep just the flag + code.
            var match = fullText.match(/^(\S+\s*\+\d+)/);
            return match ? match[1] : fullText;
        }

        Array.prototype.forEach.call(realSelect.options, function (opt) {
            var item = document.createElement('div');
            item.className = 'ws-cc-option';
            item.textContent = opt.textContent;
            item.dataset.value = opt.value;
            item.addEventListener('click', function () {
                realSelect.value = opt.value;
                trigger.textContent = shortLabel(opt.textContent);
                list.classList.remove('open');
            });
            list.appendChild(item);
        });

        // Initialize trigger with the currently selected (default) option.
        var selectedOpt = realSelect.options[realSelect.selectedIndex];
        trigger.textContent = shortLabel(selectedOpt.textContent);

        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            list.classList.toggle('open');
        });
        document.addEventListener('click', function () {
            list.classList.remove('open');
        });
    });
    </script>
    <?php
}


add_filter( 'woocommerce_cod_process_payment_order_status', function( $status, $order ) {
    return 'on-hold';
}, 10, 2 );
 
add_filter( 'woocommerce_payment_gateways', 'watchstore_add_raast_gateway' );
function watchstore_add_raast_gateway( $gateways ) {
    $gateways[] = 'WC_Gateway_Raast_Transfer';
    return $gateways;
}
 
add_action( 'plugins_loaded', 'watchstore_init_raast_gateway' );
function watchstore_init_raast_gateway() {
 
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
        return;
    }
 
    class WC_Gateway_Raast_Transfer extends WC_Payment_Gateway {
 
        public function __construct() {
            $this->id                 = 'raast_transfer';
            $this->has_fields         = true;
            $this->method_title       = 'Advance Payment (1 to 3 Working Day)';
            $this->method_description = 'Accept direct bank transfer, EasyPaisa, or JazzCash with WhatsApp order verification.';
 
            $this->init_form_fields();
            $this->init_settings();
 
            $this->title            = $this->get_option( 'title', 'Advance Payment (1 to 3 Working Day)' );
            $this->description      = $this->get_option( 'description', 'Pay online or with bank.' );
            $this->enabled          = $this->get_option( 'enabled' );
            $this->bank_name        = $this->get_option( 'bank_name', 'Meezan Bank' );
            $this->account_name     = $this->get_option( 'account_name', 'WatchStore Official' );
            $this->account_number   = $this->get_option( 'account_number', '0102-0304050607' );
            $this->iban             = $this->get_option( 'iban', 'PK36MEZN0001020304050607' );
            $this->easypaisa_number = $this->get_option( 'easypaisa_number', '0300-1234567' );
            $this->easypaisa_name   = $this->get_option( 'easypaisa_name', 'WatchStore EasyPaisa' );
            $this->jazzcash_number  = $this->get_option( 'jazzcash_number', '0300-7654321' );
            $this->jazzcash_name    = $this->get_option( 'jazzcash_name', 'WatchStore JazzCash' );
            $this->raast_id         = $this->get_option( 'raast_id', '0321-5817987' );
            $this->primary_number   = $this->get_option( 'primary_number', '0321-5817987' );
            $this->render_url       = $this->get_option( 'render_url', 'https://watchstore-whatsaap.onrender.com' );
            $this->render_api_key   = $this->get_option( 'render_api_key', '' );
 
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }
 
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => 'Enable/Disable',
                    'type'    => 'checkbox',
                    'label'   => 'Enable Advance Payment',
                    'default' => 'yes',
                ),
                'title' => array(
                    'title'   => 'Title',
                    'type'    => 'text',
                    'default' => 'Advance Payment (1 to 3 Working Day)',
                ),
                'description' => array(
                    'title'   => 'Description',
                    'type'    => 'textarea',
                    'default' => 'Pay online or with bank.',
                ),
                'bank_name' => array(
                    'title'   => 'Bank Name',
                    'type'    => 'text',
                    'default' => 'Meezan Bank',
                ),
                'account_name' => array(
                    'title'   => 'Account Title / Name',
                    'type'    => 'text',
                    'default' => 'WatchStore Official',
                ),
                'account_number' => array(
                    'title'   => 'Account Number',
                    'type'    => 'text',
                    'default' => '0102-0304050607',
                ),
                'iban' => array(
                    'title'   => 'IBAN',
                    'type'    => 'text',
                    'default' => 'PK36MEZN0001020304050607',
                ),
                'easypaisa_number' => array(
                    'title'   => 'EasyPaisa Number',
                    'type'    => 'text',
                    'default' => '0300-1234567',
                ),
                'easypaisa_name' => array(
                    'title'   => 'EasyPaisa Account Name',
                    'type'    => 'text',
                    'default' => 'WatchStore EasyPaisa',
                ),
                'jazzcash_number' => array(
                    'title'   => 'JazzCash Number',
                    'type'    => 'text',
                    'default' => '0300-7654321',
                ),
                'jazzcash_name' => array(
                    'title'   => 'JazzCash Account Name',
                    'type'    => 'text',
                    'default' => 'WatchStore JazzCash',
                ),
                'primary_number' => array(
                    'title'   => 'Primary WhatsApp Number',
                    'type'    => 'text',
                    'default' => '0321-5817987',
                ),
                'raast_id' => array(
                    'title'   => 'Secondary / Fallback WhatsApp Number',
                    'type'    => 'text',
                    'default' => '0321-5817987',
                ),
                'render_url' => array(
                    'title'       => 'WhatsApp Server URL',
                    'type'        => 'text',
                    'default'     => 'https://watchstore-whatsaap.onrender.com',
                ),
                'render_api_key' => array(
                    'title'       => 'WhatsApp Server API Key',
                    'type'        => 'password',
                    'default'     => '',
                ),
            );
        }
 
        public function payment_fields() {
            // Payment details are displayed in the checkout template's
            // "Payment details" section (section 5). Nothing rendered here
            // to avoid duplicate account information.
        }
 
        public function validate_fields() {
            return true;
        }
 
        public function process_payment( $order_id ) {
            $order = wc_get_order( $order_id );
 
            $order->update_status( 'on-hold', 'Awaiting manual payment verification.' );
            wc_reduce_stock_levels( $order_id );
            WC()->cart->empty_cart();
 
            return array(
                'result'   => 'success',
                'redirect' => $this->get_return_url( $order ),
            );
        }
    }
}
 
// ---- AJAX: WhatsApp Verification handler ----
add_action( 'wp_ajax_watchstore_send_verification', 'watchstore_handle_send_verification' );
add_action( 'wp_ajax_nopriv_watchstore_send_verification', 'watchstore_handle_send_verification' );
function watchstore_handle_send_verification() {
    $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
    $order    = $order_id ? wc_get_order( $order_id ) : false;
 
    if ( ! $order ) {
        wp_send_json( array( 'success' => false, 'reason' => 'invalid_order' ) );
    }

    $attempts = (int) $order->get_meta( '_ws_verify_attempts' );
    if ( $attempts >= 4 ) {
        wp_send_json( array(
            'success'      => false,
            'reason'       => 'max_attempts_reached',
            'attemptsUsed' => $attempts,
        ) );
    }
    $order->update_meta_data( '_ws_verify_attempts', $attempts + 1 );
    $order->save();
 
    $gateways = WC()->payment_gateways()->payment_gateways();
    $gateway  = isset( $gateways['raast_transfer'] ) ? $gateways['raast_transfer'] : null;
 
    $render_url         = $gateway ? rtrim( $gateway->get_option( 'render_url' ), '/' ) : '';
    $render_api_key     = $gateway ? $gateway->get_option( 'render_api_key' ) : '';
    $primary_number     = $gateway ? $gateway->get_option( 'primary_number', '0321-5817987' ) : '0321-5817987';
    $secondary_fallback = $gateway ? $gateway->get_option( 'raast_id', '0321-5817987' ) : '0321-5817987';
 
    if ( empty( $render_url ) || empty( $render_api_key ) ) {
        wp_send_json( array(
            'success'         => false,
            'reason'          => 'not_configured',
            'fallback'        => true,
            'secondaryNumber' => $secondary_fallback,
        ) );
    }
 
    $raw_phone    = preg_replace( '/\D/', '', (string) $order->get_billing_phone() );
    $country_code = $order->get_meta( '_billing_phone_country_code' );
    if ( empty( $country_code ) ) {
        $country_code = '92'; // fallback for orders placed before this field existed
    }

    // Strip a leading 0 from the local number (customer types the local
    // number WITHOUT the leading 0, same convention WhatsApp itself uses
    // once a country code is separately selected).
    if ( strpos( $raw_phone, '0' ) === 0 ) {
        $raw_phone = substr( $raw_phone, 1 );
    }

    $phone = $country_code . $raw_phone;
    $body_payload = array(
        'phone'          => $phone,
        'orderId'        => (string) $order->get_id(),
        'amount'         => (string) $order->get_total(),
        'customerName'   => $order->get_billing_first_name(),
        'paymentMethod'  => $order->get_payment_method(),
        'orderUrl'       => $order->get_checkout_order_received_url(),
        'paymentDetails' => array(
            'bankName'        => $gateway ? $gateway->bank_name : '',
            'accountName'     => $gateway ? $gateway->account_name : '',
            'accountNumber'   => $gateway ? $gateway->account_number : '',
            'iban'            => $gateway ? $gateway->iban : '',
            'easypaisaNumber' => $gateway ? $gateway->easypaisa_number : '',
            'easypaisaName'   => $gateway ? $gateway->easypaisa_name : '',
            'jazzcashNumber'  => $gateway ? $gateway->jazzcash_number : '',
            'jazzcashName'    => $gateway ? $gateway->jazzcash_name : '',
        ),
        // Always save the pending order server-side, even if this specific phone
        // turns out not to be on WhatsApp — the customer may message from a
        // DIFFERENT WhatsApp number instead, and the bot needs to find this
        // order by ID alone in that case.
        'alwaysSave' => true,
    );

    $response = wp_remote_post( $render_url . '/api/send-verification', array(
        'timeout' => 65,
        'headers' => array(
            'Content-Type' => 'application/json',
            'X-API-Key'    => $render_api_key,
        ),
        'body' => wp_json_encode( $body_payload ),
    ) );
 
    if ( is_wp_error( $response ) ) {
        wp_send_json( array(
            'success'         => false,
            'reason'          => 'request_failed',
            'fallback'        => true,
            'secondaryNumber' => $secondary_fallback,
        ) );
    }
 
    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( ! is_array( $body ) ) {
        $body = array( 'success' => false, 'reason' => 'bad_response', 'fallback' => true, 'secondaryNumber' => $secondary_fallback );
    }
 
    if ( is_array( $body ) ) {
        $body['primaryNumber']  = $primary_number;
        $body['attemptsUsed']   = $attempts + 1;
    }
    wp_send_json( $body );
}
 
// ---- AJAX: poll order status (WooCommerce status + Baileys backend status) ----
add_action( 'wp_ajax_watchstore_check_order_status', 'watchstore_handle_check_order_status' );
add_action( 'wp_ajax_nopriv_watchstore_check_order_status', 'watchstore_handle_check_order_status' );
function watchstore_handle_check_order_status() {
    $order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
    $order    = $order_id ? wc_get_order( $order_id ) : false;

    if ( ! $order ) {
        wp_send_json( array( 'success' => false ) );
    }

    $wc_status = $order->get_status(); // e.g. 'on-hold', 'processing'

    $gateways   = WC()->payment_gateways()->payment_gateways();
    $gateway    = isset( $gateways['raast_transfer'] ) ? $gateways['raast_transfer'] : null;
    $render_url = $gateway ? rtrim( $gateway->get_option( 'render_url' ), '/' ) : '';
    $api_key    = $gateway ? $gateway->get_option( 'render_api_key' ) : '';

    $baileys_status = 'unknown';
    if ( $render_url && $api_key ) {
        $resp = wp_remote_get( $render_url . '/api/order-status?orderId=' . rawurlencode( (string) $order_id ), array(
            'timeout' => 10,
            'headers' => array( 'X-API-Key' => $api_key ),
        ) );
        if ( ! is_wp_error( $resp ) ) {
            $data = json_decode( wp_remote_retrieve_body( $resp ), true );
            if ( is_array( $data ) && isset( $data['status'] ) ) {
                $baileys_status = $data['status'];
            }
        }
    }

    // COD orders need no manual payment check — once the customer has replied
    // YES on WhatsApp (baileysStatus 'confirmed'), auto-advance straight to
    // Processing so the store owner doesn't need a separate manual step.
    if (
        $order->get_payment_method() === 'cod'
        && $baileys_status === 'confirmed'
        && ! in_array( $wc_status, array( 'processing', 'completed' ), true )
    ) {
        $order->update_status( 'processing', 'Auto-confirmed via WhatsApp YES reply (COD).' );
        $wc_status = 'processing';
    }

    wp_send_json( array(
        'success'       => true,
        'wcStatus'      => $wc_status,
        'baileysStatus' => $baileys_status,
        'attemptsUsed'  => (int) $order->get_meta( '_ws_verify_attempts' ),
    ) );
}

// ---- Thank-you page: popup UI ----
add_action( 'woocommerce_thankyou', 'watchstore_render_verification_popup', 5 );
function watchstore_render_verification_popup( $order_id ) {
    if ( ! $order_id ) return;
    $order = wc_get_order( $order_id );
    $popup_already_shown = $order->get_meta( '_ws_popup_shown' );
    if ( ! $popup_already_shown ) {
        $order->update_meta_data( '_ws_popup_shown', 1 );
        $order->save();
    }
    $allowed_methods = array( 'raast_transfer', 'cod' );
    if ( ! $order || ! in_array( $order->get_payment_method(), $allowed_methods, true ) ) return;
    $customer_phone = $order->get_billing_phone();

    $gateways       = WC()->payment_gateways()->payment_gateways();
    $gateway        = isset( $gateways['raast_transfer'] ) ? $gateways['raast_transfer'] : null;
    $primary_number = $gateway ? $gateway->get_option( 'primary_number', '0321-5817987' ) : '0321-5817987';
    ?>
    <?php if ( ! $popup_already_shown ) : ?>
    <div id="ws-verify-overlay" class="ws-verify-overlay">
        <div class="ws-verify-box">
            <!-- Loading -->
            <div id="ws-verify-loading">
                <div class="ws-spinner"></div>
                <p>Verifying your order...</p>
            </div>

            <!-- 1. Customer has WhatsApp (Automated message sent) -->
            <div id="ws-verify-sent" style="display:none;">
                <div class="ws-check-circle">&#10003;</div>
                <h3 class="ws-verify-title">Check WhatsApp</h3>
                <p class="ws-verify-text">We've sent your order confirmation to <strong><?php echo esc_html( $customer_phone ); ?></strong>.</p>
                <p class="ws-verify-subtext">Please open WhatsApp on this number and confirm your order.</p>
                <button type="button" class="ws-verify-close">Close</button>
            </div>

            <!-- 2. Customer not on WhatsApp (Use Primary Business Number) -->
            <div id="ws-verify-not-on-wa" style="display:none;">
                <h3 class="ws-verify-title">Please send the screenshot to this number</h3>
                <p class="ws-verify-subtext">Send your payment confirmation to complete your order:</p>
                <a href="#" id="ws-primary-wa-link" target="_blank" class="ws-green-wa-btn">
                    <svg class="ws-wa-icon" viewBox="0 0 448 512" width="20" height="20" fill="currentColor">
                        <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
                    </svg>
                    <span id="ws-primary-wa-num-display"><?php echo esc_html( $primary_number ); ?></span>
                </a>
                <button type="button" class="ws-verify-close">Close</button>
            </div>

            <!-- 3. Primary Backend Down / Banned (Failover to Secondary Business Number) -->
            <div id="ws-verify-fallback" style="display:none;">
                <h3 class="ws-verify-title">Confirm Payment on WhatsApp</h3>
                <p class="ws-verify-subtext">Tap below to send your payment screenshot on WhatsApp:</p>
                <a href="#" id="ws-verify-wa-link" target="_blank" class="ws-green-wa-btn">
                    <svg class="ws-wa-icon" viewBox="0 0 448 512" width="20" height="20" fill="currentColor">
                        <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
                    </svg>
                    <span id="ws-fallback-wa-num-display">Confirm on WhatsApp</span>
                </a>
                <button type="button" class="ws-verify-close">Close</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <style>
        .ws-verify-overlay {
            position: fixed; inset: 0; z-index: 99999;
            background: rgba(255,255,255,0.6);
            backdrop-filter: blur(6px);
            display: flex; align-items: center; justify-content: center;
        }
        .ws-verify-box {
            background: #fff; border-radius: 12px; padding: 32px;
            max-width: 360px; width: 90%; text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }
        .ws-spinner {
            width: 36px; height: 36px; margin: 0 auto 16px;
            border: 4px solid #eee; border-top-color: #333;
            border-radius: 50%; animation: ws-spin 0.8s linear infinite;
        }
        @keyframes ws-spin { to { transform: rotate(360deg); } }
        .ws-check-circle {
            width: 56px; height: 56px; margin: 0 auto 16px;
            border-radius: 50%; background: #2ecc71; color: #fff;
            font-size: 28px; line-height: 56px;
        }
        .ws-verify-title {
            font-size: 19px; font-weight: 700; color: #111827; margin: 0 0 12px 0; line-height: 1.35;
        }
        .ws-verify-text {
            font-size: 15px; color: #374151; margin: 0 0 8px 0; line-height: 1.4;
        }
        .ws-verify-subtext {
            font-size: 14px; color: #6b7280; margin: 0 0 14px 0; line-height: 1.4;
        }
        .ws-green-wa-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background-color: #25D366;
            color: #ffffff !important;
            font-size: 15px;
            font-weight: 700;
            padding: 12px 22px;
            border-radius: 8px;
            text-decoration: none !important;
            margin-top: 8px;
            margin-bottom: 8px;
            transition: background-color 0.2s ease, transform 0.1s ease;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
            width: 100%;
            box-sizing: border-box;
        }
        .ws-green-wa-btn:hover {
            background-color: #1da851;
            transform: translateY(-1px);
        }
        .ws-wa-icon {
            flex-shrink: 0;
        }
        .ws-verify-close {
            margin-top: 14px; padding: 8px 20px; border: none;
            border-radius: 6px; background: #222; color: #fff; cursor: pointer;
        }
    </style>

    <?php if ( ! $popup_already_shown ) : ?>
    <script>
    (function () {
    const overlay        = document.getElementById('ws-verify-overlay');
    const loadingEl      = document.getElementById('ws-verify-loading');
    const sentEl         = document.getElementById('ws-verify-sent');
    const notOnWaEl      = document.getElementById('ws-verify-not-on-wa');
    const fallbackEl     = document.getElementById('ws-verify-fallback');
    const primaryWaLink  = document.getElementById('ws-primary-wa-link');
    const primaryNumDis  = document.getElementById('ws-primary-wa-num-display');
    const fallbackWaLink = document.getElementById('ws-verify-wa-link');

    const popupStateKey = 'watchstore_verify_popup_<?php echo esc_js( $order_id ); ?>';

    if (sessionStorage.getItem(popupStateKey) === 'dismissed') {
        overlay.style.display = 'none';
        return;
    }

    function dismissPopup() {
        sessionStorage.setItem(popupStateKey, 'dismissed');
        overlay.style.display = 'none';
    }

    function normalizePhoneForWa(phoneStr) {
        let digits = String(phoneStr || '').replace(/\D/g, '');
        if (digits.startsWith('0')) {
            digits = '92' + digits.substring(1);
        } else if (!digits.startsWith('92')) {
            digits = '92' + digits;
        }
        return digits;
    }

    document.querySelectorAll('.ws-verify-close').forEach(btn => {
        btn.addEventListener('click', dismissPopup);
    });

    if (primaryWaLink) {
        primaryWaLink.addEventListener('click', dismissPopup);
    }

    if (fallbackWaLink) {
        fallbackWaLink.addEventListener('click', dismissPopup);
    }

    fetch('<?php echo esc_js( admin_url( "admin-ajax.php" ) ); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=watchstore_send_verification&order_id=<?php echo esc_js( $order_id ); ?>'
    })
    .then(res => res.json())
    .then(data => {
        loadingEl.style.display = 'none';

        const defaultPrimary = '<?php echo esc_js( $primary_number ); ?>';
        const primaryNum = data.primaryNumber || defaultPrimary;
        const msg = encodeURIComponent('Hi! I placed order #<?php echo esc_js( $order_id ); ?> and I am waiting for confirmation.');

        if (data.success && data.reason === 'sent') {
            sentEl.style.display = 'block';
        } else if (data.reason === 'not_on_whatsapp') {
            notOnWaEl.style.display = 'block';
            const primDigits = normalizePhoneForWa(primaryNum);
            primaryWaLink.href = 'https://wa.me/' + primDigits + '?text=' + msg;
            if (primaryNumDis) {
                primaryNumDis.textContent = primaryNum;
            }
        } else {
            fallbackEl.style.display = 'block';
            const secNum = data.secondaryNumber || primaryNum;
            const secDigits = normalizePhoneForWa(secNum);
            fallbackWaLink.href = 'https://wa.me/' + secDigits + '?text=' + msg;
        }
    })
    .catch(() => {
        loadingEl.style.display = 'none';
        fallbackEl.style.display = 'block';
        const defaultPrimary = '<?php echo esc_js( $primary_number ); ?>';
        const primDigits = normalizePhoneForWa(defaultPrimary);
        const msg = encodeURIComponent('Hi! I placed order #<?php echo esc_js( $order_id ); ?> and I am waiting for confirmation.');
        fallbackWaLink.href = 'https://wa.me/' + primDigits + '?text=' + msg;
    });
})();
    </script>
    <?php endif; ?>

    <!-- Persistent section — stays visible on the page after the popup is closed -->
    <div id="ws-persistent-section" class="ws-persistent-section">

        <div id="ws-p-active">
            <p class="ws-p-note">Check your WhatsApp (<strong><?php echo esc_html( $customer_phone ); ?></strong>) to confirm your order. Didn't get a message?</p>
            <a href="#" id="ws-p-wa-link" target="_blank" class="ws-green-wa-btn">
                <svg class="ws-wa-icon" viewBox="0 0 448 512" width="20" height="20" fill="currentColor">
                    <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/>
                </svg>
                <span>Message us on WhatsApp</span>
            </a>
            <p class="ws-p-note" style="margin-top:14px;">Using a different device? Send us this Order ID to confirm manually:</p>
            <div class="pk-pay-detail">
                <span>#<?php echo esc_html( $order_id ); ?></span>
                <button type="button" class="pk-copy-btn" data-copy="<?php echo esc_attr( $order_id ); ?>">Copy</button>
            </div>
            <button type="button" id="ws-p-resend-btn" class="ws-resend-btn">Resend WhatsApp Message</button>

            <?php if ( $order->get_payment_method() === 'raast_transfer' ) : ?>
            <div class="ws-payment-info-card" style="text-align:left; margin-top:18px;">
                <p class="ws-payment-instruction-note">
                    <strong>Important:</strong> Transfer the required amount using any option below, then provide your payment slip. We ship as soon as payment is verified.
                </p>
                <div class="ws-pay-method-block">
                    <h4 class="ws-pay-method-title">Bank Deposit</h4>
                    <div class="ws-pay-detail-row">
                        <span class="ws-detail-label">Bank Name:</span>
                        <span class="ws-detail-val"><?php echo esc_html( $gateway->bank_name ); ?></span>
                    </div>
                    <div class="ws-pay-detail-row">
                        <span class="ws-detail-label">Account Name:</span>
                        <span class="ws-detail-val"><?php echo esc_html( $gateway->account_name ); ?></span>
                        <button type="button" class="pk-copy-btn" data-copy="<?php echo esc_attr( $gateway->account_name ); ?>">Copy</button>
                    </div>
                    <div class="ws-pay-detail-row">
                        <span class="ws-detail-label">Account Number:</span>
                        <span class="ws-detail-val"><?php echo esc_html( $gateway->account_number ); ?></span>
                        <button type="button" class="pk-copy-btn" data-copy="<?php echo esc_attr( $gateway->account_number ); ?>">Copy</button>
                    </div>
                    <div class="ws-pay-detail-row">
                        <span class="ws-detail-label">IBAN:</span>
                        <span class="ws-detail-val"><?php echo esc_html( $gateway->iban ); ?></span>
                        <button type="button" class="pk-copy-btn" data-copy="<?php echo esc_attr( $gateway->iban ); ?>">Copy</button>
                    </div>
                </div>
                <?php if ( ! empty( $gateway->easypaisa_number ) ) : ?>
                <div class="ws-pay-method-block">
                    <h4 class="ws-pay-method-title">EasyPaisa</h4>
                    <div class="ws-pay-detail-row">
                        <span class="ws-detail-label">Number:</span>
                        <span class="ws-detail-val"><?php echo esc_html( $gateway->easypaisa_number ); ?></span>
                        <button type="button" class="pk-copy-btn" data-copy="<?php echo esc_attr( $gateway->easypaisa_number ); ?>">Copy</button>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ( ! empty( $gateway->jazzcash_number ) ) : ?>
                <div class="ws-pay-method-block">
                    <h4 class="ws-pay-method-title">JazzCash</h4>
                    <div class="ws-pay-detail-row">
                        <span class="ws-detail-label">Number:</span>
                        <span class="ws-detail-val"><?php echo esc_html( $gateway->jazzcash_number ); ?></span>
                        <button type="button" class="pk-copy-btn" data-copy="<?php echo esc_attr( $gateway->jazzcash_number ); ?>">Copy</button>
                    </div>
                </div>
                <?php endif; ?>
                <div class="ws-pay-method-block">
                    <h4 class="ws-pay-method-title">Send your payment slip to this number</h4>
                    <div class="ws-pay-detail-row">
                        <span class="ws-detail-val"><?php echo esc_html( $primary_number ); ?></span>
                        <button type="button" class="pk-copy-btn" data-copy="<?php echo esc_attr( $primary_number ); ?>">Copy</button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div id="ws-p-verifying" style="display:none;">
            <div class="ws-p-verifying-icon">⏳</div>
            <p class="ws-p-note"><strong>Verifying your order.</strong> This may take some time — we'll update this page once it's confirmed.</p>
        </div>

        <div id="ws-p-maxed" style="display:none;" class="ws-p-highlight">
            <p>Phone not on WhatsApp or the verification message couldn't be sent. Please send us your Order ID (<strong>#<?php echo esc_html( $order_id ); ?></strong>) on <strong><?php echo esc_html( $primary_number ); ?></strong> to confirm manually.</p>
        </div>

        <div id="ws-p-verified" style="display:none;">
            <div class="ws-check-circle">&#10003;</div>
            <h3 class="ws-verify-title">Your order has been verified!</h3>
            <p class="ws-p-note">It will arrive in 3–4 working days.</p>
        </div>
    </div>

    <style>
        .ws-persistent-section {
            max-width: 480px; margin: 24px auto; padding: 20px;
            border: 1px solid #e5e7eb; border-radius: 10px;
            background: #fafafa; text-align: center;
        }
        .ws-p-note { font-size: 14px; color: #374151; line-height: 1.5; margin: 0 0 10px 0; }
        .ws-p-highlight {
            background: #fff7ed; border: 1px solid #fdba74;
            border-radius: 8px; padding: 14px; font-size: 14px; color: #7c2d12;
        }
        #ws-p-verifying {
            background: #fffbeb;
            border: 1px solid #fbbf24;
            border-left: 5px solid #f59e0b;
            border-radius: 8px;
            padding: 18px;
        }
        #ws-p-verifying .ws-p-note {
            font-size: 15px;
            color: #92400e;
            margin: 0;
        }
        .ws-p-verifying-icon {
            font-size: 24px;
            margin-bottom: 8px;
        }
        .ws-resend-btn {
            margin-top: 10px; padding: 8px 18px; border: 1px solid #d1d5db;
            border-radius: 6px; background: #fff; cursor: pointer; font-size: 14px;
        }
        .ws-resend-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    </style>

    <script>
    (function () {
        const orderId       = <?php echo esc_js( $order_id ); ?>;
        const defaultPrimary = '<?php echo esc_js( $primary_number ); ?>';
        const activeEl   = document.getElementById('ws-p-active');
        const verifyingEl= document.getElementById('ws-p-verifying');
        const maxedEl    = document.getElementById('ws-p-maxed');
        const verifiedEl = document.getElementById('ws-p-verified');
        const waLink     = document.getElementById('ws-p-wa-link');
        const resendBtn  = document.getElementById('ws-p-resend-btn');

        function normalizePhoneForWa(phoneStr) {
            let digits = String(phoneStr || '').replace(/\D/g, '');
            if (digits.startsWith('0')) digits = '92' + digits.substring(1);
            else if (!digits.startsWith('92')) digits = '92' + digits;
            return digits;
        }

        function showOnly(el) {
            [activeEl, verifyingEl, maxedEl, verifiedEl].forEach(e => e.style.display = 'none');
            el.style.display = 'block';
        }

        const msg = encodeURIComponent('Hi! I placed order #' + orderId + ' and I am waiting for confirmation.');
        waLink.href = 'https://wa.me/' + normalizePhoneForWa(defaultPrimary) + '?text=' + msg;

        function poll() {
            const body = 'action=watchstore_check_order_status&order_id=' + orderId;
            fetch('<?php echo esc_js( admin_url( "admin-ajax.php" ) ); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                if (data.wcStatus === 'processing') {
                    showOnly(verifiedEl);
                    return; // fully done, stop polling
                }

                if (data.baileysStatus === 'screenshot_received' || data.baileysStatus === 'confirmed') {
                    showOnly(verifyingEl);
                } else if (data.attemptsUsed >= 4) {
                    showOnly(maxedEl);
                } else {
                    showOnly(activeEl);
                    resendBtn.disabled = false;
                }

                setTimeout(poll, 7000);
            })
            .catch(() => setTimeout(poll, 7000));
        }

        resendBtn.addEventListener('click', function () {
            resendBtn.disabled = true;
            resendBtn.textContent = 'Sending...';
            fetch('<?php echo esc_js( admin_url( "admin-ajax.php" ) ); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=watchstore_send_verification&order_id=' + orderId
            })
            .then(res => res.json())
            .then(data => {
                if (data.reason === 'max_attempts_reached' || data.attemptsUsed >= 4) {
                    showOnly(maxedEl);
                } else {
                    resendBtn.textContent = 'Resend WhatsApp Message';
                    resendBtn.disabled = false;
                }
            })
            .catch(() => {
                resendBtn.textContent = 'Resend WhatsApp Message';
                resendBtn.disabled = false;
            });
        });

        poll();
    })();
    </script>
    <?php
}
 
add_action( 'wp_footer', 'watchstore_render_keepwarm_ping' );
function watchstore_render_keepwarm_ping() {
    if ( ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) return;
    $gateways = WC()->payment_gateways()->payment_gateways();
    $gateway  = null;
    if ( isset( $gateways['raast_transfer'] ) ) {
        $gateway = $gateways['raast_transfer'];
    }
    if ( ! $gateway ) return;
    $render_url = rtrim( $gateway->get_option( 'render_url' ), '/' );
    if ( empty( $render_url ) ) return;
    ?>
    <script>
    (function () {
        var url = <?php echo wp_json_encode( $render_url . '/health' ); ?>;
        var key = 'ws_render_ping_ts';
        var now = Date.now();
        var last = 0;
        try { last = parseInt(sessionStorage.getItem(key) || '0', 10); } catch (e) {}
        if (now - last < 600000) return;
        try { sessionStorage.setItem(key, String(now)); } catch (e) {}
        fetch(url, { method: 'GET', mode: 'no-cors', cache: 'no-store' }).catch(function () {});
    })();
    </script>
    <?php
}

add_action( 'wp_footer', 'watchstore_copy_button_script' );
function watchstore_copy_button_script() {
    if ( ! is_checkout() && ! is_wc_endpoint_url( 'order-received' ) ) return;
    ?>
    <script>
    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('pk-copy-btn')) return;
        const btn = e.target;
        const text = btn.getAttribute('data-copy');
        if (!text) return;
        navigator.clipboard.writeText(text).then(function () {
            const original = btn.textContent;
            btn.textContent = 'Copied!';
            btn.classList.add('copied');
            setTimeout(function () {
                btn.textContent = original;
                btn.classList.remove('copied');
            }, 1500);
        });
    });
    </script>
    <?php
}

add_action( 'woocommerce_email_after_order_table', 'watchstore_add_tracking_link_to_email', 10, 4 );
function watchstore_add_tracking_link_to_email( $order, $sent_to_admin, $plain_text, $email ) {
    if ( $sent_to_admin ) return;
    if ( ! $order ) return;
    $url = $order->get_checkout_order_received_url();
    if ( empty( $url ) ) return;
    if ( $plain_text ) {
        echo "\nTrack your order: " . esc_url( $url ) . "\n";
    } else {
        echo '<p>Track your order: <a href="' . esc_url( $url ) . '">' . esc_html( $url ) . '</a></p>';
    }
}
