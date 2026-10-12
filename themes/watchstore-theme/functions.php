<?php
/**
 * WatchStore child theme functions
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Theme Setup ─────────────────────────────────────────────────────────────
add_action( 'after_setup_theme', 'watchstore_setup', 20 );
function watchstore_setup() {
    add_theme_support( 'custom-logo', array(
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ) );
}


// ── Styles ─────────────────────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'watchstore_enqueue_styles' );
function watchstore_enqueue_styles() {
    wp_enqueue_style(
        'watchstore-style',
        get_stylesheet_uri(),
        array(),
        filemtime( get_stylesheet_directory() . '/style.css' )
    );
}

// ── Remove login & coupon forms from top of checkout ──────────────────────
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_login_form', 10 );
remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );

// ── Disable guest-account creation prompt ─────────────────────────────────
add_filter( 'woocommerce_checkout_registration_enabled', '__return_false' );

// ───────────────────────────────────────────────────────────────────────────
// WooCommerce Checkout Field Customization & Validation Removal
// ───────────────────────────────────────────────────────────────────────────

// 1. Remove unwanted fields from WooCommerce checkout field definitions
add_filter( 'woocommerce_checkout_fields', 'watchstore_customize_checkout_fields', 9999 );
function watchstore_customize_checkout_fields( $fields ) {
    // Unset removed billing fields
    unset( $fields['billing']['billing_country'] );
    unset( $fields['billing']['billing_state'] );
    unset( $fields['billing']['billing_address_2'] );
    unset( $fields['billing']['billing_company'] );

    // Unset removed shipping fields
    unset( $fields['shipping']['shipping_country'] );
    unset( $fields['shipping']['shipping_state'] );
    unset( $fields['shipping']['shipping_address_2'] );
    unset( $fields['shipping']['shipping_company'] );

    // Ensure Postal Code is explicitly OPTIONAL
    if ( isset( $fields['billing']['billing_postcode'] ) ) {
        $fields['billing']['billing_postcode']['required'] = false;
    }
    if ( isset( $fields['shipping']['shipping_postcode'] ) ) {
        $fields['shipping']['shipping_postcode']['required'] = false;
    }

    // Ensure Single Required Phone Field
    if ( isset( $fields['billing']['billing_phone'] ) ) {
        $fields['billing']['billing_phone']['label']    = 'Phone';
        $fields['billing']['billing_phone']['required'] = true;
    }

    return $fields;
}

// 2. Unset removed fields from individual billing & shipping field filters
add_filter( 'woocommerce_billing_fields', 'watchstore_customize_billing_fields', 9999 );
function watchstore_customize_billing_fields( $fields ) {
    unset( $fields['billing_country'] );
    unset( $fields['billing_state'] );
    unset( $fields['billing_address_2'] );
    unset( $fields['billing_company'] );

    if ( isset( $fields['billing_address_1'] ) ) {
        $fields['billing_address_1']['label']       = 'Address';
        $fields['billing_address_1']['placeholder'] = 'Full delivery address';
        $fields['billing_address_1']['required']    = true;
    }
    if ( isset( $fields['billing_city'] ) ) {
        $fields['billing_city']['label']    = 'City';
        $fields['billing_city']['required'] = true;
    }
    if ( isset( $fields['billing_postcode'] ) ) {
        $fields['billing_postcode']['label']       = 'Postal code';
        $fields['billing_postcode']['placeholder'] = 'Postal code (optional)';
        $fields['billing_postcode']['required']    = false;
    }
    if ( isset( $fields['billing_phone'] ) ) {
        $fields['billing_phone']['label']    = 'Phone';
        $fields['billing_phone']['required'] = true;
    }
    return $fields;
}

add_filter( 'woocommerce_shipping_fields', 'watchstore_customize_shipping_fields', 9999 );
function watchstore_customize_shipping_fields( $fields ) {
    unset( $fields['shipping_country'] );
    unset( $fields['shipping_state'] );
    unset( $fields['shipping_address_2'] );
    unset( $fields['shipping_company'] );

    if ( isset( $fields['shipping_address_1'] ) ) {
        $fields['shipping_address_1']['label']       = 'Address';
        $fields['shipping_address_1']['placeholder'] = 'Full delivery address';
        $fields['shipping_address_1']['required']    = true;
    }
    if ( isset( $fields['shipping_city'] ) ) {
        $fields['shipping_city']['label']    = 'City';
        $fields['shipping_city']['required'] = true;
    }
    if ( isset( $fields['shipping_postcode'] ) ) {
        $fields['shipping_postcode']['label']       = 'Postal code';
        $fields['shipping_postcode']['placeholder'] = 'Postal code (optional)';
        $fields['shipping_postcode']['required']    = false;
    }
    return $fields;
}

// 3. Address field rules (State, Country, Address 2, Postal code optional)
add_filter( 'woocommerce_default_address_fields', 'watchstore_custom_default_address_fields', 9999 );
function watchstore_custom_default_address_fields( $fields ) {
    if ( isset( $fields['postcode'] ) ) {
        $fields['postcode']['required'] = false;
    }
    if ( isset( $fields['state'] ) ) {
        $fields['state']['required'] = false;
    }
    if ( isset( $fields['country'] ) ) {
        $fields['country']['required'] = false;
    }
    if ( isset( $fields['address_2'] ) ) {
        $fields['address_2']['required'] = false;
    }
    return $fields;
}

// 4. Override Country Locale rules so state/postcode are never required by WooCommerce
add_filter( 'woocommerce_get_country_locale', 'watchstore_country_locale_override', 9999 );
function watchstore_country_locale_override( $locale ) {
    foreach ( $locale as $code => $rules ) {
        $locale[ $code ]['state']['required']    = false;
        $locale[ $code ]['state']['hidden']      = true;
        $locale[ $code ]['postcode']['required'] = false;
    }
    return $locale;
}

// 5. Default country PK
add_filter( 'default_checkout_billing_country',  'watchstore_default_pk_country' );
add_filter( 'default_checkout_shipping_country', 'watchstore_default_pk_country' );
function watchstore_default_pk_country() {
    return 'PK';
}

// 6. Force billing/shipping country to PK & empty states on order submission
add_filter( 'woocommerce_checkout_posted_data', 'watchstore_force_billing_country_pk', 9999 );
function watchstore_force_billing_country_pk( $data ) {
    $data['billing_country']    = 'PK';
    $data['shipping_country']   = 'PK';
    $data['billing_state']      = '';
    $data['shipping_state']     = '';
    $data['billing_address_2']  = '';
    $data['shipping_address_2'] = '';

    // Synchronize shipping address from billing if shipping fields are omitted
    if ( empty( $data['shipping_first_name'] ) && ! empty( $data['billing_first_name'] ) ) {
        $data['shipping_first_name'] = $data['billing_first_name'];
    }
    if ( empty( $data['shipping_last_name'] ) && ! empty( $data['billing_last_name'] ) ) {
        $data['shipping_last_name'] = $data['billing_last_name'];
    }
    if ( empty( $data['shipping_address_1'] ) && ! empty( $data['billing_address_1'] ) ) {
        $data['shipping_address_1'] = $data['billing_address_1'];
    }
    if ( empty( $data['shipping_city'] ) && ! empty( $data['billing_city'] ) ) {
        $data['shipping_city'] = $data['billing_city'];
    }
    if ( empty( $data['shipping_postcode'] ) && ! empty( $data['billing_postcode'] ) ) {
        $data['shipping_postcode'] = $data['billing_postcode'];
    }

    return $data;
}

// 7. Bypass process_checkout_field state sanitization errors
add_filter( 'woocommerce_process_checkout_field_billing_state', '__return_empty_string' );
add_filter( 'woocommerce_process_checkout_field_shipping_state', '__return_empty_string' );
add_filter( 'woocommerce_process_checkout_field_billing_country', 'watchstore_default_pk_country' );
add_filter( 'woocommerce_process_checkout_field_shipping_country', 'watchstore_default_pk_country' );

// ── Checkout interaction JS ───────────────────────────────────────────────
add_action( 'wp_footer', 'watchstore_checkout_interaction_script' );
function watchstore_checkout_interaction_script() {
    if ( ! is_checkout() ) return;
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {

        // ── 1. Payment Method cards → sync hidden WC radios + show/hide Payment details ──
        const paymentChoiceCards = document.querySelectorAll('.ws-payment-choice');
        const paymentDetailsSection = document.getElementById('ws-payment-details-section');

        function applyPaymentChoice(card) {
            paymentChoiceCards.forEach(c => c.classList.remove('active'));
            card.classList.add('active');

            const radio = card.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;

            const wcPaymentId = card.getAttribute('data-payment-id');
            if (wcPaymentId) {
                const wcRadio = document.querySelector('#payment_method_' + wcPaymentId);
                if (wcRadio) {
                    wcRadio.checked = true;
                    wcRadio.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }

            // Show / hide Payment details section
            if (paymentDetailsSection) {
                const choice = card.getAttribute('data-choice');
                paymentDetailsSection.style.display = (choice === 'advance') ? 'block' : 'none';
            }
        }

        paymentChoiceCards.forEach(card => {
            card.addEventListener('click', function () {
                applyPaymentChoice(this);
            });
        });

        // Initialise on load (COD is default → hide payment details)
        const initialActive = document.querySelector('.ws-payment-choice.active');
        if (initialActive) applyPaymentChoice(initialActive);

        // ── 2. Billing Address toggle ─────────────────────────────────────
        const billingCards = document.querySelectorAll('.ws-billing-choice');
        const diffBillingWrapper = document.getElementById('ws-different-billing-fields');

        billingCards.forEach(card => {
            card.addEventListener('click', function () {
                billingCards.forEach(c => c.classList.remove('active'));
                this.classList.add('active');

                const radio = this.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;

                if (diffBillingWrapper) {
                    diffBillingWrapper.style.display =
                        (this.getAttribute('data-billing') === 'different') ? 'block' : 'none';
                }
            });
        });

        // ── 3. Total Accordion Toggle ──────────────────────────────────────
        const totalBtn = document.getElementById('ws-total-toggle-btn');
        const totalBody = document.getElementById('ws-total-expanded-body');

        if (totalBtn && totalBody) {
            function updateAccordionState(expanded) {
                if (expanded) {
                    totalBtn.setAttribute('aria-expanded', 'true');
                    totalBtn.classList.add('open');
                    totalBody.style.display = 'block';
                    sessionStorage.setItem('ws_total_expanded', '1');
                } else {
                    totalBtn.setAttribute('aria-expanded', 'false');
                    totalBtn.classList.remove('open');
                    totalBody.style.display = 'none';
                    sessionStorage.setItem('ws_total_expanded', '0');
                }
            }

            totalBtn.addEventListener('click', function (e) {
                e.preventDefault();
                const currentlyExpanded = totalBtn.getAttribute('aria-expanded') === 'true';
                updateAccordionState(!currentlyExpanded);
            });

            // Restore saved state (defaults to collapsed '0')
            if (sessionStorage.getItem('ws_total_expanded') === '1') {
                updateAccordionState(true);
            }
        }

    });
    </script>
    <?php
}

add_action( 'after_setup_theme', 'watchstore_remove_footer_bar', 20 );
function watchstore_remove_footer_bar() {
    remove_action( 'storefront_footer', 'storefront_handheld_footer_bar', 999 );
}

add_action( 'wp_body_open', 'watchstore_mobile_top_icons_bar' );
function watchstore_mobile_top_icons_bar() {
    $links = array(
        'search' => array( 'priority' => 20, 'callback' => 'storefront_handheld_footer_bar_search' ),
        'cart' => array( 'priority' => 30, 'callback' => 'storefront_handheld_footer_bar_cart_link' ),
    );
    if ( wc_get_page_id( 'cart' ) === -1 ) {
        unset( $links['cart'] );
    }
    $links = apply_filters( 'storefront_handheld_footer_bar_links', $links );
    unset( $links['my-account'] );
    $logo_id = get_theme_mod( 'custom_logo' );
    ?>
    <div class="storefront-handheld-footer-bar watchstore-mobile-top-bar">
        <ul>
            <li class="ws-bar-menu"><a href="#" aria-label="Menu">Menu</a></li>
            <?php foreach ( $links as $key => $link ) : ?>
            <li class="<?php echo esc_attr( $key ); ?>">
                <?php if ( $link['callback'] ) { call_user_func( $link['callback'], $key, $link ); } ?>
            </li>
            <?php endforeach; ?>
            <li class="ws-bar-logo">
                <?php if ( $logo_id ) { ?>
                <a class="custom-logo-link" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'custom-logo', 'sizes' => '140px', 'alt' => get_bloginfo( 'name' ) ) ); ?></a>
                <?php } else { ?>
                <a class="ws-bar-site-name" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></a>
                <?php } ?>
            </li>
        </ul>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.querySelector('.ws-bar-menu > a');
        var real = document.getElementById('site-navigation-menu-toggle');
        if (!btn || !real) { return; }
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            real.click();
        });
    });
    </script>
    <?php
}

add_action( 'storefront_content_top', 'ws_render_page_heading', 6 );
function ws_render_page_heading() {
    $text = '';
    if ( is_front_page() ) {
        $text = 'Home';
    } elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
        $text = single_term_title( '', false );
    } elseif ( function_exists( 'is_shop' ) && is_shop() ) {
        $text = 'Watches';
    }
    if ( $text === '' ) {
        return;
    }
    echo '<h2 class="ws-page-heading">' . esc_html( $text ) . '</h2>';
}