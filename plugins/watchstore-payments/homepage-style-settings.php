<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'ws_homepage_style_menu' );
function ws_homepage_style_menu() {
    add_options_page( 'Homepage Style', 'Homepage Style', 'manage_options', 'ws-homepage-style', 'ws_homepage_style_page' );
}

add_action( 'admin_enqueue_scripts', 'ws_homepage_style_enqueue' );
function ws_homepage_style_enqueue( $hook ) {
    if ( $hook !== 'settings_page_ws-homepage-style' ) {
        return;
    }
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
}

function ws_homepage_style_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( isset( $_POST['ws_homepage_style_nonce'] ) && wp_verify_nonce( $_POST['ws_homepage_style_nonce'], 'ws_homepage_style_save' ) ) {
        $new_start = '';
        $new_end   = '';
        if ( isset( $_POST['ws_gradient_start'] ) ) {
            $new_start = (string) sanitize_hex_color( wp_unslash( $_POST['ws_gradient_start'] ) );
        }
        if ( isset( $_POST['ws_gradient_end'] ) ) {
            $new_end = (string) sanitize_hex_color( wp_unslash( $_POST['ws_gradient_end'] ) );
        }
        update_option( 'ws_homepage_gradient', array( 'start' => $new_start, 'end' => $new_end ) );
        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
    }
    $saved = get_option( 'ws_homepage_gradient', array() );
    $start = '';
    $end   = '';
    if ( is_array( $saved ) && isset( $saved['start'] ) ) {
        $start = $saved['start'];
    }
    if ( is_array( $saved ) && isset( $saved['end'] ) ) {
        $end = $saved['end'];
    }
    ?>
    <div class="wrap">
        <h1>Homepage Style</h1>
        <form method="post">
            <?php wp_nonce_field( 'ws_homepage_style_save', 'ws_homepage_style_nonce' ); ?>
            <table class="form-table">
                <tr><th>Gradient top color</th><td><input type="text" class="ws-gradient-picker" name="ws_gradient_start" value="<?php echo esc_attr( $start ); ?>" /></td></tr>
                <tr><th>Gradient bottom color</th><td><input type="text" class="ws-gradient-picker" name="ws_gradient_end" value="<?php echo esc_attr( $end ); ?>" /></td></tr>
            </table>
            <p>Both colors are needed. Leave both empty for no gradient.</p>
            <?php submit_button( 'Save Changes' ); ?>
        </form>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('.ws-gradient-picker').wpColorPicker();
    });
    </script>
    <?php
}

add_action( 'wp_head', 'ws_homepage_gradient_css' );
function ws_homepage_gradient_css() {
    if ( ! is_front_page() ) {
        return;
    }
    $saved = get_option( 'ws_homepage_gradient', array() );
    if ( ! is_array( $saved ) || empty( $saved['start'] ) || empty( $saved['end'] ) ) {
        return;
    }
    $start = sanitize_hex_color( $saved['start'] );
    $end   = sanitize_hex_color( $saved['end'] );
    if ( ! $start || ! $end ) {
        return;
    }
    echo '<style>body.home{background-color:#ffffff !important;background-image:linear-gradient(180deg,' . esc_attr( $start ) . ' 0,' . esc_attr( $end ) . ' 640px,#ffffff 940px) !important;background-repeat:no-repeat !important;}body.home .site-header,body.home .storefront-primary-navigation,body.home .site-header .col-full{background:transparent !important;}</style>';
}
