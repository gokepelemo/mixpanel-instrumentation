<?php
/*
Plugin Name: Mixpanel Instrumentation
Plugin URI: https://github.com/gokepelemo/mixpanel-instrumentation
Description: Adds Mixpanel analytics and optional session replay to your WordPress site frontend. Multisite compatible with network admin controls and site-specific overrides. Supports tracking pageviews, all JS events, or specific JS events.
Version: 1.0.0
Author: Goke Pelemo
Author URI: https://github.com/gokepelemo
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: mixpanel-instrumentation
Domain Path: /languages
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.2
Tags: analytics, mixpanel, session replay, multisite, tracking

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

/**
 * Mixpanel Instrumentation Plugin
 *
 * Adds Mixpanel analytics and optional session replay to WordPress frontend.
 * Multisite compatible: network admin can enable for all/specific sites and allow site-specific overrides.
 * Tracking options: pageviews, all JS events, or specific JS events (comma separated).
 *
 * @package MixpanelInstrumentation
 * @author Goke Pelemo
 * @license GPL-2.0-or-later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
if (!defined('MIXPANEL_PLUGIN_VERSION')) {
    define('MIXPANEL_PLUGIN_VERSION', '1.0.0');
    define('MIXPANEL_PLUGIN_URL', plugin_dir_url(__FILE__));
    define('MIXPANEL_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

/**
 * Plugin initialization
 */
class MixpanelInstrumentation {
    
    /**
     * Plugin options array
     */
    private static $options = [
        'mixpanel_token',
        'mixpanel_session_replay',
        'mixpanel_tracking_mode',
        'mixpanel_specific_events',
        'mixpanel_override_network',
        'mixpanel_wp_performance_tracking'
    ];
    
    /**
     * Network options array
     */
    private static $network_options = [
        'mixpanel_network_enabled',
        'mixpanel_network_sites',
        'mixpanel_network_allow_override'
    ];
    
    /**
     * Performance tracking data
     */
    private static $performance_data = [];
    
    /**
     * WordPress core functions to track
     */
    private static $wp_functions_to_track = [
        'wp_query',
        'get_posts',
        'get_users',
        'get_terms',
        'wp_get_nav_menus',
        'get_option',
        'update_option',
        'wp_cache_get',
        'wp_cache_set'
    ];
    
    /**
     * Initialize the plugin
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('wp_head', [__CLASS__, 'inject_tracking_code']);
        
        if (is_multisite()) {
            add_action('network_admin_menu', [__CLASS__, 'add_network_admin_menu']);
        }
        
        // Initialize WordPress performance tracking
        self::init_wp_performance_tracking();
    }
    
    /**
     * Add site admin menu page
     */
    public static function add_admin_menu() {
        add_options_page(
            'Mixpanel Instrumentation',
            'Mixpanel Instrumentation',
            'manage_options',
            'mixpanel-instrumentation',
            [__CLASS__, 'settings_page']
        );
    }
    
    /**
     * Add network admin menu page
     */
    public static function add_network_admin_menu() {
        add_submenu_page(
            'settings.php',
            'Mixpanel Instrumentation Network Settings',
            'Mixpanel Instrumentation',
            'manage_network_options',
            'mixpanel-instrumentation-network',
            [__CLASS__, 'network_settings_page']
        );
    }
    
    /**
     * Register plugin settings
     */
    public static function register_settings() {
        foreach (self::$options as $option) {
            register_setting('mixpanel_instrumentation', $option);
        }
    }
    
    /**
     * Check if plugin should be enabled for current site
     */
    private static function is_enabled_for_site() {
        if (!is_multisite()) {
            return true;
        }
        
        $network_enabled = get_site_option('mixpanel_network_enabled', 'none');
        $network_sites = get_site_option('mixpanel_network_sites', '');
        $site_id = get_current_blog_id();
        
        switch ($network_enabled) {
            case 'none':
                return false;
            case 'all':
                return true;
            case 'specific':
                $ids = array_filter(array_map('trim', explode(',', $network_sites)));
                return in_array($site_id, $ids);
            default:
                return false;
        }
    }
    
    /**
     * Get effective settings (network or site)
     */
    private static function get_effective_settings() {
        $network_allow_override = is_multisite() ? get_site_option('mixpanel_network_allow_override', 0) : 0;
        $override_network = is_multisite() ? get_option('mixpanel_override_network', 0) : 0;
        $use_site_settings = ($network_allow_override && $override_network);
        
        return [
            'token' => $use_site_settings ? get_option('mixpanel_token') : (is_multisite() ? get_site_option('mixpanel_token') : get_option('mixpanel_token')),
            'session_replay' => $use_site_settings ? get_option('mixpanel_session_replay') : (is_multisite() ? get_site_option('mixpanel_session_replay') : get_option('mixpanel_session_replay')),
            'tracking_mode' => $use_site_settings ? get_option('mixpanel_tracking_mode', 'pageviews') : (is_multisite() ? get_site_option('mixpanel_tracking_mode', 'pageviews') : get_option('mixpanel_tracking_mode', 'pageviews')),
            'specific_events' => $use_site_settings ? get_option('mixpanel_specific_events', '') : (is_multisite() ? get_site_option('mixpanel_specific_events', '') : get_option('mixpanel_specific_events', '')),
            'wp_performance_tracking' => $use_site_settings ? get_option('mixpanel_wp_performance_tracking', 0) : (is_multisite() ? get_site_option('mixpanel_wp_performance_tracking', 0) : get_option('mixpanel_wp_performance_tracking', 0))
        ];
    }
    
    /**
     * Get current user data for Mixpanel identification
     */
    private static function get_user_data() {
        if (!is_user_logged_in()) {
            return null;
        }
        
        $user = wp_get_current_user();
        
        // Prepare user properties for people.set (user profile)
        $properties = array(
            '$email' => $user->user_email,
            '$name' => $user->display_name,
            '$username' => $user->user_login,
            '$created' => $user->user_registered,
            'user_id' => $user->ID,
            'user_roles' => $user->roles,
            'user_nicename' => $user->user_nicename,
            'user_url' => $user->user_url
        );
        
        // Add custom fields if they exist
        $meta_fields = array('first_name', 'last_name', 'nickname', 'description');
        foreach ($meta_fields as $field) {
            $value = get_user_meta($user->ID, $field, true);
            if (!empty($value)) {
                $properties[$field] = $value;
            }
        }
        
        // Prepare event properties (will be sent with every event)
        $event_properties = array(
            'user_id' => $user->ID,
            'user_type' => 'logged_in',
            'user_roles' => implode(', ', $user->roles)
        );
        
        // Add site-specific data for multisite
        if (is_multisite()) {
            $event_properties['site_id'] = get_current_blog_id();
            $event_properties['site_url'] = get_site_url();
        }
        
        return array(
            'user_id' => $user->ID,
            'properties' => $properties,
            'event_properties' => $event_properties
        );
    }
    
    /**
     * Inject Mixpanel tracking code
     */
    public static function inject_tracking_code() {
        if (!self::is_enabled_for_site()) {
            return;
        }
        
        $settings = self::get_effective_settings();
        
        if (empty($settings['token'])) {
            return;
        }
        
        ?>
        <!-- Mixpanel Analytics -->
        <script type="text/javascript">
        (function(f,b){if(!b.__SV){var a,e,i,g;window.mixpanel=b;b._i=[];b.init=function(a,e,d){function f(b,h){var a=h.split(".");2==a.length&&(b=b[a[0]],h=a[1]);b[h]=function(){b.push([h].concat(Array.prototype.slice.call(arguments,0)))}}var c=b;"undefined"!==typeof d?c=b[d]=[]:d="mixpanel";c.people=c.people||[];c.toString=function(b){var a="mixpanel";"mixpanel"!==d&&(a+="."+d);b||(a+=" (stub)");return a};c.people.toString=function(){return c.toString(1)+".people (stub)"};i="disable time_event track track_pageview track_links track_forms register register_once alias unregister identify name_tag set_config reset people.set people.set_once people.unset people.increment people.append people.union people.track_charge people.clear_charges people.delete_user".split(" ");for(g=0;g<i.length;g++)f(c,i[g]);b._i.push([a,e,d])};b.__SV=1.2;a=f.createElement("script");a.type="text/javascript";a.async=!0;a.src="https://cdn.mxpnl.com/libs/mixpanel-2-latest.min.js";e=f.getElementsByTagName("script")[0];e.parentNode.insertBefore(a,e)}})(document,window.mixpanel||[]);
        
        <?php
        // Configure Mixpanel initialization based on tracking mode
        $tracking_mode = $settings['tracking_mode'];
        $autocapture_config = [];
        
        if ($tracking_mode === 'pageviews') {
            // Only track pageviews
            $autocapture_config = [
                'pageview' => 'full-url',
                'click' => false,
                'input' => false,
                'rage_click' => false,
                'scroll' => false,
                'submit' => false,
                'capture_text_content' => false
            ];
        } elseif ($tracking_mode === 'all') {
            // Track all events with full autocapture
            $autocapture_config = [
                'pageview' => 'full-url',
                'click' => true,
                'input' => true,
                'rage_click' => true,
                'scroll' => true,
                'submit' => true,
                'capture_text_content' => true
            ];
        } elseif ($tracking_mode === 'specific') {
            // Parse specific events and configure autocapture accordingly
            $specific_events = array_filter(array_map('trim', explode(',', $settings['specific_events'])));
            $autocapture_config = [
                'pageview' => in_array('pageview', $specific_events) ? 'full-url' : false,
                'click' => in_array('click', $specific_events),
                'input' => in_array('input', $specific_events),
                'rage_click' => in_array('rage_click', $specific_events),
                'scroll' => in_array('scroll', $specific_events),
                'submit' => in_array('submit', $specific_events),
                'capture_text_content' => in_array('capture_text_content', $specific_events)
            ];
        }
        ?>
        
        // Initialize Mixpanel with autocapture configuration
        mixpanel.init("<?php echo esc_js($settings['token']); ?>", {
            autocapture: <?php echo wp_json_encode($autocapture_config); ?>,
            persistence: "localStorage"
        });
        
        <?php
        // Get user data for identification
        $user_data = self::get_user_data();
        if ($user_data): ?>
        // Identify logged-in user
        mixpanel.identify("<?php echo esc_js($user_data['user_id']); ?>");
        
        // Set user properties
        mixpanel.people.set(<?php echo wp_json_encode($user_data['properties']); ?>);
        
        // Register user properties for all events
        mixpanel.register(<?php echo wp_json_encode($user_data['event_properties']); ?>);
        <?php endif; ?>
        </script>
        <?php
        
        if ($settings['session_replay']) {
            ?>
            <!-- Mixpanel Session Replay -->
            <script src="https://cdn.mxpnl.com/libs/replay-js/1.0.0/replay.min.js" async></script>
            <script type="text/javascript">
                document.addEventListener('DOMContentLoaded', function() {
                    if (window.mixpanel && window.mixpanel.replay) {
                        mixpanel.replay.init();
                    }
                });
            </script>
            <?php
        }
    }
    
    /**
     * Site settings page
     */
    public static function settings_page() {
        // Check multisite permissions
        $network_enabled = is_multisite() ? get_site_option('mixpanel_network_enabled', 'none') : 'none';
        $network_sites = is_multisite() ? get_site_option('mixpanel_network_sites', '') : '';
        $network_allow_override = is_multisite() ? get_site_option('mixpanel_network_allow_override', 0) : 0;
        $site_id = is_multisite() ? get_current_blog_id() : null;
        $show_settings = true;
        
        if (is_multisite()) {
            switch ($network_enabled) {
                case 'none':
                    $show_settings = false;
                    break;
                case 'all':
                    $show_settings = (bool)$network_allow_override;
                    break;
                case 'specific':
                    $ids = array_filter(array_map('trim', explode(',', $network_sites)));
                    $show_settings = in_array($site_id, $ids) ? (bool)$network_allow_override : false;
                    break;
            }
        }
        
        $tracking_mode = get_option('mixpanel_tracking_mode', 'pageviews');
        $specific_events = get_option('mixpanel_specific_events', '');
        $override_network = get_option('mixpanel_override_network', 0);
        ?>
        <div class="wrap">
            <h1>Mixpanel Instrumentation Settings</h1>
            <?php if ($show_settings): ?>
            <form method="post" action="options.php">
                <?php settings_fields('mixpanel_instrumentation'); ?>
                <?php do_settings_sections('mixpanel_instrumentation'); ?>
                <table class="form-table">
                    <?php if (is_multisite() && $network_allow_override): ?>
                    <tr valign="top">
                        <th scope="row">Override Network Settings</th>
                        <td><input type="checkbox" name="mixpanel_override_network" value="1" <?php checked($override_network, 1); ?> /> Enable site-specific settings</td>
                    </tr>
                    <?php endif; ?>
                    <tr valign="top">
                        <th scope="row">Mixpanel Token</th>
                        <td><input type="text" name="mixpanel_token" value="<?php echo esc_attr(get_option('mixpanel_token')); ?>" size="40" placeholder="Your Mixpanel project token" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Enable Session Replay</th>
                        <td><input type="checkbox" name="mixpanel_session_replay" value="1" <?php checked(1, get_option('mixpanel_session_replay')); ?> /> Enable Mixpanel session replay</td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Tracking Mode</th>
                        <td>
                            <select name="mixpanel_tracking_mode" id="mixpanel_tracking_mode">
                                <option value="pageviews" <?php selected($tracking_mode, 'pageviews'); ?>>Track Pageviews Only</option>
                                <option value="all" <?php selected($tracking_mode, 'all'); ?>>Track All Events (Full Autocapture)</option>
                                <option value="specific" <?php selected($tracking_mode, 'specific'); ?>>Track Specific Events</option>
                            </select>
                            <p class="description">Uses Mixpanel's autocapture feature for comprehensive event tracking without manual coding.</p>
                        </td>
                    </tr>
                    <tr valign="top" id="specific_events_row" style="<?php echo ($tracking_mode === 'specific') ? '' : 'display:none;'; ?>">
                        <th scope="row">Specific Events</th>
                        <td>
                            <input type="text" name="mixpanel_specific_events" value="<?php echo esc_attr($specific_events); ?>" size="60" placeholder="pageview,click,input,submit,scroll,rage_click" />
                            <p class="description">Available events: <strong>pageview</strong>, <strong>click</strong>, <strong>input</strong>, <strong>submit</strong>, <strong>scroll</strong>, <strong>rage_click</strong>, <strong>capture_text_content</strong></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">WordPress Performance Tracking</th>
                        <td>
                            <input type="checkbox" name="mixpanel_wp_performance_tracking" value="1" <?php checked(1, get_option('mixpanel_wp_performance_tracking')); ?> /> Track WordPress core function performance
                            <p class="description">Monitor page load times, database queries, memory usage, and WordPress hook execution times</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <script>
            document.getElementById('mixpanel_tracking_mode').addEventListener('change', function() {
                document.getElementById('specific_events_row').style.display = this.value === 'specific' ? '' : 'none';
            });
            </script>
            <?php else: ?>
            <p><strong>Notice:</strong> This plugin is disabled for this site by the network administrator.</p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Network settings page
     */
    public static function network_settings_page() {
        if (!current_user_can('manage_network_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
            check_admin_referer('mixpanel_instrumentation_network_settings');
            update_site_option('mixpanel_network_enabled', sanitize_text_field($_POST['mixpanel_network_enabled'] ?? 'none'));
            update_site_option('mixpanel_network_sites', sanitize_text_field($_POST['mixpanel_network_sites'] ?? ''));
            update_site_option('mixpanel_network_allow_override', !empty($_POST['mixpanel_network_allow_override']) ? 1 : 0);
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        
        $enabled = get_site_option('mixpanel_network_enabled', 'none');
        $sites = get_site_option('mixpanel_network_sites', '');
        $allow_override = get_site_option('mixpanel_network_allow_override', 0);
        ?>
        <div class="wrap">
            <h1>Mixpanel Instrumentation Network Settings</h1>
            <form method="post">
                <?php wp_nonce_field('mixpanel_instrumentation_network_settings'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Enable Mixpanel</th>
                        <td>
                            <select name="mixpanel_network_enabled" id="mixpanel_network_enabled">
                                <option value="none" <?php selected($enabled, 'none'); ?>>Disabled for all sites</option>
                                <option value="all" <?php selected($enabled, 'all'); ?>>Enabled for all sites</option>
                                <option value="specific" <?php selected($enabled, 'specific'); ?>>Enabled for specific sites</option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top" id="network_sites_row" style="<?php echo ($enabled === 'specific') ? '' : 'display:none;'; ?>">
                        <th scope="row">Site IDs</th>
                        <td>
                            <input type="text" name="mixpanel_network_sites" value="<?php echo esc_attr($sites); ?>" size="60" placeholder="1,2,3" />
                            <p class="description">Comma-separated list of site IDs to enable Mixpanel for</p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Allow Site-Specific Override</th>
                        <td><input type="checkbox" name="mixpanel_network_allow_override" value="1" <?php checked($allow_override, 1); ?> /> Allow individual sites to override these settings</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <script>
            document.getElementById('mixpanel_network_enabled').addEventListener('change', function() {
                document.getElementById('network_sites_row').style.display = this.value === 'specific' ? '' : 'none';
            });
            </script>
        </div>
        <?php
    }
    
    /**
     * Initialize WordPress performance tracking
     */
    public static function init_wp_performance_tracking() {
        $settings = self::get_effective_settings();
        $wp_performance_enabled = $settings['wp_performance_tracking'] ?? get_option('mixpanel_wp_performance_tracking', 0);
        
        if (!$wp_performance_enabled) {
            return;
        }
        
        // Track page load time
        add_action('wp_footer', [__CLASS__, 'track_page_performance'], 999);
        
        // Hook into WordPress core functions for performance measurement
        add_action('init', [__CLASS__, 'setup_wp_function_tracking'], 1);
    }
    
    /**
     * Setup WordPress core function tracking
     */
    public static function setup_wp_function_tracking() {
        // Track database queries
        add_filter('query', [__CLASS__, 'track_db_query_start']);
        add_action('wp_footer', [__CLASS__, 'track_db_query_stats'], 998);
        
        // Track WordPress hooks execution time
        add_action('all', [__CLASS__, 'track_hook_performance']);
    }
    
    /**
     * Track database query performance
     */
    public static function track_db_query_start($query) {
        if (!isset(self::$performance_data['db_queries'])) {
            self::$performance_data['db_queries'] = [];
        }
        
        $start_time = microtime(true);
        self::$performance_data['db_queries'][] = [
            'query' => substr($query, 0, 100), // First 100 chars for identification
            'start_time' => $start_time
        ];
        
        return $query;
    }
    
    /**
     * Track database query statistics
     */
    public static function track_db_query_stats() {
        global $wpdb;
        
        if (!empty($wpdb->queries)) {
            $total_time = 0;
            foreach ($wpdb->queries as $query) {
                $total_time += (float)$query[1];
            }
            
            self::$performance_data['db_stats'] = [
                'total_queries' => count($wpdb->queries),
                'total_time' => $total_time,
                'slow_queries' => count(array_filter($wpdb->queries, function($q) { return $q[1] > 0.05; }))
            ];
        }
    }
    
    /**
     * Track WordPress hook performance
     */
    public static function track_hook_performance($hook_name) {
        static $hook_times = [];
        static $current_hook = null;
        
        if ($current_hook && isset($hook_times[$current_hook])) {
            $hook_times[$current_hook] += microtime(true) - $hook_times[$current_hook];
        }
        
        if (in_array($hook_name, ['wp_head', 'wp_footer', 'init', 'wp_loaded'])) {
            $hook_times[$hook_name] = microtime(true);
            $current_hook = $hook_name;
        }
        
        self::$performance_data['hook_times'] = $hook_times;
    }
    
    /**
     * Track overall page performance and send to Mixpanel
     */
    public static function track_page_performance() {
        if (!self::is_enabled_for_site()) {
            return;
        }
        
        $settings = self::get_effective_settings();
        $wp_performance_enabled = $settings['wp_performance_tracking'] ?? get_option('mixpanel_wp_performance_tracking', 0);
        
        if (!$wp_performance_enabled || empty($settings['token'])) {
            return;
        }
        
        // Calculate total page generation time
        $total_time = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
        
        // Prepare performance data
        $performance_data = [
            'page_load_time' => round($total_time * 1000, 2), // Convert to milliseconds
            'memory_usage' => round(memory_get_peak_usage(true) / 1024 / 1024, 2), // MB
            'memory_limit' => ini_get('memory_limit'),
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'active_plugins' => count(get_option('active_plugins', [])),
            'active_theme' => get_option('current_theme'),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'is_admin' => is_admin(),
            'is_ajax' => wp_doing_ajax()
        ];
        
        // Add database stats if available
        if (!empty(self::$performance_data['db_stats'])) {
            $performance_data = array_merge($performance_data, self::$performance_data['db_stats']);
        }
        
        // Add hook timing data
        if (!empty(self::$performance_data['hook_times'])) {
            foreach (self::$performance_data['hook_times'] as $hook => $time) {
                $performance_data["hook_{$hook}_time"] = round($time * 1000, 2);
            }
        }
        
        ?>
        <script type="text/javascript">
        if (window.mixpanel) {
            mixpanel.track('WordPress Performance', <?php echo wp_json_encode($performance_data); ?>);
        }
        </script>
        <?php
    }
    
    /**
     * Clean up plugin options
     */
    public static function cleanup_options() {
        // Remove site options
        foreach (self::$options as $option) {
            delete_option($option);
        }
        
        // Remove network options (multisite)
        if (is_multisite()) {
            foreach (self::$network_options as $option) {
                delete_site_option($option);
            }
            // Also remove any network copies of site options
            foreach (self::$options as $option) {
                delete_site_option($option);
            }
        }
    }
}

// Initialize the plugin
MixpanelInstrumentation::init();

// Deactivation hook: clean up on plugin disable
register_deactivation_hook(__FILE__, [MixpanelInstrumentation::class, 'cleanup_options']);

// Uninstall hook: clean up on plugin uninstall
register_uninstall_hook(__FILE__, [MixpanelInstrumentation::class, 'cleanup_options']);
