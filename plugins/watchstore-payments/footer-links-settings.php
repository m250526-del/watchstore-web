<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'watchstore_footer_links_admin_menu' );
function watchstore_footer_links_admin_menu() {
    add_options_page(
        'Footer Links',
        'Footer Links',
        'manage_options',
        'ws-footer-links',
        'watchstore_footer_links_render_admin_page'
    );
}

function watchstore_footer_links_render_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_POST['ws_footer_links_nonce'] ) ) {
        if ( wp_verify_nonce( $_POST['ws_footer_links_nonce'], 'ws_footer_links_save' ) ) {
            $data = array();

            if ( isset( $_POST['whatsapp_contact_number'] ) ) {
                $data['whatsapp_contact_number'] = sanitize_text_field( $_POST['whatsapp_contact_number'] );
            } else {
                $data['whatsapp_contact_number'] = '';
            }

            if ( isset( $_POST['contact_email'] ) ) {
                $data['contact_email'] = sanitize_text_field( $_POST['contact_email'] );
            } else {
                $data['contact_email'] = '';
            }

            if ( isset( $_POST['instagram_url'] ) ) {
                $data['instagram_url'] = esc_url_raw( $_POST['instagram_url'] );
            } else {
                $data['instagram_url'] = '';
            }

            if ( isset( $_POST['facebook_url'] ) ) {
                $data['facebook_url'] = esc_url_raw( $_POST['facebook_url'] );
            } else {
                $data['facebook_url'] = '';
            }

            if ( isset( $_POST['tiktok_url'] ) ) {
                $data['tiktok_url'] = esc_url_raw( $_POST['tiktok_url'] );
            } else {
                $data['tiktok_url'] = '';
            }

            if ( isset( $_POST['community_whatsapp_url'] ) ) {
                $data['community_whatsapp_url'] = esc_url_raw( $_POST['community_whatsapp_url'] );
            } else {
                $data['community_whatsapp_url'] = '';
            }

            if ( isset( $_POST['community_instagram_url'] ) ) {
                $data['community_instagram_url'] = esc_url_raw( $_POST['community_instagram_url'] );
            } else {
                $data['community_instagram_url'] = '';
            }

            update_option( 'ws_footer_links', $data );
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }
    }

    $saved = get_option( 'ws_footer_links', array() );
    if ( ! is_array( $saved ) ) {
        $saved = array();
    }

    $raast_settings = get_option( 'woocommerce_raast_transfer_settings' );
    $default_phone  = '0321-5817987';
    if ( is_array( $raast_settings ) ) {
        if ( ! empty( $raast_settings['primary_number'] ) ) {
            $default_phone = $raast_settings['primary_number'];
        }
    }

    if ( isset( $saved['whatsapp_contact_number'] ) ) {
        $val_wa_phone = $saved['whatsapp_contact_number'];
    } else {
        $val_wa_phone = $default_phone;
    }

    if ( isset( $saved['contact_email'] ) ) {
        $val_email = $saved['contact_email'];
    } else {
        $val_email = '';
    }

    if ( isset( $saved['instagram_url'] ) ) {
        $val_ig = $saved['instagram_url'];
    } else {
        $val_ig = '';
    }

    if ( isset( $saved['facebook_url'] ) ) {
        $val_fb = $saved['facebook_url'];
    } else {
        $val_fb = '';
    }

    if ( isset( $saved['tiktok_url'] ) ) {
        $val_tt = $saved['tiktok_url'];
    } else {
        $val_tt = '';
    }

    if ( isset( $saved['community_whatsapp_url'] ) ) {
        $val_comm_wa = $saved['community_whatsapp_url'];
    } else {
        $val_comm_wa = '';
    }

    if ( isset( $saved['community_instagram_url'] ) ) {
        $val_comm_ig = $saved['community_instagram_url'];
    } else {
        $val_comm_ig = '';
    }
    ?>
    <div class="wrap">
        <h1>Footer Links Settings</h1>
        <form method="post" action="">
            <?php wp_nonce_field( 'ws_footer_links_save', 'ws_footer_links_nonce' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="whatsapp_contact_number">WhatsApp Contact Number</label></th>
                    <td>
                        <input type="text" id="whatsapp_contact_number" name="whatsapp_contact_number" value="<?php echo esc_attr( $val_wa_phone ); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="contact_email">Contact Email</label></th>
                    <td>
                        <input type="text" id="contact_email" name="contact_email" value="<?php echo esc_attr( $val_email ); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="instagram_url">Instagram URL</label></th>
                    <td>
                        <input type="text" id="instagram_url" name="instagram_url" value="<?php echo esc_attr( $val_ig ); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="facebook_url">Facebook URL</label></th>
                    <td>
                        <input type="text" id="facebook_url" name="facebook_url" value="<?php echo esc_attr( $val_fb ); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="tiktok_url">TikTok URL</label></th>
                    <td>
                        <input type="text" id="tiktok_url" name="tiktok_url" value="<?php echo esc_attr( $val_tt ); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="community_whatsapp_url">Community WhatsApp Group URL</label></th>
                    <td>
                        <input type="text" id="community_whatsapp_url" name="community_whatsapp_url" value="<?php echo esc_attr( $val_comm_wa ); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="community_instagram_url">Community Instagram Page URL</label></th>
                    <td>
                        <input type="text" id="community_instagram_url" name="community_instagram_url" value="<?php echo esc_attr( $val_comm_ig ); ?>" class="regular-text" />
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Save Changes' ); ?>
        </form>
    </div>
    <?php
}

add_action( 'widgets_init', 'watchstore_register_footer_widgets' );
function watchstore_register_footer_widgets() {
    wp_register_sidebar_widget( 'ws_footer_col_2', 'Footer Contact Us', 'watchstore_render_contact_us_column' );
    wp_register_sidebar_widget( 'ws_footer_col_3', 'Footer Follow Us', 'watchstore_render_follow_us_column' );
    wp_register_sidebar_widget( 'ws_footer_col_4', 'Footer Join Community', 'watchstore_render_join_community_column' );
}

add_filter( 'sidebars_widgets', 'watchstore_inject_footer_sidebars' );
function watchstore_inject_footer_sidebars( $sidebars ) {
    if ( is_admin() ) {
        return $sidebars;
    }

    if ( empty( $sidebars['footer-2'] ) ) {
        $sidebars['footer-2'] = array( 'ws_footer_col_2' );
    }

    if ( empty( $sidebars['footer-3'] ) ) {
        $sidebars['footer-3'] = array( 'ws_footer_col_3' );
    }

    if ( empty( $sidebars['footer-4'] ) ) {
        $sidebars['footer-4'] = array( 'ws_footer_col_4' );
    }

    return $sidebars;
}

function watchstore_digits_only( $phone_str ) {
    $digits = preg_replace( '/\D/', '', (string) $phone_str );
    if ( str_starts_with( $digits, '0' ) ) {
        $digits = '92' . substr( $digits, 1 );
    } else {
        if ( ! str_starts_with( $digits, '92' ) ) {
            $digits = '92' . $digits;
        }
    }
    return $digits;
}

function watchstore_get_footer_options() {
    $saved = get_option( 'ws_footer_links', array() );
    if ( ! is_array( $saved ) ) {
        $saved = array();
    }
    return $saved;
}

function watchstore_render_contact_us_column() {
    $saved = watchstore_get_footer_options();

    $raast_settings = get_option( 'woocommerce_raast_transfer_settings' );
    $default_phone  = '0321-5817987';
    if ( is_array( $raast_settings ) ) {
        if ( ! empty( $raast_settings['primary_number'] ) ) {
            $default_phone = $raast_settings['primary_number'];
        }
    }

    if ( isset( $saved['whatsapp_contact_number'] ) ) {
        $wa_num = $saved['whatsapp_contact_number'];
    } else {
        $wa_num = $default_phone;
    }

    if ( isset( $saved['contact_email'] ) ) {
        $email = $saved['contact_email'];
    } else {
        $email = '';
    }

    ?>
    <div id="ws-footer-contact" class="widget ws-footer-widget">
        <span class="gamma widget-title">Contact Us</span>
        <div class="ws-footer-buttons">
            <?php if ( ! empty( $wa_num ) ) : ?>
                <?php $wa_digits = watchstore_digits_only( $wa_num ); ?>
                <a href="https://wa.me/<?php echo esc_attr( $wa_digits ); ?>" target="_blank" rel="noopener" class="ws-footer-btn ws-footer-btn-wa" aria-label="Contact on WhatsApp">
                    <svg viewBox="0 0 448 512" width="18" height="18" fill="currentColor"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg>
                    <span>WhatsApp</span>
                </a>
            <?php endif; ?>
            <?php if ( ! empty( $email ) ) : ?>
                <a href="mailto:<?php echo esc_attr( $email ); ?>" class="ws-footer-btn ws-footer-btn-email" aria-label="Send Email">
                    <svg viewBox="0 0 512 512" width="18" height="18" fill="currentColor"><path d="M48 64C21.5 64 0 85.5 0 112c0 15.1 7.1 29.3 19.2 38.4l217.6 163.2c12.2 9.1 28.5 9.1 40.7 0l217.6-163.2C504.9 141.3 512 127.1 512 112c0-26.5-21.5-48-48-48L48 64zM0 176L0 400c0 26.5 21.5 48 48 48l416 0c26.5 0 48-21.5 48-48l0-224-198.4 148.8c-34.1 25.6-81.1 25.6-115.2 0L0 176z"/></svg>
                    <span>Email</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function watchstore_render_follow_us_column() {
    $saved = watchstore_get_footer_options();

    if ( isset( $saved['instagram_url'] ) ) {
        $ig = $saved['instagram_url'];
    } else {
        $ig = '';
    }

    if ( isset( $saved['facebook_url'] ) ) {
        $fb = $saved['facebook_url'];
    } else {
        $fb = '';
    }

    if ( isset( $saved['tiktok_url'] ) ) {
        $tt = $saved['tiktok_url'];
    } else {
        $tt = '';
    }

    ?>
    <div id="ws-footer-follow" class="widget ws-footer-widget">
        <span class="gamma widget-title">Follow Us</span>
        <div class="ws-footer-buttons">
            <?php if ( ! empty( $ig ) ) : ?>
                <a href="<?php echo esc_url( $ig ); ?>" target="_blank" rel="noopener" class="ws-footer-btn ws-footer-btn-ig" aria-label="Instagram">
                    <svg viewBox="0 0 448 512" width="18" height="18" fill="currentColor"><path d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.9-26.9 26.9-14.9 0-26.9-12-26.9-26.9s12-26.9 26.9-26.9c14.9 0 26.9 12 26.9 26.9zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z"/></svg>
                    <span>Instagram</span>
                </a>
            <?php endif; ?>
            <?php if ( ! empty( $fb ) ) : ?>
                <a href="<?php echo esc_url( $fb ); ?>" target="_blank" rel="noopener" class="ws-footer-btn ws-footer-btn-fb" aria-label="Facebook">
                    <svg viewBox="0 0 512 512" width="18" height="18" fill="currentColor"><path d="M504 256C504 119 393 8 256 8S8 119 8 256c0 123.78 90.69 226.38 209.25 245V327.69h-63V256h63v-54.64c0-62.15 37-96.48 93.67-96.48 27.14 0 55.52 4.84 55.52 4.84v61h-31.28c-30.8 0-40.41 19.12-40.41 38.73V256h68.78l-11 71.69h-57.78V501C413.31 482.38 504 379.78 504 256z"/></svg>
                    <span>Facebook</span>
                </a>
            <?php endif; ?>
            <?php if ( ! empty( $tt ) ) : ?>
                <a href="<?php echo esc_url( $tt ); ?>" target="_blank" rel="noopener" class="ws-footer-btn ws-footer-btn-tt" aria-label="TikTok">
                    <svg viewBox="0 0 448 512" width="18" height="18" fill="currentColor"><path d="M448 209.91a210.06 210.06 0 0 1-122.77-39.25V349.38A162.55 162.55 0 1 1 185 188.31V258.2a91.73 91.73 0 1 0 70.36 89.26V0h69.44a140.7 140.7 0 0 0 123.2 138.86z"/></svg>
                    <span>TikTok</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function watchstore_render_join_community_column() {
    $saved = watchstore_get_footer_options();

    if ( isset( $saved['community_whatsapp_url'] ) ) {
        $comm_wa = $saved['community_whatsapp_url'];
    } else {
        $comm_wa = '';
    }

    if ( isset( $saved['community_instagram_url'] ) ) {
        $comm_ig = $saved['community_instagram_url'];
    } else {
        $comm_ig = '';
    }

    ?>
    <div id="ws-footer-community" class="widget ws-footer-widget">
        <span class="gamma widget-title">Join Our Community</span>
        <div class="ws-footer-buttons">
            <?php if ( ! empty( $comm_wa ) ) : ?>
                <a href="<?php echo esc_url( $comm_wa ); ?>" target="_blank" rel="noopener" class="ws-footer-btn ws-footer-btn-wa" aria-label="WhatsApp Community">
                    <svg viewBox="0 0 448 512" width="18" height="18" fill="currentColor"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg>
                    <span>WhatsApp Group</span>
                </a>
            <?php endif; ?>
            <?php if ( ! empty( $comm_ig ) ) : ?>
                <a href="<?php echo esc_url( $comm_ig ); ?>" target="_blank" rel="noopener" class="ws-footer-btn ws-footer-btn-ig" aria-label="Instagram Community">
                    <svg viewBox="0 0 448 512" width="18" height="18" fill="currentColor"><path d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.9-26.9 26.9-14.9 0-26.9-12-26.9-26.9s12-26.9 26.9-26.9c14.9 0 26.9 12 26.9 26.9zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z"/></svg>
                    <span>Instagram Page</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

add_action( 'wp_head', 'watchstore_footer_columns_styles' );
function watchstore_footer_columns_styles() {
    ?>
    <style>
        .ws-footer-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 12px;
        }
        .ws-footer-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 6px;
            background: #222222;
            color: #ffffff !important;
            text-decoration: none !important;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s ease, transform 0.1s ease;
            width: fit-content;
        }
        .ws-footer-btn:hover {
            background: #333333;
            color: #ffffff !important;
            transform: translateY(-1px);
        }
        .ws-footer-btn svg {
            flex-shrink: 0;
        }
        .ws-footer-btn-wa {
            background: #25D366;
        }
        .ws-footer-btn-wa:hover {
            background: #1da851;
        }
        .ws-footer-btn-ig {
            background: #E1306C;
        }
        .ws-footer-btn-ig:hover {
            background: #c1275b;
        }
        .ws-footer-btn-fb {
            background: #1877F2;
        }
        .ws-footer-btn-fb:hover {
            background: #1464c9;
        }
        .ws-footer-btn-tt {
            background: #000000;
        }
        .ws-footer-btn-tt:hover {
            background: #222222;
        }
        .ws-footer-btn-email {
            background: #4b5563;
        }
        .ws-footer-btn-email:hover {
            background: #374151;
        }
    </style>
    <?php
}
