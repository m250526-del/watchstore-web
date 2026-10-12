<?php
/**
 * Custom WooCommerce Checkout Template for WatchStore
 *
 * Sections (in order):
 * 1. Order summary       – thumbnails + total only
 * 2. Contact             – "Email or phone number"
 * 3. Delivery            – First name, Last name, Address, City, Postal code (optional), Phone (required)
 * 4. Payment method      – Cash on Delivery / Advance Payment
 * 5. Payment details     – bank/EP/JC info, visible only for Advance Payment
 * 6. Billing address     – same / different toggle
 * 7. Total
 * 8. Complete order
 * 9. Footer policy links
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
    echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
    return;
}

// Fetch gateway for payment details output
$ws_gateways = WC()->payment_gateways()->payment_gateways();
$ws_gateway  = isset( $ws_gateways['raast_transfer'] ) ? $ws_gateways['raast_transfer'] : null;
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout ws-checkout-form" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

    <div class="ws-checkout-container">

        <!-- 1. ORDER SUMMARY: thumbnails + total only -->
        <div class="ws-checkout-card ws-section-order-summary">
            <h2 class="ws-card-title">Order summary</h2>
            <div class="ws-summary-compact">
                <div class="ws-summary-thumbs">
                    <?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
                        $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
                        if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] < 1 ) continue;
                        $thumbnail = $_product->get_image( array( 48, 48 ) );
                        if ( $cart_item['quantity'] > 1 ) :
                    ?>
                        <div class="ws-thumb-wrap">
                            <?php echo wp_kses_post( $thumbnail ); ?>
                            <span class="ws-thumb-qty"><?php echo esc_html( $cart_item['quantity'] ); ?></span>
                        </div>
                    <?php else : ?>
                        <div class="ws-thumb-wrap">
                            <?php echo wp_kses_post( $thumbnail ); ?>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
                <div class="ws-summary-total">
                    <strong><?php echo WC()->cart->get_total(); ?></strong>
                </div>
            </div>
        </div>

        <!-- 2. CONTACT: "Email or phone number" -->
        <div class="ws-checkout-card ws-section-contact">
            <h2 class="ws-card-title">Contact</h2>
            <div class="ws-field-group">
                <?php
                $billing_fields = $checkout->get_checkout_fields( 'billing' );
                if ( isset( $billing_fields['billing_email'] ) ) {
                    $email_field                  = $billing_fields['billing_email'];
                    $email_field['label']         = 'Email or phone number';
                    $email_field['placeholder']   = 'Email or phone number';
                    $email_field['class']         = array( 'form-row-wide' );
                    $email_field['validate']      = array(); // loosen built-in email-only validation
                    woocommerce_form_field( 'billing_email', $email_field, $checkout->get_value( 'billing_email' ) );
                }
                ?>
            </div>
        </div>

        <!-- 3. DELIVERY: First name, Last name, Address, City, Postal code (opt), Phone (required) -->
        <div class="ws-checkout-card ws-section-delivery">
            <h2 class="ws-card-title">Delivery</h2>
            <div class="ws-delivery-fields">
                <?php
                $billing_fields = $checkout->get_checkout_fields( 'billing' );

                // First name
                if ( isset( $billing_fields['billing_first_name'] ) ) {
                    woocommerce_form_field( 'billing_first_name', $billing_fields['billing_first_name'], $checkout->get_value( 'billing_first_name' ) );
                }

                // Last name
                if ( isset( $billing_fields['billing_last_name'] ) ) {
                    woocommerce_form_field( 'billing_last_name', $billing_fields['billing_last_name'], $checkout->get_value( 'billing_last_name' ) );
                }

                // Address – single field, labelled "Address"
                if ( isset( $billing_fields['billing_address_1'] ) ) {
                    $addr_field                = $billing_fields['billing_address_1'];
                    $addr_field['label']       = 'Address';
                    $addr_field['placeholder'] = 'Full delivery address';
                    $addr_field['class']       = array( 'form-row-wide' );
                    woocommerce_form_field( 'billing_address_1', $addr_field, $checkout->get_value( 'billing_address_1' ) );
                }
                // address_2 intentionally omitted

                // City – labelled "City"
                if ( isset( $billing_fields['billing_city'] ) ) {
                    $city_field          = $billing_fields['billing_city'];
                    $city_field['label'] = 'City';
                    $city_field['class'] = array( 'form-row-wide' );
                    woocommerce_form_field( 'billing_city', $city_field, $checkout->get_value( 'billing_city' ) );
                }

                // Postal code – optional
                if ( isset( $billing_fields['billing_postcode'] ) ) {
                    $pc_field                  = $billing_fields['billing_postcode'];
                    $pc_field['required']      = false;
                    $pc_field['label']         = 'Postal code';
                    $pc_field['placeholder']   = 'Postal code (optional)';
                    $pc_field['class']         = array( 'form-row-wide' );
                    woocommerce_form_field( 'billing_postcode', $pc_field, $checkout->get_value( 'billing_postcode' ) );
                }

                // Phone – required, one field only
                if ( isset( $billing_fields['billing_phone_country_code'] ) ) {
                    $cc_field = $billing_fields['billing_phone_country_code'];
                    woocommerce_form_field( 'billing_phone_country_code', $cc_field, $checkout->get_value( 'billing_phone_country_code' ) );
                }
                if ( isset( $billing_fields['billing_phone'] ) ) {
                    $phone_field               = $billing_fields['billing_phone'];
                    $phone_field['label']      = 'Phone';
                    $phone_field['required']   = true;
                    $phone_field['class']      = array( 'form-row-wide' );
                    woocommerce_form_field( 'billing_phone', $phone_field, $checkout->get_value( 'billing_phone' ) );
                }
                // country, state, address_2 intentionally omitted from visible output
                ?>
            </div>
        </div>

        <!-- 4. PAYMENT METHOD -->
        <div class="ws-checkout-card ws-section-payment-method">
            <h2 class="ws-card-title">Payment method</h2>
            <div class="ws-option-cards-group">
                <label class="ws-option-card ws-payment-choice active" data-choice="cod" data-payment-id="cod">
                    <input type="radio" name="ws_payment_method_choice" value="cod" checked="checked" class="ws-radio-input">
                    <div class="ws-option-text">
                        <span class="ws-option-title">Cash on Delivery</span>
                        <span class="ws-option-desc">Pay with cash when your package arrives</span>
                    </div>
                </label>
                <label class="ws-option-card ws-payment-choice" data-choice="advance" data-payment-id="raast_transfer">
                    <input type="radio" name="ws_payment_method_choice" value="advance" class="ws-radio-input">
                    <div class="ws-option-text">
                        <span class="ws-option-title">Advance Payment (1 to 3 Working Day)</span>
                        <span class="ws-option-desc">Pay online or with bank.</span>
                    </div>
                </label>
            </div>

            <!-- Hidden WooCommerce payment radio buttons (required for order processing) -->
            <div id="payment" style="display:none;">
                <?php if ( WC()->cart->needs_payment() ) : ?>
                    <ul class="wc_payment_methods payment_methods methods">
                        <?php
                        $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
                        foreach ( $available_gateways as $gateway ) {
                            wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
                        }
                        ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>


        <!-- 6. BILLING ADDRESS -->
        <div class="ws-checkout-card ws-section-billing-address">
            <h2 class="ws-card-title">Billing address</h2>
            <div class="ws-option-cards-group">
                <label class="ws-option-card ws-billing-choice active" data-billing="same">
                    <input type="radio" name="ws_billing_address_choice" value="same" checked="checked" class="ws-radio-input">
                    <div class="ws-option-text">
                        <span class="ws-option-title">Same as shipping address</span>
                    </div>
                </label>
                <label class="ws-option-card ws-billing-choice" data-billing="different">
                    <input type="radio" name="ws_billing_address_choice" value="different" class="ws-radio-input">
                    <div class="ws-option-text">
                        <span class="ws-option-title">Use a different billing address</span>
                    </div>
                </label>
            </div>
            <div id="ws-different-billing-fields" class="ws-different-billing-wrapper" style="display:none;">
                <?php
                $billing_fields_full = $checkout->get_checkout_fields( 'billing' );
                foreach ( $billing_fields_full as $key => $field ) {
                    if ( in_array( $key, array( 'billing_email', 'billing_phone' ), true ) ) {
                        continue;
                    }
                    $diff_key = 'ws_diff_' . $key;
                    $field['id'] = $diff_key;
                    woocommerce_form_field( $diff_key, $field, $checkout->get_value( $key ) );
                }
                ?>
            </div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('form.checkout');
    if (!form) return;
    var sameRadio = document.querySelector('input[name="ws_billing_address_choice"][value="same"]');
    var diffRadio = document.querySelector('input[name="ws_billing_address_choice"][value="different"]');
    var fieldMap = [
        ['billing_first_name', 'ws_diff_billing_first_name'],
        ['billing_last_name', 'ws_diff_billing_last_name'],
        ['billing_address_1', 'ws_diff_billing_address_1'],
        ['billing_city', 'ws_diff_billing_city'],
        ['billing_postcode', 'ws_diff_billing_postcode']
    ];
    form.addEventListener('submit', function () {
        var useDifferent = diffRadio && diffRadio.checked;
        var i = 0;
        while (i < fieldMap.length) {
            var realField = document.getElementById(fieldMap[i][0]);
            var diffField = document.getElementById(fieldMap[i][1]);
            if (realField && useDifferent && diffField) {
                realField.value = diffField.value;
            }
            i = i + 1;
        }
    });
});
</script>
        </div>

        <!-- 7. TOTAL (Collapsible Accordion) -->
        <div class="ws-checkout-card ws-section-total ws-total-accordion">
            <button type="button" class="ws-total-toggle-header" id="ws-total-toggle-btn" aria-expanded="false">
                <div class="ws-total-header-left">
                    <h2 class="ws-card-title ws-total-accordion-title">Total</h2>
                    <div class="ws-total-header-thumbs">
                        <?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
                            $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
                            if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] < 1 ) continue;
                            $thumbnail = $_product->get_image( array( 36, 36 ) );
                        ?>
                            <div class="ws-total-stack-thumb">
                                <?php echo wp_kses_post( $thumbnail ); ?>
                                <?php if ( $cart_item['quantity'] > 1 ) : ?>
                                    <span class="ws-thumb-qty"><?php echo esc_html( $cart_item['quantity'] ); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="ws-total-header-right">
                    <span class="ws-total-header-grand"><?php echo WC()->cart->get_total(); ?></span>
                    <svg class="ws-total-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </div>
            </button>

            <div class="ws-total-expanded-body" id="ws-total-expanded-body" style="display: none;">
                <!-- Product List -->
                <div class="ws-total-items-list">
                    <?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) :
                        $_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
                        if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] < 1 ) continue;
                        $thumbnail     = $_product->get_image( array( 44, 44 ) );
                        $product_name  = $_product->get_name();
                        $product_price = WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] );
                    ?>
                        <div class="ws-total-item-row">
                            <div class="ws-total-item-img">
                                <?php echo wp_kses_post( $thumbnail ); ?>
                                <?php if ( $cart_item['quantity'] > 1 ) : ?>
                                    <span class="ws-thumb-qty"><?php echo esc_html( $cart_item['quantity'] ); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="ws-total-item-info">
                                <span class="ws-total-item-name"><?php echo esc_html( $product_name ); ?></span>
                                <span class="ws-total-item-qty">Qty: <?php echo esc_html( $cart_item['quantity'] ); ?></span>
                            </div>
                            <div class="ws-total-item-price">
                                <?php echo wp_kses_post( $product_price ); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Price Breakdown -->
                <div class="ws-totals-summary">
                    <div class="ws-total-line">
                        <span>Subtotal</span>
                        <span><?php echo WC()->cart->get_cart_subtotal(); ?></span>
                    </div>
                    <?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
                        <div class="ws-total-line">
                            <span>Shipping</span>
                            <span><?php echo WC()->cart->get_cart_shipping_total(); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
                        <div class="ws-total-line ws-discount-line">
                            <span>Discount (<?php echo esc_html( $code ); ?>)</span>
                            <span><?php wc_cart_totals_coupon_html( $coupon ); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div class="ws-total-line ws-grand-total">
                        <strong>Total</strong>
                        <strong><?php echo WC()->cart->get_total(); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- 8. COMPLETE ORDER -->
        <div class="ws-checkout-card ws-section-complete-order">
            <div class="ws-complete-order-action">
                <button type="submit" class="button alt ws-complete-order-btn" name="woocommerce_checkout_place_order" id="place_order" value="Complete order" data-value="Complete order">Complete order</button>
                <?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
            </div>
        </div>

        <!-- 9. FOOTER POLICY LINKS -->
        <div class="ws-checkout-footer-policies">
            <a href="<?php echo esc_url( get_privacy_policy_url() ); ?>" target="_blank">Refund policy</a>
            <a href="<?php echo esc_url( get_privacy_policy_url() ); ?>" target="_blank">Shipping policy</a>
            <a href="<?php echo esc_url( get_privacy_policy_url() ); ?>" target="_blank">Privacy policy</a>
            <a href="<?php echo esc_url( get_privacy_policy_url() ); ?>" target="_blank">Terms of service</a>
        </div>

    </div>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
