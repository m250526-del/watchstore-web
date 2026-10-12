<?php

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'add_meta_boxes', 'ws_color_variants_meta_box' );
function ws_color_variants_meta_box() {
    add_meta_box(
        'ws_color_variants',
        'Color Variants',
        'ws_color_variants_meta_box_html',
        'product',
        'normal',
        'default'
    );
}

add_action( 'admin_enqueue_scripts', 'ws_color_variants_enqueue' );
function ws_color_variants_enqueue( $hook ) {
    if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) {
        return;
    }
    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'product' ) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
}

function ws_color_variants_meta_box_html( $post ) {
    wp_nonce_field( 'ws_color_variants_save', 'ws_color_variants_nonce' );
    $variants = get_post_meta( $post->ID, '_ws_color_variants', true );
    if ( ! is_array( $variants ) ) {
        $variants = array();
    }
    ?>
    <div id="ws-color-rows">
        <?php foreach ( $variants as $variant ) { ?>
        <?php
        $v_hex   = isset( $variant['hex'] )          ? $variant['hex']          : '';
        $v_url   = isset( $variant['image_url'] )    ? $variant['image_url']    : '';
        $v_oos   = isset( $variant['out_of_stock'] ) ? $variant['out_of_stock'] : false;
        $v_check = $v_oos ? 'checked' : '';
        ?>
        <div class="ws-color-row" style="display:flex;align-items:center;gap:10px;margin-bottom:10px;padding:10px;border:1px solid #ddd;border-radius:4px;">
            <input type="text" class="ws-color-picker" name="color_hex[]" value="<?php echo esc_attr( $v_hex ); ?>" />
            <button type="button" class="button ws-choose-color-photo">Choose Photo</button>
            <input type="hidden" name="ws_color_url[]" class="ws-color-url" value="<?php echo esc_attr( $v_url ); ?>" />
            <div class="ws-color-preview" style="width:50px;height:50px;overflow:hidden;border:1px solid #ccc;">
                <?php if ( $v_url !== '' ) { ?>
                <img src="<?php echo esc_url( $v_url ); ?>" style="width:100%;height:100%;object-fit:cover;" />
                <?php } ?>
            </div>
            <label style="white-space:nowrap;">
                <input type="checkbox" name="ws_color_oos[]" class="ws-color-oos" value="1" <?php echo $v_check; ?> /> Out of stock
            </label>
            <input type="hidden" name="ws_color_oos_flag[]" class="ws-oos-flag" value="<?php echo $v_oos ? '1' : '0'; ?>" />
            <button type="button" class="button ws-remove-color-row">Remove</button>
        </div>
        <?php } ?>
    </div>
    <button type="button" class="button button-secondary" id="ws-add-color-row" style="margin-top:8px;">+ Add Color</button>

    <script>
    jQuery(document).ready(function($) {

        function initColorPickers() {
            jQuery('.ws-color-picker').not('.wp-color-picker').wpColorPicker();
        }
        initColorPickers();

        function bindColorRow(row) {
            var removeBtn  = row.querySelector('.ws-remove-color-row');
            var chooseBtn  = row.querySelector('.ws-choose-color-photo');
            var urlInput   = row.querySelector('.ws-color-url');
            var preview    = row.querySelector('.ws-color-preview');
            var oosCheck   = row.querySelector('.ws-color-oos');
            var oosFlag    = row.querySelector('.ws-oos-flag');

            removeBtn.addEventListener('click', function() {
                row.remove();
            });

            oosCheck.addEventListener('change', function() {
                if (oosCheck.checked) {
                    oosFlag.value = '1';
                } else {
                    oosFlag.value = '0';
                }
            });

            chooseBtn.addEventListener('click', function() {
                var frame = wp.media({
                    title: 'Select Color Photo',
                    button: { text: 'Use this photo' },
                    library: { type: 'image' },
                    multiple: false
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    urlInput.value = attachment.url;
                    preview.innerHTML = '';
                    var img = document.createElement('img');
                    img.src = attachment.url;
                    img.style.cssText = 'width:100%;height:100%;object-fit:cover;';
                    preview.appendChild(img);
                });

                frame.open();
            });
        }

        var existingRows = document.querySelectorAll('#ws-color-rows .ws-color-row');
        for (var i = 0; i < existingRows.length; i++) {
            bindColorRow(existingRows[i]);
        }

        document.getElementById('ws-add-color-row').addEventListener('click', function() {
            var container = document.getElementById('ws-color-rows');
            var row = document.createElement('div');
            row.className = 'ws-color-row';
            row.style.cssText = 'display:flex;align-items:center;gap:10px;margin-bottom:10px;padding:10px;border:1px solid #ddd;border-radius:4px;';
            row.innerHTML = '<input type="text" class="ws-color-picker" name="color_hex[]" value="" />'
                + '<button type="button" class="button ws-choose-color-photo">Choose Photo</button>'
                + '<input type="hidden" name="ws_color_url[]" class="ws-color-url" value="" />'
                + '<div class="ws-color-preview" style="width:50px;height:50px;overflow:hidden;border:1px solid #ccc;"></div>'
                + '<label style="white-space:nowrap;"><input type="checkbox" name="ws_color_oos[]" class="ws-color-oos" value="1" /> Out of stock</label>'
                + '<input type="hidden" name="ws_color_oos_flag[]" class="ws-oos-flag" value="0" />'
                + '<button type="button" class="button ws-remove-color-row">Remove</button>';
            container.appendChild(row);
            bindColorRow(row);
            initColorPickers();
        });

    });
    </script>
    <?php
}

add_action( 'save_post_product', 'ws_color_variants_save_meta' );
function ws_color_variants_save_meta( $post_id ) {
    if ( ! isset( $_POST['ws_color_variants_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( $_POST['ws_color_variants_nonce'], 'ws_color_variants_save' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $hexes     = isset( $_POST['color_hex'] )         && is_array( $_POST['color_hex'] )         ? $_POST['color_hex']         : array();
    $urls      = isset( $_POST['ws_color_url'] )      && is_array( $_POST['ws_color_url'] )      ? $_POST['ws_color_url']      : array();
    $oos_flags = isset( $_POST['ws_color_oos_flag'] ) && is_array( $_POST['ws_color_oos_flag'] ) ? $_POST['ws_color_oos_flag'] : array();

    $variants = array();
    $count = count( $hexes );
    for ( $i = 0; $i < $count; $i++ ) {
        $hex = sanitize_hex_color( isset( $hexes[ $i ] ) ? $hexes[ $i ] : '' );
        $url = esc_url_raw( isset( $urls[ $i ] ) ? $urls[ $i ] : '' );
        $oos = isset( $oos_flags[ $i ] ) && $oos_flags[ $i ] === '1';

        if ( $hex === '' && $url === '' ) {
            continue;
        }

        if ( $hex === null ) {
            $hex = '';
        }

        $entry = array(
            'hex'          => $hex,
            'image_url'    => $url,
            'out_of_stock' => $oos,
        );
        $variants[] = $entry;
    }

    update_post_meta( $post_id, '_ws_color_variants', $variants );
}

add_action( 'woocommerce_after_shop_loop_item_title', 'ws_render_color_swatches', 15 );
function ws_render_color_swatches() {
    global $product;
    if ( ! $product ) {
        return;
    }
    $variants = get_post_meta( $product->get_id(), '_ws_color_variants', true );
    if ( ! is_array( $variants ) || empty( $variants ) ) {
        return;
    }

    $default_url = '';
    foreach ( $variants as $v ) {
        $is_oos = isset( $v['out_of_stock'] ) ? $v['out_of_stock'] : false;
        if ( ! $is_oos && isset( $v['image_url'] ) && $v['image_url'] !== '' ) {
            $default_url = $v['image_url'];
            break;
        }
    }

    $product_id = $product->get_id();
    ?>
    <div class="ws-color-swatches" data-product-id="<?php echo esc_attr( $product_id ); ?>" data-default-img="<?php echo esc_attr( $default_url ); ?>">
        <?php foreach ( $variants as $v ) { ?>
        <?php
        $v_hex = isset( $v['hex'] )           ? $v['hex']           : '';
        $v_url = isset( $v['image_url'] )     ? $v['image_url']     : '';
        $v_oos = isset( $v['out_of_stock'] )  ? $v['out_of_stock']  : false;

        $swatch_class = 'ws-color-swatch';
        if ( $v_oos ) {
            $swatch_class .= ' out-of-stock';
        }

        $swatch_style = '';
        if ( $v_hex !== '' ) {
            $swatch_style = 'background-color:' . esc_attr( $v_hex ) . ';';
        }
        ?>
        <span
            class="<?php echo esc_attr( $swatch_class ); ?>"
            title="<?php echo esc_attr( $v_hex ); ?>"
            <?php if ( ! $v_oos && $v_url !== '' ) { ?>
            data-img="<?php echo esc_attr( $v_url ); ?>"
            <?php } ?>
            style="<?php echo $swatch_style; ?>">
        </span>
        <?php } ?>
    </div>
    <?php
}

add_action( 'wp_footer', 'ws_color_swatches_assets' );
function ws_color_swatches_assets() {
    if ( ! is_shop() && ! is_product_category() && ! is_product_tag() && ! is_front_page() && ! is_home() ) {
        return;
    }
    ?>
    <style>
    .ws-color-swatches {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 8px 0 4px;
        justify-content: center;
    }
    .ws-color-swatch{display:inline-block;width:24px;height:24px;border-radius:50%;border:1px solid rgba(0,0,0,.15);cursor:pointer;position:relative;margin-right:6px}
    .ws-color-swatch.active{box-shadow:0 0 0 2px #111}
    .ws-color-swatch.out-of-stock{cursor:not-allowed;opacity:.6}
    .ws-color-swatch.out-of-stock::before{content:"";position:absolute;top:50%;left:-2px;right:-2px;height:2px;background:#e11d1d;transform:rotate(-45deg);transform-origin:center}
    </style>
    <script>
    (function() {
        document.addEventListener('click', function(e) {
            var swatch = e.target.closest('.ws-color-swatch');
            if ( ! swatch ) return;
            if ( swatch.classList.contains('out-of-stock') ) return;

            var swatchesContainer = swatch.closest('.ws-color-swatches');
            if ( ! swatchesContainer ) return;

            var card = swatchesContainer.closest('li.product');
            if ( ! card ) return;

            var imgEl = card.querySelector('a img');
            if ( ! imgEl ) return;

            var newSrc = swatch.getAttribute('data-img');
            if ( ! newSrc ) return;

            imgEl.src = newSrc;

            var srcset = imgEl.getAttribute('srcset');
            if ( srcset ) {
                imgEl.removeAttribute('srcset');
            }

            var allSwatches = swatchesContainer.querySelectorAll('.ws-color-swatch');
            for (var i = 0; i < allSwatches.length; i++) {
                allSwatches[i].classList.remove('active');
            }
            swatch.classList.add('active');
        });

        document.addEventListener('DOMContentLoaded', function() {
            var allContainers = document.querySelectorAll('.ws-color-swatches');
            for (var c = 0; c < allContainers.length; c++) {
                var container = allContainers[c];
                var defaultImg = container.getAttribute('data-default-img');
                if ( ! defaultImg ) continue;

                var card = container.closest('li.product');
                if ( ! card ) continue;

                var imgEl = card.querySelector('a img');
                if ( ! imgEl ) continue;

                imgEl.src = defaultImg;

                var srcset = imgEl.getAttribute('srcset');
                if ( srcset ) {
                    imgEl.removeAttribute('srcset');
                }

                var firstActive = container.querySelector('.ws-color-swatch:not(.out-of-stock)');
                if ( firstActive ) {
                    firstActive.classList.add('active');
                }
            }
        });
    })();
    </script>
    <?php
}
