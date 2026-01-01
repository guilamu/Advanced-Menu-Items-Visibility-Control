<?php
/*
Plugin Name: Advanced Menu Items Visibility Control
Description: Control menu item visibility based on Login Status, WordPress User Roles, Restrict Content Pro Membership and Restrict Content Pro Access Levels.
Version: 1.2.4
Author: Guilamu
Plugin URI: https://github.com/guilamu/Advanced-Menu-Items-Visibility-Control
Update URI: https://github.com/guilamu/Advanced-Menu-Items-Visibility-Control/
Text Domain: advanced-menu-items-visibility-control
Domain Path: /languages
Requires at least: 5.0
Requires PHP: 7.0
*/

if (!defined('ABSPATH')) {
    exit;
}

// Include the GitHub auto-updater
require_once plugin_dir_path(__FILE__) . 'includes/class-github-updater.php';

class RCP_Menu_Items_Visibility
{

    /**
     * Check if Restrict Content Pro is installed and active
     */
    public static function is_rcp_active()
    {
        return function_exists('rcp_get_membership_levels');
    }

    /**
     * Wait for 'plugins_loaded' to ensure RCP is active before we run.
     */
    public static function load()
    {
        add_action('plugins_loaded', array(__CLASS__, 'init'));
    }

    public static function init()
    {
        // Load plugin text domain for translations
        load_plugin_textdomain('advanced-menu-items-visibility-control', false, dirname(plugin_basename(__FILE__)) . '/languages');

        if (is_admin()) {
            add_action('wp_nav_menu_item_custom_fields', array(__CLASS__, 'add_custom_fields'), 10, 5);
            add_action('wp_update_nav_menu_item', array(__CLASS__, 'save_custom_fields'), 10, 3);
            add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
        } else {
            add_filter('wp_get_nav_menu_items', array(__CLASS__, 'filter_menu_items'), 10, 3);
        }
    }

    /**
     * Enqueue admin scripts and styles for the nav-menus page.
     *
     * @param string $hook_suffix The current admin page hook suffix.
     * @return void
     */
    public static function enqueue_admin_assets( $hook_suffix ) {
        // Only load on nav-menus page
        if ( 'nav-menus.php' !== $hook_suffix ) {
            return;
        }

        // Enqueue admin styles
        wp_enqueue_style(
            'amiv-admin',
            plugins_url('assets/css/admin.css', __FILE__),
            array(),
            '1.2.4'
        );

        // Enqueue admin scripts
        wp_enqueue_script(
            'amiv-admin',
            plugins_url('assets/js/admin.js', __FILE__),
            array('jquery'),
            '1.2.4',
            true
        );
    }

    /**
     * Display Fields in Menu Editor
     */
    public static function add_custom_fields($item_id, $item, $depth, $args, $id = 0)
    {
        // Output nonce only once per page to avoid duplicates
        static $nonce_output = false;
        if (!$nonce_output) {
            wp_nonce_field('rcp_menu_meta_nonce', '_rcp_menu_meta_nonce');
            $nonce_output = true;
        }

        // --- 1. Retrieve Saved Data ---
        $saved_login_status = get_post_meta($item_id, '_rcp_menu_item_login_status', true);

        $saved_roles = get_post_meta($item_id, '_rcp_menu_item_roles', true);
        if (!is_array($saved_roles))
            $saved_roles = array();

        $saved_rcp_levels = get_post_meta($item_id, '_rcp_menu_item_levels', true);
        if (!is_array($saved_rcp_levels))
            $saved_rcp_levels = array();

        $saved_access_level = get_post_meta($item_id, '_rcp_menu_item_access_level', true);

        // --- 2. Get Available Options ---
        $is_rcp_active = self::is_rcp_active();
        $rcp_levels = $is_rcp_active ? rcp_get_membership_levels(array('number' => 0)) : array();

        global $wp_roles;
        $all_roles = $wp_roles->get_names();

        // --- 3. Determine if accordion should be open by default ---
        // Open if login status is NOT empty (i.e., not "Show to Everyone")
        // OR if any roles are selected
        $should_be_open = !empty($saved_login_status) || !empty($saved_roles);

        $accordion_class = $should_be_open ? 'active' : '';
        $content_class = $should_be_open ? 'open-by-default' : '';
        $arrow_direction = $should_be_open ? 'dashicons-arrow-up' : 'dashicons-arrow-down';

        ?>
                <div class="field-rcp-visibility description-wide">

                    <!-- Accordion Toggle Button -->
                    <a href="#" class="rcp-visibility-accordion-toggle <?php echo esc_attr($accordion_class); ?>">
                        <span><?php esc_html_e('Visibility Options', 'advanced-menu-items-visibility-control'); ?></span>
                        <span class="dashicons <?php echo esc_attr($arrow_direction); ?> toggle-indicator"></span>
                    </a>

                    <!-- Accordion Content (Conditionally Open) -->
                    <div class="field-rcp-visibility-content <?php echo esc_attr($content_class); ?>">

                        <!-- 1. LOGIN STATUS -->
                        <p style="margin-top:0;"><strong><?php esc_html_e('Restrict by Login Status:', 'advanced-menu-items-visibility-control'); ?></strong></p>
                        <label style="display: block; margin-bottom: 10px;">
                            <select name="rcp_menu_item_login_status[<?php echo esc_attr($item_id); ?>]" class="widefat rcp-login-status-select">
                                <option value="" <?php selected($saved_login_status, ''); ?>><?php esc_html_e('Show to Everyone', 'advanced-menu-items-visibility-control'); ?></option>
                                <option value="logged_in" <?php selected($saved_login_status, 'logged_in'); ?>><?php esc_html_e('Show only to Logged In Users', 'advanced-menu-items-visibility-control'); ?></option>
                                <option value="logged_out" <?php selected($saved_login_status, 'logged_out'); ?>><?php esc_html_e('Show only to Logged Out Users', 'advanced-menu-items-visibility-control'); ?></option>
                            </select>
                        </label>

                        <!-- 2. USER ROLES -->
                        <p style="margin-bottom: 5px;"><strong><?php esc_html_e('Restrict to User Roles:', 'advanced-menu-items-visibility-control'); ?></strong></p>
                        <?php if ($all_roles): ?>
                                <div style="max-height: 100px; overflow-y: auto; margin-bottom: 10px; border: 1px solid #eee; background: #fff; padding: 5px;">
                                    <?php foreach ($all_roles as $role_slug => $role_name): ?>
                                            <label style="display: block; margin-bottom: 3px;">
                                                <input type="checkbox" 
                                                       name="rcp_menu_item_roles[<?php echo esc_attr($item_id); ?>][]" 
                                                       value="<?php echo esc_attr($role_slug); ?>" 
                                                       <?php checked(in_array($role_slug, $saved_roles)); ?> 
                                                />
                                                <?php echo esc_html($role_name); ?>
                                            </label>
                                    <?php endforeach; ?>
                                </div>
                        <?php endif; ?>

                        <!-- 3. MEMBERSHIP LEVELS (RCP) - Only if RCP is installed AND logged_in is selected -->
                        <?php if ($is_rcp_active): ?>
                                <div class="rcp-conditional-section" style="<?php echo ($saved_login_status !== 'logged_in') ? 'display:none;' : ''; ?>">
                                    <p><strong><?php esc_html_e('Restrict to Membership Levels (RCP):', 'advanced-menu-items-visibility-control'); ?></strong></p>
                                    <?php if ($rcp_levels): ?>
                                            <div style="max-height: 100px; overflow-y: auto; margin-bottom: 10px; border: 1px solid #eee; background: #fff; padding: 5px;">
                                                <?php foreach ($rcp_levels as $level): ?>
                                                        <label style="display: block; margin-bottom: 3px;">
                                                            <input type="checkbox" 
                                                                   name="rcp_menu_item_levels[<?php echo esc_attr($item_id); ?>][]" 
                                                                   value="<?php echo esc_attr($level->get_name()); ?>" 
                                                                   <?php checked(in_array($level->get_name(), $saved_rcp_levels)); ?> 
                                                            />
                                                            <?php echo esc_html($level->get_name()); ?>
                                                        </label>
                                                <?php endforeach; ?>
                                            </div>
                                    <?php else: ?>
                                            <p><?php esc_html_e('No membership levels found.', 'advanced-menu-items-visibility-control'); ?></p>
                                    <?php endif; ?>
                                </div>
                        <?php endif; ?>

                        <!-- 4. ACCESS LEVEL (RCP) - Only if RCP is installed AND logged_in is selected -->
                        <?php if ($is_rcp_active): ?>
                                <div class="rcp-conditional-section" style="<?php echo ($saved_login_status !== 'logged_in') ? 'display:none;' : ''; ?>">
                                    <p style="margin-bottom: 5px;"><strong><?php esc_html_e('Restrict to Access Level (RCP):', 'advanced-menu-items-visibility-control'); ?></strong></p>
                                    <label>
                                        <select name="rcp_menu_item_access_level[<?php echo esc_attr($item_id); ?>]" class="widefat">
                                            <option value="" <?php selected($saved_access_level, ''); ?>><?php esc_html_e('Any (No Restriction)', 'advanced-menu-items-visibility-control'); ?></option>
                                            <?php for ($i = 0; $i <= 10; $i++): ?>
                                                    <option value="<?php echo esc_attr($i); ?>" <?php selected($saved_access_level, (string) $i); ?>>
                                                        <?php printf(__('%d and higher', 'rcp'), $i); ?>
                                                    </option>
                                            <?php endfor; ?>
                                        </select>
                                    </label>
                                </div>
                        <?php endif; ?>

                        <p class="description" style="margin-top:10px; margin-bottom:0; color:#666;">
                            <?php esc_html_e('Note: If multiple sections (Roles, Levels, Access) are used, the user must match ALL selected criteria.', 'advanced-menu-items-visibility-control'); ?>
                        </p>

                    </div><!-- .field-rcp-visibility-content -->

                </div>
                <?php
    }

    /**
     * Save the Data
     */
    public static function save_custom_fields($menu_id, $menu_item_db_id, $args)
    {
        // Verify nonce
        if (!isset($_POST['_rcp_menu_meta_nonce']) || !wp_verify_nonce($_POST['_rcp_menu_meta_nonce'], 'rcp_menu_meta_nonce')) {
            return;
        }

        // Verify user capabilities
        if (!current_user_can('edit_theme_options')) {
            return;
        }

        // 1. Save Login Status
        if (isset($_POST['rcp_menu_item_login_status'][$menu_item_db_id]) && in_array($_POST['rcp_menu_item_login_status'][$menu_item_db_id], array('logged_in', 'logged_out'))) {
            update_post_meta($menu_item_db_id, '_rcp_menu_item_login_status', sanitize_text_field($_POST['rcp_menu_item_login_status'][$menu_item_db_id]));
        } else {
            delete_post_meta($menu_item_db_id, '_rcp_menu_item_login_status');
        }

        // Get the login status to determine if RCP fields should be saved
        $login_status = isset($_POST['rcp_menu_item_login_status'][$menu_item_db_id])
            ? $_POST['rcp_menu_item_login_status'][$menu_item_db_id]
            : '';

        // 2. Save User Roles
        if (isset($_POST['rcp_menu_item_roles'][$menu_item_db_id])) {
            $clean_roles = array_map('sanitize_text_field', $_POST['rcp_menu_item_roles'][$menu_item_db_id]);
            update_post_meta($menu_item_db_id, '_rcp_menu_item_roles', $clean_roles);
        } else {
            delete_post_meta($menu_item_db_id, '_rcp_menu_item_roles');
        }

        // 3. Save Membership Levels (only if RCP is active AND logged_in is selected)
        if (self::is_rcp_active() && $login_status === 'logged_in') {
            if (isset($_POST['rcp_menu_item_levels'][$menu_item_db_id])) {
                // Store level names (not IDs) for more reliable comparison
                $clean_levels = array_values(array_map('sanitize_text_field', $_POST['rcp_menu_item_levels'][$menu_item_db_id]));
                update_post_meta($menu_item_db_id, '_rcp_menu_item_levels', $clean_levels);
            } else {
                delete_post_meta($menu_item_db_id, '_rcp_menu_item_levels');
            }
        } else {
            // Clear RCP membership levels if not logged_in
            delete_post_meta($menu_item_db_id, '_rcp_menu_item_levels');
        }

        // 4. Save Access Level (only if RCP is active AND logged_in is selected)
        if (self::is_rcp_active() && $login_status === 'logged_in') {
            if (isset($_POST['rcp_menu_item_access_level'][$menu_item_db_id]) && $_POST['rcp_menu_item_access_level'][$menu_item_db_id] !== '') {
                update_post_meta($menu_item_db_id, '_rcp_menu_item_access_level', intval($_POST['rcp_menu_item_access_level'][$menu_item_db_id]));
            } else {
                delete_post_meta($menu_item_db_id, '_rcp_menu_item_access_level');
            }
        } else {
            // Clear RCP access level if not logged_in
            delete_post_meta($menu_item_db_id, '_rcp_menu_item_access_level');
        }
    }

    /**
     * Frontend Visibility Logic
     */
    public static function filter_menu_items($items, $menu, $args)
    {
        $hidden_items = array();
        $user_id = get_current_user_id();
        $is_logged_in = is_user_logged_in();
        $is_rcp_active = self::is_rcp_active();

        // Pre-load all meta data in a single query to prevent N+1 queries
        $item_ids = wp_list_pluck($items, 'ID');
        if (!empty($item_ids)) {
            update_meta_cache('post', $item_ids);
        }

        foreach ($items as $key => $item) {
            $visible = true;

            // --- PRIMARY CHECK 1: LOGIN STATUS (Logged In/Out) ---
            $required_login_status = get_post_meta($item->ID, '_rcp_menu_item_login_status', true);


            if ($required_login_status === 'logged_in' && !$is_logged_in) {
                $visible = false;
            } elseif ($required_login_status === 'logged_out' && $is_logged_in) {
                $visible = false;
            }

            // If the item is marked for logged-out users, we skip all other checks since they are logged-out.
            if ($visible && $required_login_status !== 'logged_out') {

                // --- CHECK 2: USER ROLES ---
                $required_roles = get_post_meta($item->ID, '_rcp_menu_item_roles', true);
                if ($visible && !empty($required_roles) && is_array($required_roles)) {
                    if (!$is_logged_in) {
                        $visible = false;
                    } else {
                        $user = wp_get_current_user();
                        $role_matches = array_intersect($required_roles, (array) $user->roles);
                        if (empty($role_matches))
                            $visible = false;
                    }
                }

                // --- CHECK 3: MEMBERSHIP LEVELS (only if RCP is active AND logged_in status is selected) ---
                if ($is_rcp_active && $required_login_status === 'logged_in') {
                    $required_levels = get_post_meta($item->ID, '_rcp_menu_item_levels', true);
                    if ($visible && !empty($required_levels) && is_array($required_levels)) {
                        if (!$is_logged_in) {
                            $visible = false;
                        } else {
                            // Direct database query to bypass RCP API caching bug
                            $user_level_names = self::get_user_membership_level_names_direct($user_id);
                            $matches = array_intersect($required_levels, (array) $user_level_names);
                            if (empty($matches))
                                $visible = false;
                        }
                    }
                }


                // --- CHECK 4: ACCESS LEVEL (only if RCP is active AND logged_in status is selected) ---
                if ($is_rcp_active && $visible && $required_login_status === 'logged_in') {
                    $required_access = get_post_meta($item->ID, '_rcp_menu_item_access_level', true);
                    if ($required_access !== '' && $required_access !== false) {
                        if (!$is_logged_in) {
                            $visible = false;
                        } else {
                            if (!self::user_has_access_level($user_id, intval($required_access))) {
                                $visible = false;
                            }
                        }
                    }
                }
            } // End of conditional checks for logged-in/all users

            // --- PARENT / CHILD CLEANUP ---
            if (!$visible || (isset($item->menu_item_parent) && isset($hidden_items[$item->menu_item_parent]))) {
                $hidden_items[$item->ID] = true;
                unset($items[$key]);
            }
        }

        return $items;
    }

    /**
     * Check if user has a membership with the required access level or higher.
     * Implements proper access level checking using RCP's API since
     * rcp_user_has_access_level is a filter hook, not a function.
     *
     * @param int $user_id WordPress user ID
     * @param int $required_level Required access level (0-10)
     * @return bool True if user has access level >= required
     */
    private static function user_has_access_level($user_id, $required_level)
    {
        if (!function_exists('rcp_get_customer_by_user_id') || !function_exists('rcp_get_memberships')) {
            return false;
        }

        $customer = rcp_get_customer_by_user_id($user_id);
        if (!$customer) {
            return false;
        }

        $memberships = rcp_get_memberships(array(
            'customer_id' => $customer->get_id(),
            'status' => 'active'
        ));

        foreach ($memberships as $membership) {
            $level_id = $membership->get_object_id();
            if (function_exists('rcp_get_membership_level')) {
                $level = rcp_get_membership_level($level_id);
                if ($level && method_exists($level, 'get_access_level')) {
                    $user_access_level = intval($level->get_access_level());
                    if ($user_access_level >= $required_level) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get user membership level names.
     * Uses direct membership query to avoid RCP API caching issues.
     *
     * @param int $user_id WordPress user ID
     * @return array Array of membership level names
     */
    private static function get_user_membership_level_names_direct($user_id)
    {
        // Primary method: directly query active memberships using RCP's functions
        // This bypasses RCP's API caching which can return stale/incomplete data
        if (function_exists('rcp_get_customer_by_user_id') && function_exists('rcp_get_memberships')) {
            $customer = rcp_get_customer_by_user_id($user_id);
            if ($customer) {
                $memberships = rcp_get_memberships(array(
                    'customer_id' => $customer->get_id(),
                    'status' => 'active'
                ));
                
                $level_names = array();
                foreach ($memberships as $membership) {
                    $level_id = $membership->get_object_id();
                    if (function_exists('rcp_get_membership_level')) {
                        $level = rcp_get_membership_level($level_id);
                        if ($level) {
                            $level_names[] = $level->get_name();
                        }
                    }
                }
                
                if (!empty($level_names)) {
                    return array_unique($level_names);
                }
            }
        }
        
        // Fallback: use standard RCP API (may have caching issues)
        if (function_exists('rcp_get_customer_membership_level_names')) {
            $names = rcp_get_customer_membership_level_names($user_id);
            if (!empty($names)) {
                return (array) $names;
            }
        }
        
        return array();
    }
}

// Initialize
RCP_Menu_Items_Visibility::load();
