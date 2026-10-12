<?php
if ( ! defined( 'ABSPATH' ) ) exit;
function watchstore_dynamic_home_url( $value ) {
    if ( isset( $_SERVER['HTTP_HOST'] ) ) {
        $host = $_SERVER['HTTP_HOST'];
        if ( strpos( $host, 'watch-web.local' ) !== 0 ) {
            return 'https://' . $host;
        }
        if ( $host !== 'watch-web.local' ) {
            return 'http://' . $host;
        }
    }
    return $value;
}
add_filter( 'option_siteurl', 'watchstore_dynamic_home_url' );
add_filter( 'option_home', 'watchstore_dynamic_home_url' );
