<?php
/**
 * Plugin Name: Secure Social Share Buttons
 * Description: A lightweight, secure social sharing plugin with customizable icons and shortcode support
 * Version: 1.0
 * Author: Gibi
 */

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

class CustomSocialShare {
    public function __construct() {
        // Add hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Register shortcode
        add_shortcode('social_share', array($this, 'shortcode_handler'));
        
        // Optional: Add filter for automatic append (can be enabled/disabled in settings)
        add_filter('the_content', array($this, 'maybe_append_share_buttons'));
    }

    public function enqueue_styles() {
        wp_enqueue_style('dashicons');
        wp_enqueue_style('custom-social-share', plugins_url('css/style.css', __FILE__));
    }

    public function get_share_urls($post_id = null) {
        if ($post_id === null) {
            $post_id = get_the_ID();
        }
        
        $url = urlencode(get_permalink($post_id));
        $title = urlencode(get_the_title($post_id));

        return array(
            'facebook' => "https://www.facebook.com/sharer/sharer.php?u={$url}",
            'twitter' => "https://twitter.com/intent/tweet?url={$url}&text={$title}",
            'reddit' => "https://reddit.com/submit?url={$url}&title={$title}",
            'pinterest' => "https://pinterest.com/pin/create/button/?url={$url}&description={$title}",
            'email' => "mailto:?subject={$title}&body={$url}",
            'instagram' => "https://www.instagram.com/",
            'twitch' => "https://www.twitch.tv/",
        );
    }

    public function generate_share_buttons($post_id = null) {
        if ($post_id === null) {
            $post_id = get_the_ID();
        }

        $share_urls = $this->get_share_urls($post_id);
        $icon_size = get_option('css_icon_size', '32');

        ob_start();
        ?>
        <div class="custom-social-share">
            <h4><?php echo esc_html__('Share this post:', 'custom-social-share'); ?></h4>
            <div class="share-buttons">
                <a href="<?php echo esc_url($share_urls['facebook']); ?>" target="_blank" rel="noopener noreferrer" class="share-button facebook">
                    <span class="dashicons dashicons-facebook-alt"></span>
                </a>
                <a href="<?php echo esc_url($share_urls['twitter']); ?>" target="_blank" rel="noopener noreferrer" class="share-button twitter">
                    <span class="dashicons dashicons-twitter"></span>
                </a>
                <a href="<?php echo esc_url($share_urls['reddit']); ?>" target="_blank" rel="noopener noreferrer" class="share-button reddit">
                    <span class="dashicons dashicons-reddit"></span>
                </a>
                <a href="<?php echo esc_url($share_urls['pinterest']); ?>" target="_blank" rel="noopener noreferrer" class="share-button pinterest">
                    <span class="dashicons dashicons-pinterest"></span>
                </a>
                <a href="<?php echo esc_url($share_urls['instagram']); ?>" target="_blank" rel="noopener noreferrer" class="share-button instagram">
                    <span class="dashicons dashicons-instagram"></span>
                </a>
                <a href="<?php echo esc_url($share_urls['twitch']); ?>" target="_blank" rel="noopener noreferrer" class="share-button twitch">
                    <span class="dashicons dashicons-twitch"></span>
                </a>
                <a href="<?php echo esc_url($share_urls['email']); ?>" class="share-button email">
                    <span class="dashicons dashicons-email"></span>
                </a>
                <button class="share-button copy-link" onclick="copyPageLink_<?php echo esc_attr($post_id); ?>()">
                    <span class="dashicons dashicons-admin-links"></span>
                </button>
            </div>
        </div>
        <script>
        function copyPageLink_<?php echo esc_js($post_id); ?>() {
            const url = '<?php echo esc_js(get_permalink($post_id)); ?>';
            navigator.clipboard.writeText(url).then(function() {
                alert('Link copied to clipboard!');
            }).catch(function(err) {
                console.error('Failed to copy link:', err);
            });
        }
        </script>
        <?php
        return ob_get_clean();
    }

    // Shortcode handler
    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'post_id' => get_the_ID(), // Default to current post
        ), $atts);

        $post_id = absint($atts['post_id']); // Sanitize post_id

        return $this->generate_share_buttons($post_id);
    }

    // Optional automatic append to content
    public function maybe_append_share_buttons($content) {
        if (!is_single() || !get_option('css_auto_append', true)) {
            return $content;
        }

        return $content . $this->generate_share_buttons();
    }

    public function add_admin_menu() {
        add_options_page(
            'Social Share Settings',
            'Social Share',
            'manage_options',
            'custom-social-share',
            array($this, 'settings_page')
        );
    }

    public function register_settings() {
        register_setting('css_settings', 'css_icon_size', array(
            'sanitize_callback' => 'absint', // Ensure icon size is a positive integer
        ));
        register_setting('css_settings', 'css_auto_append', array(
            'sanitize_callback' => 'boolval', // Ensure auto append is a boolean
        ));
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        wp_nonce_field('css_settings_nonce', 'css_settings_nonce');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Social Share Settings', 'custom-social-share'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('css_settings');
                do_settings_sections('css_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Icon Size (px)</th>
                        <td>
                            <input type="number" name="css_icon_size" value="<?php echo esc_attr(get_option('css_icon_size', '32')); ?>" min="16" max="64">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Automatically append to posts</th>
                        <td>
                            <input type="checkbox" name="css_auto_append" value="1" <?php checked(get_option('css_auto_append', true)); ?>>
                            <span class="description">If unchecked, use shortcode or manual placement only</span>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <div class="usage-guide" style="margin-top: 2em; padding: 1em; background: #fff; border: 1px solid #ccc;">
                <h2>Usage Guide</h2>
                <p>You can display the social share buttons in three ways:</p>
                <ol>
                    <li>Automatically at the end of posts (toggle with setting above)</li>
                    <li>Using the shortcode: <code>[social_share]</code></li>
                    <li>For a specific post: <code>[social_share post_id="123"]</code></li>
                </ol>
                <p>For PHP template files, you can also use:</p>
                <code>
                    &lt;?php 
                    if (class_exists('CustomSocialShare')) {
                        $social_share = new CustomSocialShare();
                        echo $social_share->generate_share_buttons();
                    }
                    ?&gt;
                </code>
            </div>
        </div>
        <?php
    }
}

// Initialize the plugin
new CustomSocialShare();