<?php

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'ws_homepage_content_menu' );
function ws_homepage_content_menu() {
    add_options_page(
        'Homepage Content',
        'Homepage Content',
        'manage_options',
        'ws-homepage-content',
        'ws_homepage_content_page'
    );
}

add_action( 'admin_enqueue_scripts', 'ws_homepage_content_enqueue' );
function ws_homepage_content_enqueue( $hook ) {
    if ( $hook !== 'settings_page_ws-homepage-content' ) {
        return;
    }
    wp_enqueue_media();
}

add_action( 'admin_init', 'ws_homepage_content_save' );
function ws_homepage_content_save() {
    if ( ! isset( $_POST['ws_homepage_content_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( $_POST['ws_homepage_content_nonce'], 'ws_homepage_content_save' ) ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $ticker_lines = array();
    if ( isset( $_POST['ticker_text'] ) && is_array( $_POST['ticker_text'] ) ) {
        foreach ( $_POST['ticker_text'] as $line ) {
            $clean = sanitize_text_field( $line );
            if ( $clean !== '' ) {
                $ticker_lines[] = $clean;
            }
        }
    }
    update_option( 'ws_homepage_ticker', $ticker_lines );

    $banner_slides = array();
    $types    = isset( $_POST['banner_type'] )    && is_array( $_POST['banner_type'] )    ? $_POST['banner_type']    : array();
    $urls     = isset( $_POST['banner_url'] )     && is_array( $_POST['banner_url'] )     ? $_POST['banner_url']     : array();
    $links    = isset( $_POST['banner_link'] )    && is_array( $_POST['banner_link'] )    ? $_POST['banner_link']    : array();
    $captions = isset( $_POST['banner_caption'] ) && is_array( $_POST['banner_caption'] ) ? $_POST['banner_caption'] : array();

    $count = count( $types );
    for ( $i = 0; $i < $count; $i++ ) {
        $type    = sanitize_text_field( isset( $types[ $i ] )    ? $types[ $i ]    : 'image' );
        $url     = esc_url_raw(          isset( $urls[ $i ] )     ? $urls[ $i ]     : '' );
        $link    = esc_url_raw(          isset( $links[ $i ] )    ? $links[ $i ]    : '' );
        $caption = sanitize_text_field( isset( $captions[ $i ] ) ? $captions[ $i ] : '' );

        if ( $url === '' ) {
            continue;
        }

        $slide = array(
            'type'    => $type,
            'url'     => $url,
            'link'    => $link,
            'caption' => $caption,
        );
        $banner_slides[] = $slide;
    }
    update_option( 'ws_homepage_banner', $banner_slides );

    set_transient( 'ws_homepage_content_saved', 1, 30 );
}

function ws_homepage_content_page() {
    $ticker_lines  = get_option( 'ws_homepage_ticker', array() );
    $banner_slides = get_option( 'ws_homepage_banner', array() );

    if ( ! is_array( $ticker_lines ) ) {
        $ticker_lines = array();
    }
    if ( ! is_array( $banner_slides ) ) {
        $banner_slides = array();
    }

    $saved = get_transient( 'ws_homepage_content_saved' );
    if ( $saved ) {
        delete_transient( 'ws_homepage_content_saved' );
    }
    ?>
    <div class="wrap">
        <h1>Homepage Content</h1>
        <?php if ( $saved ) { ?>
        <div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
        <?php } ?>

        <form method="post" action="">
            <?php wp_nonce_field( 'ws_homepage_content_save', 'ws_homepage_content_nonce' ); ?>

            <h2>Headline Ticker</h2>
            <p>Each line scrolls continuously across the site. Empty lines are ignored on save.</p>
            <div id="ws-ticker-rows">
                <?php if ( empty( $ticker_lines ) ) { ?>
                <div class="ws-ticker-row" style="display:flex;gap:8px;margin-bottom:6px;">
                    <input type="text" name="ticker_text[]" value="" style="flex:1;" />
                    <button type="button" class="button ws-remove-ticker-row">Remove</button>
                </div>
                <?php } else { ?>
                <?php foreach ( $ticker_lines as $line ) { ?>
                <div class="ws-ticker-row" style="display:flex;gap:8px;margin-bottom:6px;">
                    <input type="text" name="ticker_text[]" value="<?php echo esc_attr( $line ); ?>" style="flex:1;" />
                    <button type="button" class="button ws-remove-ticker-row">Remove</button>
                </div>
                <?php } ?>
                <?php } ?>
            </div>
            <button type="button" class="button" id="ws-add-ticker-row">+ Add Line</button>

            <hr style="margin:30px 0;" />

            <h2>Homepage Banner</h2>
            <p>Slides shown only on the homepage. Slides without a URL are skipped on save.</p>
            <div id="ws-banner-rows">
                <?php foreach ( $banner_slides as $slide ) { ?>
                <?php
                $s_type    = isset( $slide['type'] )    ? $slide['type']    : 'image';
                $s_url     = isset( $slide['url'] )     ? $slide['url']     : '';
                $s_link    = isset( $slide['link'] )    ? $slide['link']    : '';
                $s_caption = isset( $slide['caption'] ) ? $slide['caption'] : '';
                ?>
                <div class="ws-banner-row" style="border:1px solid #ccd0d4;padding:14px;margin-bottom:10px;border-radius:4px;">
                    <p>
                        <strong>Type:</strong>
                        <label style="margin-right:12px;">
                            <input type="radio" name="banner_type[]" value="image" <?php if ( $s_type === 'image' ) { echo 'checked'; } ?> class="ws-type-radio" /> Image
                        </label>
                        <label style="margin-right:12px;">
                            <input type="radio" name="banner_type[]" value="video" <?php if ( $s_type === 'video' ) { echo 'checked'; } ?> class="ws-type-radio" /> Video
                        </label>
                        <label>
                            <input type="radio" name="banner_type[]" value="video_url" <?php if ( $s_type === 'video_url' ) { echo 'checked'; } ?> class="ws-type-radio" /> Video URL (direct file or YouTube link)
                        </label>
                    </p>
                    <p class="ws-choose-media-wrap" <?php if ( $s_type === 'video_url' ) { echo 'style="display:none;"'; } ?>>
                        <button type="button" class="button ws-choose-media">Choose Media</button>
                    </p>
                    <p class="ws-video-url-wrap" <?php if ( $s_type !== 'video_url' ) { echo 'style="display:none;"'; } ?>>
                        <label>Direct Video URL:<br />
                            <input type="text" class="ws-video-url-input" placeholder="https://example.com/video.mp4" style="width:100%;" value="<?php if ( $s_type === 'video_url' ) { echo esc_attr( $s_url ); } ?>" />
                        </label>
                    </p>
                    <input type="hidden" name="banner_url[]" class="ws-banner-url" value="<?php echo esc_attr( $s_url ); ?>" />
                    <div class="ws-media-preview" style="margin-bottom:8px;">
                        <?php if ( $s_url !== '' ) { ?>
                        <?php if ( $s_type === 'image' ) { ?>
                        <img src="<?php echo esc_url( $s_url ); ?>" style="max-width:200px;max-height:120px;display:block;" />
                        <?php } elseif ( $s_type === 'video_url' ) { ?>
                        <span><?php echo esc_html( $s_url ); ?></span>
                        <?php } else { ?>
                        <span><?php echo esc_html( basename( $s_url ) ); ?></span>
                        <?php } ?>
                        <?php } ?>
                    </div>
                    <p>
                        <label>Link URL (optional):<br />
                            <input type="text" name="banner_link[]" value="<?php echo esc_attr( $s_link ); ?>" style="width:100%;" />
                        </label>
                    </p>
                    <p>
                        <label>Caption (optional):<br />
                            <input type="text" name="banner_caption[]" value="<?php echo esc_attr( $s_caption ); ?>" style="width:100%;" />
                        </label>
                    </p>
                    <button type="button" class="button ws-remove-banner-row">Remove Slide</button>
                </div>
                <?php } ?>
            </div>
            <button type="button" class="button" id="ws-add-banner-row">+ Add Slide</button>

            <hr style="margin:30px 0;" />
            <?php submit_button( 'Save Settings' ); ?>
        </form>
    </div>

    <script>
    (function() {

        document.getElementById('ws-add-ticker-row').addEventListener('click', function() {
            var container = document.getElementById('ws-ticker-rows');
            var row = document.createElement('div');
            row.className = 'ws-ticker-row';
            row.style.cssText = 'display:flex;gap:8px;margin-bottom:6px;';
            row.innerHTML = '<input type="text" name="ticker_text[]" value="" style="flex:1;" />'
                          + '<button type="button" class="button ws-remove-ticker-row">Remove</button>';
            container.appendChild(row);
            bindRemoveTicker(row.querySelector('.ws-remove-ticker-row'));
        });

        function bindRemoveTicker(btn) {
            btn.addEventListener('click', function() {
                btn.closest('.ws-ticker-row').remove();
            });
        }

        var tickerRemoveBtns = document.querySelectorAll('.ws-remove-ticker-row');
        for (var i = 0; i < tickerRemoveBtns.length; i++) {
            bindRemoveTicker(tickerRemoveBtns[i]);
        }

        function buildBannerRow(typeVal, urlVal, linkVal, captionVal) {
            var row = document.createElement('div');
            row.className = 'ws-banner-row';
            row.style.cssText = 'border:1px solid #ccd0d4;padding:14px;margin-bottom:10px;border-radius:4px;';

            var imgChecked      = (typeVal === 'image')     ? 'checked' : '';
            var videoChecked    = (typeVal === 'video')     ? 'checked' : '';
            var videoUrlChecked = (typeVal === 'video_url') ? 'checked' : '';

            var chooseWrapStyle   = (typeVal === 'video_url') ? 'display:none;' : '';
            var videoUrlWrapStyle = (typeVal !== 'video_url') ? 'display:none;' : '';
            var videoUrlVal      = (typeVal === 'video_url')  ? urlVal : '';

            var previewHtml = '';
            if (urlVal !== '') {
                if (typeVal === 'image') {
                    previewHtml = '<img src="' + urlVal + '" style="max-width:200px;max-height:120px;display:block;" />';
                } else if (typeVal === 'video_url') {
                    previewHtml = '<span>' + urlVal + '</span>';
                } else {
                    var parts = urlVal.split('/');
                    previewHtml = '<span>' + parts[parts.length - 1] + '</span>';
                }
            }

            row.innerHTML = '<p><strong>Type:</strong>'
                + '<label style="margin-right:12px;"><input type="radio" name="banner_type[]" value="image" '     + imgChecked      + ' class="ws-type-radio" /> Image</label>'
                + '<label style="margin-right:12px;"><input type="radio" name="banner_type[]" value="video" '     + videoChecked    + ' class="ws-type-radio" /> Video</label>'
                + '<label><input type="radio" name="banner_type[]" value="video_url" ' + videoUrlChecked + ' class="ws-type-radio" /> Video URL (direct file or YouTube link)</label>'
                + '</p>'
                + '<p class="ws-choose-media-wrap" style="' + chooseWrapStyle + '"><button type="button" class="button ws-choose-media">Choose Media</button></p>'
                + '<p class="ws-video-url-wrap" style="' + videoUrlWrapStyle + '"><label>Direct Video URL:<br /><input type="text" class="ws-video-url-input" placeholder="https://example.com/video.mp4" style="width:100%;" value="' + videoUrlVal + '" /></label></p>'
                + '<input type="hidden" name="banner_url[]" class="ws-banner-url" value="' + urlVal + '" />'
                + '<div class="ws-media-preview" style="margin-bottom:8px;">' + previewHtml + '</div>'
                + '<p><label>Link URL (optional):<br /><input type="text" name="banner_link[]" value="' + linkVal + '" style="width:100%;" /></label></p>'
                + '<p><label>Caption (optional):<br /><input type="text" name="banner_caption[]" value="' + captionVal + '" style="width:100%;" /></label></p>'
                + '<button type="button" class="button ws-remove-banner-row">Remove Slide</button>';

            bindBannerRow(row);
            return row;
        }

        function bindBannerRow(row) {
            var removeBtn = row.querySelector('.ws-remove-banner-row');
            removeBtn.addEventListener('click', function() {
                row.remove();
            });

            var urlInput      = row.querySelector('.ws-banner-url');
            var preview       = row.querySelector('.ws-media-preview');
            var chooseWrap    = row.querySelector('.ws-choose-media-wrap');
            var videoUrlWrap  = row.querySelector('.ws-video-url-wrap');
            var videoUrlInput = row.querySelector('.ws-video-url-input');

            var radios = row.querySelectorAll('.ws-type-radio');
            for (var r = 0; r < radios.length; r++) {
                radios[r].addEventListener('change', function() {
                    var selectedType = 'image';
                    for (var s = 0; s < radios.length; s++) {
                        if (radios[s].checked) {
                            selectedType = radios[s].value;
                        }
                    }
                    if (selectedType === 'video_url') {
                        chooseWrap.style.display   = 'none';
                        videoUrlWrap.style.display  = '';
                    } else {
                        chooseWrap.style.display   = '';
                        videoUrlWrap.style.display  = 'none';
                        videoUrlInput.value = '';
                    }
                });
            }

            if (videoUrlInput) {
                videoUrlInput.addEventListener('input', function() {
                    urlInput.value = videoUrlInput.value;
                    preview.innerHTML = '';
                    if (videoUrlInput.value !== '') {
                        var span = document.createElement('span');
                        span.textContent = videoUrlInput.value;
                        preview.appendChild(span);
                    }
                });
            }

            var chooseBtn = row.querySelector('.ws-choose-media');
            if (chooseBtn) {
                chooseBtn.addEventListener('click', function() {
                    var selectedType = 'image';
                    for (var r = 0; r < radios.length; r++) {
                        if (radios[r].checked) {
                            selectedType = radios[r].value;
                        }
                    }

                    var libraryType = 'image';
                    if (selectedType === 'video') {
                        libraryType = 'video';
                    }

                    var frame = wp.media({
                        title: 'Select Media',
                        button: { text: 'Use this media' },
                        library: { type: libraryType },
                        multiple: false
                    });

                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        urlInput.value = attachment.url;
                        preview.innerHTML = '';
                        if (selectedType === 'image') {
                            var img = document.createElement('img');
                            img.src = attachment.url;
                            img.style.cssText = 'max-width:200px;max-height:120px;display:block;';
                            preview.appendChild(img);
                        } else {
                            var parts = attachment.url.split('/');
                            var span = document.createElement('span');
                            span.textContent = parts[parts.length - 1];
                            preview.appendChild(span);
                        }
                    });

                    frame.open();
                });
            }
        }

        document.getElementById('ws-add-banner-row').addEventListener('click', function() {
            var container = document.getElementById('ws-banner-rows');
            var row = buildBannerRow('image', '', '', '');
            container.appendChild(row);
        });

        var existingBannerRows = document.querySelectorAll('.ws-banner-row');
        for (var j = 0; j < existingBannerRows.length; j++) {
            bindBannerRow(existingBannerRows[j]);
        }

    })();
    </script>
    <?php
}

add_action( 'storefront_before_content', 'ws_render_ticker_bar', 5 );
function ws_render_ticker_bar() {
    $lines = get_option( 'ws_homepage_ticker', array() );
    if ( ! is_array( $lines ) || empty( $lines ) ) {
        return;
    }
    $text = implode( '   •   ', $lines );
    ?>
    <div class="ws-ticker-bar" aria-label="News ticker">
        <div class="ws-ticker-track">
            <span class="ws-ticker-content"><?php echo esc_html( $text ); ?></span>
            <span class="ws-ticker-content" aria-hidden="true"><?php echo esc_html( $text ); ?></span>
        </div>
    </div>
    <style>
    .ws-ticker-bar {
        width: 100%;
        background: #111827;
        color: #ffffff;
        overflow: hidden;
        height: 36px;
        display: flex;
        align-items: center;
    }
    .ws-ticker-track {
        display: flex;
        white-space: nowrap;
        animation: ws-ticker-scroll 25s linear infinite;
    }
    .ws-ticker-content {
        font-size: 13px;
        font-weight: 500;
        letter-spacing: 0.02em;
        padding-right: 60px;
        flex-shrink: 0;
    }
    @keyframes ws-ticker-scroll {
        0%   { transform: translateX(0); }
        100% { transform: translateX(-50%); }
    }
    </style>
    <?php
}

add_action( 'storefront_content_top', 'ws_render_homepage_banner', 5 );
function ws_render_homepage_banner() {
    if ( ! is_front_page() ) {
        return;
    }
    $slides = get_option( 'ws_homepage_banner', array() );
    if ( ! is_array( $slides ) || empty( $slides ) ) {
        return;
    }
    ?>
    <div class="ws-banner-strip">
        <?php foreach ( $slides as $slide ) { ?>
        <?php
        $type    = isset( $slide['type'] )    ? $slide['type']    : 'image';
        $url     = isset( $slide['url'] )     ? $slide['url']     : '';
        $link    = isset( $slide['link'] )    ? $slide['link']    : '';
        $caption = isset( $slide['caption'] ) ? $slide['caption'] : '';

        if ( $url === '' ) {
            continue;
        }
        ?>
        <div class="ws-banner-slide">
            <?php if ( $link !== '' ) { ?>
            <a href="<?php echo esc_url( $link ); ?>">
            <?php } ?>

            <?php if ( $type === 'video' ) { ?>
            <video src="<?php echo esc_url( $url ); ?>" autoplay muted loop playsinline></video>
            <?php } elseif ( $type === 'video_url' ) {
    $yt_id = '';
    if ( preg_match( '~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/))([A-Za-z0-9_-]{11})~', $url, $yt_match ) ) {
        $yt_id = $yt_match[1];
    }
    if ( $yt_id !== '' ) { ?>
<iframe src="https://www.youtube-nocookie.com/embed/<?php echo esc_attr( $yt_id ); ?>?autoplay=1&amp;mute=1&amp;loop=1&amp;playlist=<?php echo esc_attr( $yt_id ); ?>&amp;controls=0&amp;modestbranding=1&amp;rel=0&amp;playsinline=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
<?php } else { ?>
<video src="<?php echo esc_url( $url ); ?>" autoplay muted loop playsinline></video>
<?php }
} else { ?>
            <img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $caption ); ?>" />
            <?php } ?>

            <?php if ( $caption !== '' ) { ?>
            <div class="ws-slide-caption"><?php echo esc_html( $caption ); ?></div>
            <?php } ?>

            <?php if ( $link !== '' ) { ?>
            </a>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
    <style>
    .ws-banner-strip {
        display: flex;
        gap: 12px;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        box-sizing: border-box;
        width: 100%;
        padding: 0;
        margin: 12px 0 24px;
    }
    .ws-banner-strip::-webkit-scrollbar {
        display: none;
    }
    .ws-banner-slide {
        position: relative;
        flex: 0 0 100%;
        scroll-snap-align: start;
        overflow: hidden;
        max-height: 480px;
        min-height: 200px;
        background: #000;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 20px;
    }
    .ws-banner-slide img,
    .ws-banner-slide video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .ws-banner-slide > a {
        display: block;
        width: 100%;
        height: 100%;
        position: relative;
    }
    .ws-banner-slide > a img,
    .ws-banner-slide > a video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .ws-banner-slide iframe,
    .ws-banner-slide > a iframe {
        width: 100%;
        aspect-ratio: 16 / 9;
        height: auto;
        flex: none;
        border: 0;
        display: block;
        pointer-events: none;
    }
    .ws-slide-caption {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(transparent, rgba(0,0,0,0.65));
        color: #fff;
        font-size: 15px;
        font-weight: 600;
        padding: 20px 16px 14px;
        text-align: center;
    }
    </style>
    <?php
}
