<?php
/**
 * Plugin Name: Borbila PhotoCard Generator
 * Description: Generate and download 1080x1080 PhotoCards from Post and WooCommerce Product editor screens.
 * Version: 2.0.0
 * Author: Borbila
 * Author URI: https://www.borbila.com
 * Plugin URI: https://www.borbila.com
 * Text Domain: borbila-photocard-generator
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BORBILA_PHOTOCARD_VERSION', '2.0.0');
define('BORBILA_PHOTOCARD_FILE', __FILE__);
define('BORBILA_PHOTOCARD_DIR', plugin_dir_path(__FILE__));

require_once BORBILA_PHOTOCARD_DIR . 'includes/class-borbila-photocard-license.php';

Borbila_PhotoCard_License::register_hooks();

register_activation_hook(__FILE__, array('Borbila_PhotoCard_License', 'install'));
register_deactivation_hook(__FILE__, array('Borbila_PhotoCard_License', 'unschedule'));

final class Borbila_PhotoCard_Generator
{
    const HANDLE = 'borbila-photocard-generator';
    const OPTION_KEY = 'borbila_photocard_options';
    const SETTINGS_SLUG = 'borbila-photocard-generator';

    private $settings_hook = '';
    private $frontend_button_rendered = false;

    public function __construct()
    {
        add_action('admin_menu', array($this, 'register_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('add_meta_boxes', array($this, 'register_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links'));
        add_filter('the_content', array($this, 'prepend_frontend_button_to_content'), 9);
    }

    /**
     * @return string[]
     */
    private function get_supported_post_types()
    {
        if (!Borbila_PhotoCard_License::is_active()) {
            return array();
        }

        $options = $this->get_options();
        $post_types = array();

        if (!empty($options['enable_post'])) {
            $post_types[] = 'post';
        }

        if (!empty($options['enable_product']) && post_type_exists('product')) {
            $post_types[] = 'product';
        }

        return $post_types;
    }

    private function get_default_options()
    {
        return array(
            'selected_format'        => 'classic-red',
            'enable_post'            => 1,
            'enable_product'         => 1,
            'enable_frontend_button' => 1,
            'primary_color'          => '#d60000',
            'secondary_color'        => '#7a0000',
            'accent_color'           => '#ff2d2d',
            'top_background_color'   => '#fff0f0',
            'title_color'            => '#ffffff',
            'date_color'             => '#5a5a5a',
            'text_color'             => '#181818',
            'logo_color'             => '#181818',
            'logo_id'                => 0,
            'logo_url'               => '',
            'bottom_text'            => 'বিস্তারিত কমেন্টে',
            'caption_text'           => 'ছবি: সংগৃহীত',
            'section_label'          => 'News',
            'footer_left_text'       => '',
            'footer_right_text'      => '',
            'ad_text'                => '',
            'brand_url'              => home_url('/'),
            'facebook_url'           => '',
            'youtube_url'            => '',
            'instagram_url'          => '',
            'front_button_text'      => 'Download PhotoCard',
            'download_prefix'        => 'photocard',
            'date_format'            => 'j F Y',
            'active_tab'             => 'colors',
        );
    }

    private function get_design_presets()
    {
        return array(
            'classic-red' => array(
                'label'                => __('Angled Red News Card', 'borbila-photocard-generator'),
                'description'          => __('Logo/date header, angled photo frame, red headline body, and CTA footer.', 'borbila-photocard-generator'),
                'primary_color'        => '#d60000',
                'secondary_color'      => '#7a0000',
                'accent_color'         => '#ff2d2d',
                'top_background_color' => '#fff0f0',
                'title_color'          => '#ffffff',
                'date_color'           => '#3f3f46',
                'text_color'           => '#181818',
                'logo_color'           => '#181818',
            ),
            'fresh-blue' => array(
                'label'                => __('White Editorial Card', 'borbila-photocard-generator'),
                'description'          => __('Large top photo, clean white headline area, bottom date and website strip.', 'borbila-photocard-generator'),
                'primary_color'        => '#d32929',
                'secondary_color'      => '#e6e6e6',
                'accent_color'         => '#f44336',
                'top_background_color' => '#f3f3f3',
                'title_color'          => '#202020',
                'date_color'           => '#555555',
                'text_color'           => '#202020',
                'logo_color'           => '#d32929',
            ),
            'green-market' => array(
                'label'                => __('Dark Breaking News', 'borbila-photocard-generator'),
                'description'          => __('Dark red breaking-news header, photo cut, CTA pill, and bottom ad/url area.', 'borbila-photocard-generator'),
                'primary_color'        => '#e50914',
                'secondary_color'      => '#050000',
                'accent_color'         => '#ff3232',
                'top_background_color' => '#130000',
                'title_color'          => '#ffffff',
                'date_color'           => '#ffffff',
                'text_color'           => '#ffffff',
                'logo_color'           => '#ffffff',
            ),
            'dark-magazine' => array(
                'label'                => __('Social Footer TV Card', 'borbila-photocard-generator'),
                'description'          => __('Top photo, strong red title panel, social URL strip, and optional ad bar.', 'borbila-photocard-generator'),
                'primary_color'        => '#b40000',
                'secondary_color'      => '#6c0000',
                'accent_color'         => '#ffffff',
                'top_background_color' => '#ffffff',
                'title_color'          => '#ffffff',
                'date_color'           => '#ffffff',
                'text_color'           => '#ffffff',
                'logo_color'           => '#ffffff',
            ),
            'gold-frame' => array(
                'label'                => __('Red Caption Square', 'borbila-photocard-generator'),
                'description'          => __('Top image, center logo badge, deep red headline area, and footer CTA/url.', 'borbila-photocard-generator'),
                'primary_color'        => '#cf0000',
                'secondary_color'      => '#530000',
                'accent_color'         => '#ffef00',
                'top_background_color' => '#080000',
                'title_color'          => '#ffffff',
                'date_color'           => '#ffffff',
                'text_color'           => '#ffffff',
                'logo_color'           => '#ffffff',
            ),
        );
    }

    private function get_options()
    {
        $saved = get_option(self::OPTION_KEY, array());
        if (!is_array($saved)) {
            $saved = array();
        }

        return wp_parse_args($saved, $this->get_default_options());
    }

    private function sanitize_hex_or_default($value, $default)
    {
        $color = sanitize_hex_color($value);
        return $color ? $color : $default;
    }

    public function sanitize_options($input)
    {
        if (!Borbila_PhotoCard_License::is_active()) {
            add_settings_error(
                self::OPTION_KEY,
                'borbila_photocard_license_required',
                __('Settings were not changed. Activate your Borbila license first.', 'borbila-photocard-generator'),
                'error'
            );

            return $this->get_options();
        }

        $defaults = $this->get_default_options();
        $presets  = $this->get_design_presets();

        if (!is_array($input)) {
            $input = array();
        }

        $format = isset($input['selected_format']) ? sanitize_key($input['selected_format']) : $defaults['selected_format'];
        if (!isset($presets[$format])) {
            $format = $defaults['selected_format'];
        }

        $logo_id  = isset($input['logo_id']) ? absint($input['logo_id']) : 0;
        $logo_url = isset($input['logo_url']) ? esc_url_raw($input['logo_url']) : '';
        if ($logo_id) {
            $attachment_url = wp_get_attachment_image_url($logo_id, 'full');
            if ($attachment_url) {
                $logo_url = $attachment_url;
            }
        }

        $bottom_text       = isset($input['bottom_text']) ? sanitize_text_field($input['bottom_text']) : $defaults['bottom_text'];
        $caption_text      = isset($input['caption_text']) ? sanitize_text_field($input['caption_text']) : $defaults['caption_text'];
        $section_label     = isset($input['section_label']) ? sanitize_text_field($input['section_label']) : $defaults['section_label'];
        $footer_left_text  = isset($input['footer_left_text']) ? sanitize_text_field($input['footer_left_text']) : $defaults['footer_left_text'];
        $footer_right_text = isset($input['footer_right_text']) ? sanitize_text_field($input['footer_right_text']) : $defaults['footer_right_text'];
        $ad_text           = isset($input['ad_text']) ? sanitize_text_field($input['ad_text']) : $defaults['ad_text'];
        $front_button_text = isset($input['front_button_text']) ? sanitize_text_field($input['front_button_text']) : $defaults['front_button_text'];
        $date_format       = isset($input['date_format']) ? sanitize_text_field($input['date_format']) : $defaults['date_format'];
        $prefix            = isset($input['download_prefix']) ? sanitize_title($input['download_prefix']) : $defaults['download_prefix'];
        $active_tab        = isset($input['active_tab']) ? sanitize_key($input['active_tab']) : $defaults['active_tab'];
        $allowed_tabs      = array('colors', 'logo', 'text', 'social', 'visibility', 'formats');
        if (!in_array($active_tab, $allowed_tabs, true)) {
            $active_tab = $defaults['active_tab'];
        }

        return array(
            'selected_format'        => $format,
            'enable_post'            => !empty($input['enable_post']) ? 1 : 0,
            'enable_product'         => !empty($input['enable_product']) ? 1 : 0,
            'enable_frontend_button' => !empty($input['enable_frontend_button']) ? 1 : 0,
            'primary_color'          => $this->sanitize_hex_or_default(isset($input['primary_color']) ? $input['primary_color'] : '', $defaults['primary_color']),
            'secondary_color'        => $this->sanitize_hex_or_default(isset($input['secondary_color']) ? $input['secondary_color'] : '', $defaults['secondary_color']),
            'accent_color'           => $this->sanitize_hex_or_default(isset($input['accent_color']) ? $input['accent_color'] : '', $defaults['accent_color']),
            'top_background_color'   => $this->sanitize_hex_or_default(isset($input['top_background_color']) ? $input['top_background_color'] : '', $defaults['top_background_color']),
            'title_color'            => $this->sanitize_hex_or_default(isset($input['title_color']) ? $input['title_color'] : '', $defaults['title_color']),
            'date_color'             => $this->sanitize_hex_or_default(isset($input['date_color']) ? $input['date_color'] : '', $defaults['date_color']),
            'text_color'             => $this->sanitize_hex_or_default(isset($input['text_color']) ? $input['text_color'] : '', $defaults['text_color']),
            'logo_color'             => $this->sanitize_hex_or_default(isset($input['logo_color']) ? $input['logo_color'] : '', $defaults['logo_color']),
            'logo_id'                => $logo_id,
            'logo_url'               => $logo_url,
            'bottom_text'            => $bottom_text ? $bottom_text : $defaults['bottom_text'],
            'caption_text'           => $caption_text,
            'section_label'          => $section_label,
            'footer_left_text'       => $footer_left_text,
            'footer_right_text'      => $footer_right_text,
            'ad_text'                => $ad_text,
            'brand_url'              => isset($input['brand_url']) ? esc_url_raw($input['brand_url']) : $defaults['brand_url'],
            'facebook_url'           => isset($input['facebook_url']) ? esc_url_raw($input['facebook_url']) : $defaults['facebook_url'],
            'youtube_url'            => isset($input['youtube_url']) ? esc_url_raw($input['youtube_url']) : $defaults['youtube_url'],
            'instagram_url'          => isset($input['instagram_url']) ? esc_url_raw($input['instagram_url']) : $defaults['instagram_url'],
            'front_button_text'      => $front_button_text ? $front_button_text : $defaults['front_button_text'],
            'download_prefix'        => $prefix ? $prefix : $defaults['download_prefix'],
            'date_format'            => $date_format ? $date_format : $defaults['date_format'],
            'active_tab'             => $active_tab,
        );
    }

    public function register_settings()
    {
        register_setting(
            'borbila_photocard_settings',
            self::OPTION_KEY,
            array($this, 'sanitize_options')
        );
    }

    public function register_settings_page()
    {
        $this->settings_hook = add_menu_page(
            __('Borbila Photo Generator', 'borbila-photocard-generator'),
            __('Photo Generator', 'borbila-photocard-generator'),
            'manage_options',
            self::SETTINGS_SLUG,
            array($this, 'render_settings_page'),
            'dashicons-format-image',
            58
        );
    }

    public function add_plugin_action_links($links)
    {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('admin.php?page=' . self::SETTINGS_SLUG)),
            esc_html__('Settings', 'borbila-photocard-generator')
        );

        array_unshift($links, $settings_link);

        $license_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('admin.php?page=' . self::SETTINGS_SLUG . '&tab=license#tab-license')),
            esc_html__('License', 'borbila-photocard-generator')
        );

        array_unshift($links, $license_link);

        return $links;
    }

    public function register_meta_boxes()
    {
        foreach ($this->get_supported_post_types() as $post_type) {
            add_meta_box(
                'borbila_photocard_box',
                __('PhotoCard Downloader', 'borbila-photocard-generator'),
                array($this, 'render_meta_box'),
                $post_type,
                'side',
                'high'
            );
        }
    }

    private function is_settings_screen($hook)
    {
        return $hook === $this->settings_hook || $hook === 'toplevel_page_' . self::SETTINGS_SLUG;
    }

    public function enqueue_assets($hook)
    {
        $is_settings = $this->is_settings_screen($hook);
        $is_editor   = false;

        if (in_array($hook, array('post.php', 'post-new.php'), true)) {
            $screen = get_current_screen();
            $is_editor = $screen && in_array($screen->post_type, $this->get_supported_post_types(), true);
        }

        if (!$is_settings && !$is_editor) {
            return;
        }

        $this->enqueue_plugin_assets($is_settings);
    }

    public function enqueue_frontend_assets()
    {
        if (!Borbila_PhotoCard_License::is_active()) {
            return;
        }

        if (!is_singular()) {
            return;
        }

        $post_id = get_queried_object_id();
        if (!$post_id || !$this->should_show_frontend_button($post_id)) {
            return;
        }

        $this->enqueue_plugin_assets(false);
    }

    private function enqueue_plugin_assets($with_media)
    {
        if ($with_media) {
            wp_enqueue_media();
        }

        $css_path = plugin_dir_path(__FILE__) . 'assets/admin.css';
        $js_path  = plugin_dir_path(__FILE__) . 'assets/admin.js';

        wp_enqueue_style(
            self::HANDLE,
            plugin_dir_url(__FILE__) . 'assets/admin.css',
            array(),
            file_exists($css_path) ? (string) filemtime($css_path) : BORBILA_PHOTOCARD_VERSION
        );

        wp_enqueue_script(
            self::HANDLE,
            plugin_dir_url(__FILE__) . 'assets/admin.js',
            array(),
            file_exists($js_path) ? (string) filemtime($js_path) : BORBILA_PHOTOCARD_VERSION,
            true
        );

        wp_localize_script(self::HANDLE, 'borbilaPhotoCard', array(
            'settings'         => $this->get_public_settings(),
            'presets'          => $this->get_public_presets(),
            'loadingText'      => __('Generating...', 'borbila-photocard-generator'),
            'buttonText'       => __('Generate & Download PhotoCard (1080x1080)', 'borbila-photocard-generator'),
            'missingImageText' => __('Set a featured image first.', 'borbila-photocard-generator'),
            'errorText'        => __('PhotoCard তৈরি করা যায়নি। আবার চেষ্টা করুন।', 'borbila-photocard-generator'),
            'defaultCenterText'=> __('বিস্তারিত কমেন্টে', 'borbila-photocard-generator'),
            'downloadPrefix'   => __('photocard', 'borbila-photocard-generator'),
            'mediaTitle'       => __('Choose Borbila logo', 'borbila-photocard-generator'),
            'mediaButtonText'  => __('Use this logo', 'borbila-photocard-generator'),
            'licenseActive'    => Borbila_PhotoCard_License::is_active(),
            'licenseMessage'   => __('Activate your Borbila license to use PhotoCard Generator.', 'borbila-photocard-generator'),
        ));
    }

    private function get_public_presets()
    {
        $public = array();

        foreach ($this->get_design_presets() as $key => $preset) {
            $public[$key] = array(
                'label'              => $preset['label'],
                'primaryColor'       => $preset['primary_color'],
                'secondaryColor'     => $preset['secondary_color'],
                'accentColor'        => $preset['accent_color'],
                'topBackgroundColor' => $preset['top_background_color'],
                'titleColor'         => $preset['title_color'],
                'dateColor'          => $preset['date_color'],
                'textColor'          => $preset['text_color'],
                'logoColor'          => $preset['logo_color'],
            );
        }

        return $public;
    }

    private function get_public_settings()
    {
        $options = $this->get_options();

        return array(
            'format'                => $options['selected_format'],
            'primaryColor'          => $options['primary_color'],
            'secondaryColor'        => $options['secondary_color'],
            'accentColor'           => $options['accent_color'],
            'topBackgroundColor'    => $options['top_background_color'],
            'titleColor'            => $options['title_color'],
            'dateColor'             => $options['date_color'],
            'textColor'             => $options['text_color'],
            'logoColor'             => $options['logo_color'],
            'logoUrl'               => $this->get_logo_url(),
            'bottomText'            => $options['bottom_text'],
            'captionText'           => $options['caption_text'],
            'sectionLabel'          => $options['section_label'],
            'footerLeftText'        => $options['footer_left_text'],
            'footerRightText'       => $options['footer_right_text'],
            'adText'                => $options['ad_text'],
            'brandUrl'              => $options['brand_url'],
            'facebookUrl'           => $options['facebook_url'],
            'youtubeUrl'            => $options['youtube_url'],
            'instagramUrl'          => $options['instagram_url'],
            'frontButtonText'       => $options['front_button_text'],
            'downloadPrefix'        => $options['download_prefix'],
            'frontendButtonEnabled' => !empty($options['enable_frontend_button']),
        );
    }

    private function get_logo_url()
    {
        $options = $this->get_options();
        if (!empty($options['logo_url'])) {
            return esc_url_raw($options['logo_url']);
        }

        // Prefer Redux theme logo first for themes using custom logo fields in options panel.
        $redux_options = get_option('redux_demo');
        if (is_array($redux_options) && !empty($redux_options['logo']) && is_array($redux_options['logo']) && !empty($redux_options['logo']['url'])) {
            return esc_url_raw($redux_options['logo']['url']);
        }

        $custom_logo_id = (int) get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $custom_logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
            if (!empty($custom_logo_url)) {
                return $custom_logo_url;
            }
        }

        $site_icon_id = (int) get_option('site_icon');
        if ($site_icon_id) {
            $site_icon_url = wp_get_attachment_image_url($site_icon_id, 'full');
            if (!empty($site_icon_url)) {
                return $site_icon_url;
            }
        }

        return '';
    }

    private function get_card_image_url($post)
    {
        $featured_id = get_post_thumbnail_id($post);
        if ($featured_id) {
            $featured_url = wp_get_attachment_image_url($featured_id, 'full');
            if ($featured_url) {
                return $featured_url;
            }
        }

        if ('product' === get_post_type($post) && function_exists('wc_get_product')) {
            $product = wc_get_product($post->ID);
            if ($product && $product->get_image_id()) {
                $product_image = wp_get_attachment_image_url($product->get_image_id(), 'full');
                if ($product_image) {
                    return $product_image;
                }
            }
        }

        return '';
    }

    private function get_card_data($post)
    {
        $options   = $this->get_options();
        $brand_url = !empty($options['brand_url']) ? $options['brand_url'] : home_url('/');
        $domain    = wp_parse_url($brand_url, PHP_URL_HOST);
        $post_date = get_post_datetime($post);

        if (!$domain) {
            $domain = wp_parse_url(home_url('/'), PHP_URL_HOST);
        }

        $domain = $domain ? preg_replace('#^www\.#i', '', $domain) : '';

        return array(
            'title'        => get_the_title($post),
            'image'        => $this->get_card_image_url($post),
            'logo'         => $this->get_logo_url(),
            'date'         => $post_date instanceof \DateTimeInterface ? $post_date->format($options['date_format']) : '',
            'domain'       => $domain,
            'site_name'    => get_bloginfo('name'),
            'format_label' => '',
        );
    }

    private function is_post_type_enabled($post_type)
    {
        $options = $this->get_options();

        if ('post' === $post_type) {
            return !empty($options['enable_post']);
        }

        if ('product' === $post_type) {
            return !empty($options['enable_product']) && post_type_exists('product');
        }

        return false;
    }

    private function should_show_frontend_button($post_id)
    {
        if (is_admin() || !Borbila_PhotoCard_License::is_active()) {
            return false;
        }

        $options = $this->get_options();
        if (empty($options['enable_frontend_button'])) {
            return false;
        }

        $post = get_post($post_id);
        if (!$post || !$this->is_post_type_enabled($post->post_type)) {
            return false;
        }

        if (!is_singular($post->post_type) || (int) get_queried_object_id() !== (int) $post_id) {
            return false;
        }

        return (bool) $this->get_card_image_url($post);
    }

    private function render_frontend_button_html($post)
    {
        if (!Borbila_PhotoCard_License::is_active()) {
            return '';
        }

        $options = $this->get_options();
        $data    = $this->get_card_data($post);

        if (empty($data['image'])) {
            return '';
        }

        ob_start();
        ?>
        <span class="borbila-photocard-box borbila-photocard-frontend"
             data-title="<?php echo esc_attr($data['title']); ?>"
             data-image="<?php echo esc_url($data['image']); ?>"
             data-logo="<?php echo esc_url($data['logo']); ?>"
             data-date="<?php echo esc_attr($data['date']); ?>"
             data-domain="<?php echo esc_attr($data['domain']); ?>"
             data-site-name="<?php echo esc_attr($data['site_name']); ?>">
            <button type="button"
                    class="borbila-photocard-front-button borbila-photocard-download"
                    style="--borbila-primary: <?php echo esc_attr($options['primary_color']); ?>; --borbila-secondary: <?php echo esc_attr($options['secondary_color']); ?>;">
                <svg class="borbila-photocard-front-button-icon" viewBox="0 0 24 20" aria-hidden="true" focusable="false">
                    <path d="M8.1 2.2 9.7 0h4.6l1.6 2.2H20c2.2 0 4 1.8 4 4V16c0 2.2-1.8 4-4 4H4c-2.2 0-4-1.8-4-4V6.2c0-2.2 1.8-4 4-4h4.1ZM12 16.2a5.3 5.3 0 1 0 0-10.6 5.3 5.3 0 0 0 0 10.6Zm0-2.7a2.6 2.6 0 1 1 0-5.2 2.6 2.6 0 0 1 0 5.2Z" />
                </svg>
                <span class="borbila-photocard-front-button-text"><?php echo esc_html($options['front_button_text']); ?></span>
            </button>
        </span>
        <?php

        return ob_get_clean();
    }

    public function prepend_frontend_button_to_content($content)
    {
        if ($this->frontend_button_rendered || !is_singular() || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        $post_id = get_queried_object_id();
        if (!$post_id || !$this->should_show_frontend_button($post_id)) {
            return $content;
        }

        $post = get_post($post_id);
        if (!$post) {
            return $content;
        }

        $this->frontend_button_rendered = true;

        return $this->render_frontend_button_html($post) . $content;
    }

    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $options        = $this->get_options();
        $presets        = $this->get_design_presets();
        $license_active = Borbila_PhotoCard_License::is_active();
        $initial_tab    = isset($_GET['tab']) && 'license' === sanitize_key(wp_unslash($_GET['tab'])) ? 'license' : $options['active_tab'];
        ?>
        <div class="wrap borbila-photocard-admin-page" data-default-tab="<?php echo esc_attr($initial_tab); ?>">
            <header class="borbila-admin-header">
                <div class="borbila-admin-brand">
                    <span class="dashicons dashicons-format-image"></span>
                    <div>
                        <span class="borbila-admin-kicker"><?php esc_html_e('BORBILA CREATIVE TOOLS', 'borbila-photocard-generator'); ?></span>
                        <h1><?php esc_html_e('PhotoCard Generator', 'borbila-photocard-generator'); ?></h1>
                        <p><?php esc_html_e('Create polished 1080×1080 social cards from WordPress posts and WooCommerce products.', 'borbila-photocard-generator'); ?></p>
                    </div>
                </div>
                <span class="borbila-license-pill <?php echo $license_active ? 'is-active' : 'is-inactive'; ?>">
                    <span class="dashicons <?php echo $license_active ? 'dashicons-shield-alt' : 'dashicons-lock'; ?>"></span>
                    <?php echo $license_active ? esc_html__('Licensed', 'borbila-photocard-generator') : esc_html__('License required', 'borbila-photocard-generator'); ?>
                </span>
            </header>
            <?php settings_errors(self::OPTION_KEY); ?>

            <div class="borbila-photocard-dashboard" data-license-active="<?php echo $license_active ? '1' : '0'; ?>">
                <nav class="borbila-admin-tabs" role="tablist" aria-label="<?php esc_attr_e('PhotoCard settings tabs', 'borbila-photocard-generator'); ?>">
                    <span class="borbila-tab-caption"><?php esc_html_e('Settings', 'borbila-photocard-generator'); ?></span>
                    <button type="button" class="borbila-admin-tab" data-tab="colors" role="tab" aria-selected="false"><span class="dashicons dashicons-art"></span><?php esc_html_e('Colors', 'borbila-photocard-generator'); ?></button>
                    <button type="button" class="borbila-admin-tab" data-tab="logo" role="tab" aria-selected="false"><span class="dashicons dashicons-format-image"></span><?php esc_html_e('Logo', 'borbila-photocard-generator'); ?></button>
                    <button type="button" class="borbila-admin-tab" data-tab="text" role="tab" aria-selected="false"><span class="dashicons dashicons-edit"></span><?php esc_html_e('Text', 'borbila-photocard-generator'); ?></button>
                    <button type="button" class="borbila-admin-tab" data-tab="social" role="tab" aria-selected="false"><span class="dashicons dashicons-share"></span><?php esc_html_e('URL & Social', 'borbila-photocard-generator'); ?></button>
                    <button type="button" class="borbila-admin-tab" data-tab="visibility" role="tab" aria-selected="false"><span class="dashicons dashicons-visibility"></span><?php esc_html_e('Visibility', 'borbila-photocard-generator'); ?></button>
                    <button type="button" class="borbila-admin-tab" data-tab="formats" role="tab" aria-selected="false"><span class="dashicons dashicons-screenoptions"></span><?php esc_html_e('Ready Formats', 'borbila-photocard-generator'); ?></button>
                    <span class="borbila-tab-divider"></span>
                    <button type="button" class="borbila-admin-tab borbila-license-tab" data-tab="license" role="tab" aria-selected="false"><span class="dashicons dashicons-lock"></span><?php esc_html_e('License', 'borbila-photocard-generator'); ?><em><?php echo $license_active ? esc_html__('Active', 'borbila-photocard-generator') : esc_html__('Required', 'borbila-photocard-generator'); ?></em></button>
                </nav>

                <div class="borbila-dashboard-content">
                <form method="post" action="options.php" class="borbila-photocard-settings <?php echo $license_active ? '' : 'is-license-locked'; ?>" data-settings-form>
                    <?php settings_fields('borbila_photocard_settings'); ?>
                    <input type="hidden" class="borbila-active-tab-input" name="<?php echo esc_attr(self::OPTION_KEY); ?>[active_tab]" value="<?php echo esc_attr($options['active_tab']); ?>">

                    <?php if (!$license_active) : ?>
                        <div class="borbila-settings-lock-banner">
                            <span class="dashicons dashicons-lock"></span>
                            <div><strong><?php esc_html_e('Settings are locked', 'borbila-photocard-generator'); ?></strong><small><?php esc_html_e('Activate your Borbila license to edit or save any PhotoCard setting.', 'borbila-photocard-generator'); ?></small></div>
                            <button type="button" class="button button-primary" data-open-license><?php esc_html_e('Activate License', 'borbila-photocard-generator'); ?></button>
                        </div>
                    <?php endif; ?>

                    <fieldset class="borbila-settings-fieldset" <?php disabled(!$license_active); ?>>

                <div class="borbila-admin-panel" data-panel="colors" role="tabpanel" hidden>
                    <div class="borbila-settings-grid">
                        <?php
                        $color_fields = array(
                            'primary_color'        => __('Primary Color', 'borbila-photocard-generator'),
                            'secondary_color'      => __('Secondary Color', 'borbila-photocard-generator'),
                            'accent_color'         => __('Accent Color', 'borbila-photocard-generator'),
                            'top_background_color' => __('Top Background', 'borbila-photocard-generator'),
                            'title_color'          => __('Title Color', 'borbila-photocard-generator'),
                            'date_color'           => __('Date Color', 'borbila-photocard-generator'),
                            'text_color'           => __('Text Color', 'borbila-photocard-generator'),
                            'logo_color'           => __('Logo Text Color', 'borbila-photocard-generator'),
                        );

                        foreach ($color_fields as $field => $label) :
                            ?>
                            <label class="borbila-field">
                                <span><?php echo esc_html($label); ?></span>
                                <input
                                    type="color"
                                    data-borbila-option="<?php echo esc_attr($field); ?>"
                                    name="<?php echo esc_attr(self::OPTION_KEY); ?>[<?php echo esc_attr($field); ?>]"
                                    value="<?php echo esc_attr($options[$field]); ?>">
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="description">
                        <?php esc_html_e('Ready Format tab থেকে format select করলে এই color গুলো auto-fill হবে, তারপর চাইলে manually adjust করতে পারবেন।', 'borbila-photocard-generator'); ?>
                    </p>
                </div>

                <div class="borbila-admin-panel" data-panel="logo" role="tabpanel" hidden>
                    <div class="borbila-logo-settings">
                        <div class="borbila-logo-preview" data-empty-text="<?php esc_attr_e('No custom logo selected', 'borbila-photocard-generator'); ?>">
                            <?php if (!empty($options['logo_url'])) : ?>
                                <img src="<?php echo esc_url($options['logo_url']); ?>" alt="<?php esc_attr_e('Selected logo', 'borbila-photocard-generator'); ?>">
                            <?php else : ?>
                                <span><?php esc_html_e('No custom logo selected', 'borbila-photocard-generator'); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="borbila-logo-controls">
                            <input
                                type="hidden"
                                class="borbila-logo-id"
                                name="<?php echo esc_attr(self::OPTION_KEY); ?>[logo_id]"
                                value="<?php echo esc_attr((string) $options['logo_id']); ?>">

                            <label class="borbila-field borbila-field-wide">
                                <span><?php esc_html_e('Logo URL', 'borbila-photocard-generator'); ?></span>
                                <input
                                    type="url"
                                    class="large-text borbila-logo-url"
                                    name="<?php echo esc_attr(self::OPTION_KEY); ?>[logo_url]"
                                    value="<?php echo esc_url($options['logo_url']); ?>"
                                    placeholder="<?php esc_attr_e('Upload/select a logo or paste logo URL', 'borbila-photocard-generator'); ?>">
                            </label>

                            <div class="borbila-button-row">
                                <button type="button" class="button button-secondary borbila-upload-logo"><?php esc_html_e('Upload / Select Logo', 'borbila-photocard-generator'); ?></button>
                                <button type="button" class="button borbila-remove-logo"><?php esc_html_e('Remove Logo', 'borbila-photocard-generator'); ?></button>
                            </div>

                            <p class="description">
                                <?php esc_html_e('Custom logo না দিলে theme logo, custom logo, site icon fallback হিসেবে use হবে। Logo image না থাকলে site name logo text হিসেবে আসবে।', 'borbila-photocard-generator'); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="borbila-admin-panel" data-panel="text" role="tabpanel" hidden>
                    <div class="borbila-settings-grid borbila-text-grid">
                        <label class="borbila-field borbila-field-wide">
                            <span><?php esc_html_e('Bottom CTA Text', 'borbila-photocard-generator'); ?></span>
                            <input
                                type="text"
                                name="<?php echo esc_attr(self::OPTION_KEY); ?>[bottom_text]"
                                value="<?php echo esc_attr($options['bottom_text']); ?>"
                                placeholder="<?php esc_attr_e('বিস্তারিত কমেন্টে', 'borbila-photocard-generator'); ?>">
                        </label>

                        <label class="borbila-field">
                            <span><?php esc_html_e('Download Prefix', 'borbila-photocard-generator'); ?></span>
                            <input
                                type="text"
                                name="<?php echo esc_attr(self::OPTION_KEY); ?>[download_prefix]"
                                value="<?php echo esc_attr($options['download_prefix']); ?>"
                                placeholder="photocard">
                        </label>

                        <label class="borbila-field">
                            <span><?php esc_html_e('Date Format', 'borbila-photocard-generator'); ?></span>
                            <input
                                type="text"
                                name="<?php echo esc_attr(self::OPTION_KEY); ?>[date_format]"
                                value="<?php echo esc_attr($options['date_format']); ?>"
                                placeholder="j F Y">
                        </label>

                        <label class="borbila-field">
                            <span><?php esc_html_e('Photo Caption', 'borbila-photocard-generator'); ?></span>
                            <input
                                type="text"
                                name="<?php echo esc_attr(self::OPTION_KEY); ?>[caption_text]"
                                value="<?php echo esc_attr($options['caption_text']); ?>"
                                placeholder="<?php esc_attr_e('ছবি: সংগৃহীত', 'borbila-photocard-generator'); ?>">
                        </label>

                        <label class="borbila-field">
                            <span><?php esc_html_e('Section / Badge Text', 'borbila-photocard-generator'); ?></span>
                            <input
                                type="text"
                                name="<?php echo esc_attr(self::OPTION_KEY); ?>[section_label]"
                                value="<?php echo esc_attr($options['section_label']); ?>"
                                placeholder="News">
                        </label>

                        <label class="borbila-field">
                            <span><?php esc_html_e('Footer Left Text', 'borbila-photocard-generator'); ?></span>
                            <input
                                type="text"
                                name="<?php echo esc_attr(self::OPTION_KEY); ?>[footer_left_text]"
                                value="<?php echo esc_attr($options['footer_left_text']); ?>"
                                placeholder="<?php esc_attr_e('বাংলাদেশ', 'borbila-photocard-generator'); ?>">
                        </label>

                        <label class="borbila-field">
                            <span><?php esc_html_e('Footer Right Text', 'borbila-photocard-generator'); ?></span>
                            <input
                                type="text"
                                name="<?php echo esc_attr(self::OPTION_KEY); ?>[footer_right_text]"
                                value="<?php echo esc_attr($options['footer_right_text']); ?>"
                                placeholder="<?php esc_attr_e('PhotoCard', 'borbila-photocard-generator'); ?>">
                        </label>

                        <label class="borbila-field borbila-field-wide">
                            <span><?php esc_html_e('Ad / Sponsor Text', 'borbila-photocard-generator'); ?></span>
                            <input
                                type="text"
                                name="<?php echo esc_attr(self::OPTION_KEY); ?>[ad_text]"
                                value="<?php echo esc_attr($options['ad_text']); ?>"
                                placeholder="<?php esc_attr_e('Optional sponsor/ad text shown in ad-style formats', 'borbila-photocard-generator'); ?>">
                        </label>

                        <label class="borbila-field">
                            <span><?php esc_html_e('Frontend Button Text', 'borbila-photocard-generator'); ?></span>
                            <input
                                type="text"
                                name="<?php echo esc_attr(self::OPTION_KEY); ?>[front_button_text]"
                                value="<?php echo esc_attr($options['front_button_text']); ?>"
                                placeholder="Download PhotoCard">
                        </label>
                    </div>
                    <p class="description">
                        <?php esc_html_e('Title, date, and image post/product editor থেকে dynamic আসবে। এখানে global label, caption, CTA, footer/ad text edit করা যাবে।', 'borbila-photocard-generator'); ?>
                    </p>
                </div>

                <div class="borbila-admin-panel" data-panel="social" role="tabpanel" hidden>
                    <div class="borbila-settings-grid">
                        <label class="borbila-field borbila-field-wide">
                            <span><?php esc_html_e('Website / Brand URL', 'borbila-photocard-generator'); ?></span>
                            <input
                                type="url"
                                name="<?php echo esc_attr(self::OPTION_KEY); ?>[brand_url]"
                                value="<?php echo esc_url($options['brand_url']); ?>"
                                placeholder="https://example.com">
                        </label>

                        <label class="borbila-field">
                            <span><?php esc_html_e('Facebook URL', 'borbila-photocard-generator'); ?></span>
                            <input
                                type="url"
                                name="<?php echo esc_attr(self::OPTION_KEY); ?>[facebook_url]"
                                value="<?php echo esc_url($options['facebook_url']); ?>"
                                placeholder="https://facebook.com/page">
                        </label>

                        <label class="borbila-field">
                            <span><?php esc_html_e('YouTube URL', 'borbila-photocard-generator'); ?></span>
                            <input
                                type="url"
                                name="<?php echo esc_attr(self::OPTION_KEY); ?>[youtube_url]"
                                value="<?php echo esc_url($options['youtube_url']); ?>"
                                placeholder="https://youtube.com/@channel">
                        </label>

                        <label class="borbila-field">
                            <span><?php esc_html_e('Instagram URL', 'borbila-photocard-generator'); ?></span>
                            <input
                                type="url"
                                name="<?php echo esc_attr(self::OPTION_KEY); ?>[instagram_url]"
                                value="<?php echo esc_url($options['instagram_url']); ?>"
                                placeholder="https://instagram.com/profile">
                        </label>
                    </div>
                    <p class="description">
                        <?php esc_html_e('এই URL/social fields card footer/social strip-এ দেখাবে। Blank রাখলে fallback হিসেবে site domain দেখাবে।', 'borbila-photocard-generator'); ?>
                    </p>
                </div>

                <div class="borbila-admin-panel" data-panel="visibility" role="tabpanel" hidden>
                    <div class="borbila-toggle-grid">
                        <label class="borbila-toggle-card">
                            <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[enable_post]" value="1" <?php checked(!empty($options['enable_post'])); ?>>
                            <span>
                                <strong><?php esc_html_e('Post PhotoCard On', 'borbila-photocard-generator'); ?></strong>
                                <small><?php esc_html_e('Post editor metabox and frontend article button enabled.', 'borbila-photocard-generator'); ?></small>
                            </span>
                        </label>

                        <label class="borbila-toggle-card">
                            <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[enable_product]" value="1" <?php checked(!empty($options['enable_product'])); ?>>
                            <span>
                                <strong><?php esc_html_e('Product PhotoCard On', 'borbila-photocard-generator'); ?></strong>
                                <small><?php esc_html_e('WooCommerce product editor/front product button enabled when product post type exists.', 'borbila-photocard-generator'); ?></small>
                            </span>
                        </label>

                        <label class="borbila-toggle-card">
                            <input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[enable_frontend_button]" value="1" <?php checked(!empty($options['enable_frontend_button'])); ?>>
                            <span>
                                <strong><?php esc_html_e('Frontend Button On', 'borbila-photocard-generator'); ?></strong>
                                <small><?php esc_html_e('Shows a Download PhotoCard button below the single featured image.', 'borbila-photocard-generator'); ?></small>
                            </span>
                        </label>
                    </div>
                    <p class="description">
                        <?php esc_html_e('Post/Product off করলে ঐ post type-এ admin metabox এবং frontend button দুটোই বন্ধ থাকবে।', 'borbila-photocard-generator'); ?>
                    </p>
                </div>

                <div class="borbila-admin-panel" data-panel="formats" role="tabpanel" hidden>
                    <div class="borbila-format-grid">
                        <?php foreach ($presets as $preset_id => $preset) : ?>
                            <label class="borbila-format-card <?php echo $preset_id === $options['selected_format'] ? 'is-selected' : ''; ?>" data-borbila-preset="<?php echo esc_attr($preset_id); ?>">
                                <input
                                    type="radio"
                                    name="<?php echo esc_attr(self::OPTION_KEY); ?>[selected_format]"
                                    value="<?php echo esc_attr($preset_id); ?>"
                                    <?php checked($options['selected_format'], $preset_id); ?>>

                                <span class="borbila-format-preview" style="--primary: <?php echo esc_attr($preset['primary_color']); ?>; --secondary: <?php echo esc_attr($preset['secondary_color']); ?>; --accent: <?php echo esc_attr($preset['accent_color']); ?>; --top-bg: <?php echo esc_attr($preset['top_background_color']); ?>;">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </span>

                                <strong><?php echo esc_html($preset['label']); ?></strong>
                                <small><?php echo esc_html($preset['description']); ?></small>

                                <span class="borbila-swatch-row" aria-hidden="true">
                                    <i style="background: <?php echo esc_attr($preset['primary_color']); ?>"></i>
                                    <i style="background: <?php echo esc_attr($preset['secondary_color']); ?>"></i>
                                    <i style="background: <?php echo esc_attr($preset['accent_color']); ?>"></i>
                                    <i style="background: <?php echo esc_attr($preset['top_background_color']); ?>"></i>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="description">
                        <?php esc_html_e('Format select করলে layout change হবে এবং preset color গুলো Color tab-এ বসবে। Save Changes চাপলে post/product editor generator এই setting use করবে।', 'borbila-photocard-generator'); ?>
                    </p>
                </div>

                    <div class="borbila-savebar">
                        <span><span class="dashicons <?php echo $license_active ? 'dashicons-saved' : 'dashicons-lock'; ?>"></span><?php echo $license_active ? esc_html__('Save your changes when ready.', 'borbila-photocard-generator') : esc_html__('License activation is required before saving.', 'borbila-photocard-generator'); ?></span>
                        <?php submit_button(__('Save PhotoCard Settings', 'borbila-photocard-generator'), 'primary', 'submit', false, $license_active ? array() : array('disabled' => 'disabled')); ?>
                    </div>
                    </fieldset>
                </form>

                <section id="tab-license" class="borbila-admin-panel borbila-license-panel" data-panel="license" role="tabpanel" hidden>
                    <?php $this->render_license_panel(); ?>
                </section>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_license_panel()
    {
        $license = Borbila_PhotoCard_License::get();
        $active  = Borbila_PhotoCard_License::is_active();
        $key     = 'borbila_photocard_license_notice_' . get_current_user_id();
        $notice  = get_transient($key);

        if (false !== $notice) {
            delete_transient($key);
        }

        $checked = !empty($license['last_checked'])
            ? wp_date(get_option('date_format') . ' · ' . get_option('time_format'), strtotime($license['last_checked']))
            : __('Never', 'borbila-photocard-generator');
        ?>
        <?php if (is_array($notice)) : ?>
            <div class="borbila-license-notice <?php echo !empty($notice['success']) ? 'is-success' : 'is-error'; ?>">
                <span class="dashicons <?php echo !empty($notice['success']) ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                <?php echo esc_html(isset($notice['message']) ? $notice['message'] : ''); ?>
            </div>
        <?php endif; ?>

        <section class="borbila-license-status <?php echo $active ? 'is-active' : 'is-inactive'; ?>">
            <span class="borbila-license-status-icon"><span class="dashicons <?php echo $active ? 'dashicons-shield-alt' : 'dashicons-lock'; ?>"></span></span>
            <div>
                <span class="borbila-admin-kicker"><?php esc_html_e('BORBILA LICENSING', 'borbila-photocard-generator'); ?></span>
                <h2><?php echo $active ? esc_html__('Your license is active', 'borbila-photocard-generator') : esc_html__('Activate PhotoCard Generator', 'borbila-photocard-generator'); ?></h2>
                <p><?php echo $active ? esc_html__('All PhotoCard settings, editor tools and frontend downloads are unlocked.', 'borbila-photocard-generator') : esc_html__('Settings and PhotoCard tools remain read-only until activation.', 'borbila-photocard-generator'); ?></p>
            </div>
            <strong><?php echo $active ? esc_html__('ACTIVE', 'borbila-photocard-generator') : esc_html__('INACTIVE', 'borbila-photocard-generator'); ?></strong>
        </section>

        <div class="borbila-license-grid">
            <section class="borbila-license-card">
                <h2><?php esc_html_e('Manage License', 'borbila-photocard-generator'); ?></h2>
                <p><?php esc_html_e('Enter the license assigned to template ID “photocard”.', 'borbila-photocard-generator'); ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="borbila_photocard_license_action">
                    <?php wp_nonce_field('borbila_photocard_license_action', 'borbila_photocard_license_nonce'); ?>
                    <label for="borbila-photocard-license-key"><?php esc_html_e('License key', 'borbila-photocard-generator'); ?></label>
                    <div class="borbila-license-key-row">
                        <input id="borbila-photocard-license-key" name="license_key" type="text" value="<?php echo esc_attr(Borbila_PhotoCard_License::mask($license['license_key'])); ?>" placeholder="BRB-XXXX-XXXX-XXXX" autocomplete="off">
                        <button class="button button-primary" name="license_operation" value="activate"><?php esc_html_e('Activate License', 'borbila-photocard-generator'); ?></button>
                    </div>
                    <div class="borbila-license-actions">
                        <?php if ($active) : ?>
                            <button class="button" name="license_operation" value="check"><?php esc_html_e('Check License', 'borbila-photocard-generator'); ?></button>
                            <button class="button borbila-danger" name="license_operation" value="deactivate"><?php esc_html_e('Deactivate', 'borbila-photocard-generator'); ?></button>
                        <?php endif; ?>
                        <a class="button" href="https://borbila.com/my-account/licenses/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Get License', 'borbila-photocard-generator'); ?></a>
                    </div>
                </form>
            </section>

            <div class="borbila-license-meta">
                <div><span><?php esc_html_e('Website', 'borbila-photocard-generator'); ?></span><strong><?php echo esc_html(Borbila_PhotoCard_License::domain()); ?></strong></div>
                <div><span><?php esc_html_e('Template ID', 'borbila-photocard-generator'); ?></span><strong><?php echo esc_html(Borbila_PhotoCard_License::TEMPLATE_ID); ?></strong></div>
                <div><span><?php esc_html_e('Validity', 'borbila-photocard-generator'); ?></span><strong><?php echo $active ? esc_html__('Lifetime', 'borbila-photocard-generator') : '—'; ?></strong></div>
                <div><span><?php esc_html_e('Last checked', 'borbila-photocard-generator'); ?></span><strong><?php echo esc_html($checked); ?></strong></div>
            </div>
        </div>
        <?php
    }

    public function render_meta_box($post)
    {
        if (!Borbila_PhotoCard_License::is_active()) {
            echo '<div class="borbila-photocard-warning"><span class="dashicons dashicons-lock"></span> ' . esc_html__('Activate your Borbila license to use PhotoCard Generator.', 'borbila-photocard-generator') . '</div>';
            return;
        }

        $options      = $this->get_options();
        $presets      = $this->get_design_presets();
        $data         = $this->get_card_data($post);
        $format_label = isset($presets[$options['selected_format']]) ? $presets[$options['selected_format']]['label'] : $presets['classic-red']['label'];

        $is_disabled = empty($data['image']);
        ?>
        <div class="borbila-photocard-box"
             data-title="<?php echo esc_attr($data['title']); ?>"
             data-image="<?php echo esc_url($data['image']); ?>"
             data-logo="<?php echo esc_url($data['logo']); ?>"
             data-date="<?php echo esc_attr($data['date']); ?>"
             data-domain="<?php echo esc_attr($data['domain']); ?>"
             data-site-name="<?php echo esc_attr($data['site_name']); ?>">

            <?php if ($is_disabled) : ?>
                <p class="borbila-photocard-warning"><?php esc_html_e('Featured image না থাকলে PhotoCard generate হবে না।', 'borbila-photocard-generator'); ?></p>
            <?php endif; ?>

            <p class="borbila-photocard-fixed-text">
                <strong><?php esc_html_e('Design:', 'borbila-photocard-generator'); ?></strong>
                <?php echo esc_html($format_label); ?>
            </p>

            <p class="borbila-photocard-fixed-text">
                <strong><?php esc_html_e('Bottom Text:', 'borbila-photocard-generator'); ?></strong>
                <?php echo esc_html($options['bottom_text']); ?>
            </p>

            <button type="button"
                    class="button button-primary button-large widefat borbila-photocard-download"
                <?php disabled($is_disabled); ?>>
                <?php esc_html_e('Generate & Download PhotoCard (1080x1080)', 'borbila-photocard-generator'); ?>
            </button>

            <p class="description borbila-photocard-description">
                <?php esc_html_e('ক্লিক করলে এই post/product title + date + featured image দিয়ে selected format অনুযায়ী PhotoCard download হবে।', 'borbila-photocard-generator'); ?>
            </p>

            <p class="borbila-photocard-settings-link">
                <a href="<?php echo esc_url(admin_url('admin.php?page=' . self::SETTINGS_SLUG)); ?>"><?php esc_html_e('Edit PhotoCard Settings', 'borbila-photocard-generator'); ?></a>
            </p>
        </div>
        <?php
    }
}

new Borbila_PhotoCard_Generator();
