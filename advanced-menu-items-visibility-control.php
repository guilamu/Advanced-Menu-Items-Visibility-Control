<?php
/*
Plugin Name: Advanced Menu Items Visibility Control
Description: Control menu item visibility based on Login Status, WordPress User Roles, Restrict Content Pro Membership and Restrict Content Pro Access Levels.
Version: 1.2
Author: Guilamu
Plugin URI: https://github.com/guilamu/Advanced-Menu-Items-Visibility-Control
Update URI: https://github.com/guilamu/Advanced-Menu-Items-Visibility-Control/
Text Domain: advanced-menu-items-visibility-control
Domain Path: /languages
*/

add_filter( 'update_plugins_github.com', 'amiv_check_for_updates', 10, 4 );

/**
 * Check for updates from GitHub
 */
function amiv_check_for_updates( $update, array $plugin_data, string $plugin_file, $locales ) {
    // Only check this specific plugin
    if ( 'advanced-menu-items-visibility-control/advanced-menu-items-visibility-control.php' !== $plugin_file ) {
        return $update;
    }
    
    // Skip if update already found
    if ( ! empty( $update ) ) {
        return $update;
    }
    
    // Fetch latest release from GitHub API
    $response = wp_remote_get(
        'https://api.github.com/repos/guilamu/Advanced-Menu-Items-Visibility-Control/releases/latest',
        array(
            'user-agent' => 'guilamu',
        )
    );
    
    if ( is_wp_error( $response ) ) {
        return $update;
    }
    
    $release_data = json_decode( wp_remote_retrieve_body( $response ), true );
    
    if ( empty( $release_data ) ) {
        return $update;
    }
    
    $new_version = ltrim( $release_data['tag_name'], 'v' ); // Remove 'v' prefix if exists
    
    // Compare versions
    if ( ! version_compare( $plugin_data['Version'], $new_version, '<' ) ) {
        return false;
    }
    
    // Return update data
    return array(
        'slug'        => 'advanced-menu-items-visibility-control',
        'version'     => $new_version,
        'url'         => $release_data['html_url'],
        'package'     => $release_data['zipball_url'],
        'tested'      => '6.7', // Update as needed
        'requires_php' => '7.0',
    );
}

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'plugins_api', 'amiv_plugin_information', 10, 3 );

/**
 * Provide plugin information for the "View details" modal
 */
function amiv_plugin_information( $result, $action, $args ) {
    // Only handle plugin_information requests for our plugin
    if ( $action !== 'plugin_information' ) {
        return $result;
    }
    
    if ( ! isset( $args->slug ) || $args->slug !== 'advanced-menu-items-visibility-control' ) {
        return $result;
    }
    
    // Fetch latest release from GitHub
    $response = wp_remote_get(
        'https://api.github.com/repos/guilamu/Advanced-Menu-Items-Visibility-Control/releases/latest',
        array(
            'user-agent' => 'guilamu',
        )
    );
    
    if ( is_wp_error( $response ) ) {
        return $result;
    }
    
    $release_data = json_decode( wp_remote_retrieve_body( $response ), true );
    
    if ( empty( $release_data ) ) {
        return $result;
    }
    
    $version = ltrim( $release_data['tag_name'], 'v' );
    
    // Return plugin information object
    $plugin_info = new stdClass();
    $plugin_info->name = 'Advanced Menu Items Visibility Control';
    $plugin_info->slug = 'advanced-menu-items-visibility-control';
    $plugin_info->version = $version;
    $plugin_info->author = '<a href="https://github.com/guilamu">Guilamu</a>';
    $plugin_info->homepage = 'https://github.com/guilamu/Advanced-Menu-Items-Visibility-Control';
    $plugin_info->download_link = $release_data['zipball_url'];
    $plugin_info->requires = '5.0';
    $plugin_info->tested = '6.9';
    $plugin_info->requires_php = '7.0';
    $plugin_info->last_updated = $release_data['published_at'];
    
    // Add sections (description, changelog, etc.)
    $plugin_info->sections = array(
        'description' => 'Control menu item visibility based on Login Status, WordPress User Roles, Restrict Content Pro Membership and Restrict Content Pro Access Levels.',
        'changelog' => '<h4>' . esc_html( $version ) . '</h4><p>' . esc_html( $release_data['body'] ) . '</p>',
    );
    
    return $plugin_info;
}

class RCP_Menu_Items_Visibility {

    /**
     * Check if Restrict Content Pro is installed and active
     */
    public static function is_rcp_active() {
        return function_exists( 'rcp_get_membership_levels' );
    }

    /**
     * Wait for 'plugins_loaded' to ensure RCP is active before we run.
     */
    public static function load() {
        add_action( 'plugins_loaded', array( __CLASS__, 'init' ) );
    }

    public static function init() {
        // Load plugin text domain for translations
        load_plugin_textdomain( 'advanced-menu-items-visibility-control', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

        if ( is_admin() ) {
            add_action( 'wp_nav_menu_item_custom_fields', array( __CLASS__, 'add_custom_fields' ), 10, 5 );
            add_action( 'wp_update_nav_menu_item', array( __CLASS__, 'save_custom_fields' ), 10, 3 );
            add_action( 'admin_footer', array( __CLASS__, 'admin_footer_script' ) );
        } else {
            add_filter( 'wp_get_nav_menu_items', array( __CLASS__, 'filter_menu_items' ), 10, 3 );
        }
    }

    /**
     * Add JavaScript to admin footer for better compatibility with WordPress nav menu editor
     */
    public static function admin_footer_script() {
        $screen = get_current_screen();
        if ( 'nav-menus' !== $screen->id ) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {

            // Function to toggle RCP fields visibility
            function toggleRCPFields(selectElement) {
                var $select = $(selectElement);
                var $container = $select.closest('.field-rcp-visibility-content');
                var $rcpSections = $container.find('.rcp-conditional-section');

                if ($select.val() === 'logged_in') {
                    $rcpSections.slideDown(200);
                } else {
                    $rcpSections.slideUp(200);
                }
            }

            // Initialize all existing dropdowns on page load
            function initializeAllDropdowns() {
                $('select[name^="rcp_menu_item_login_status"]').each(function() {
                    toggleRCPFields(this);
                });
            }

            // Handle accordion toggle
            $(document).on('click', '.rcp-visibility-accordion-toggle', function(e) {
                e.preventDefault();
                var $toggle = $(this);
                var $content = $toggle.next('.field-rcp-visibility-content');
                var $icon = $toggle.find('.toggle-indicator');

                if ($content.is(':visible')) {
                    $content.slideUp(300);
                    $icon.removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
                    $toggle.removeClass('active');
                } else {
                    $content.slideDown(300);
                    $icon.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
                    $toggle.addClass('active');

                    // Initialize dropdowns when accordion opens
                    setTimeout(function() {
                        initializeAllDropdowns();
                    }, 50);
                }
            });

            // Run on initial page load
            initializeAllDropdowns();

            // Handle changes to login status dropdowns using event delegation
            $(document).on('change', 'select[name^="rcp_menu_item_login_status"]', function() {
                toggleRCPFields(this);
            });

            // WordPress nav menu fires this event when items are added/expanded
            $(document).on('click', '.item-edit', function() {
                setTimeout(function() {
                    initializeAllDropdowns();
                }, 100);
            });

            // Handle when menu items are added from the left column
            $('#submit-posttype-post, #submit-posttype-page, #submit-custom-links, #submit-category, #submit-post_tag').on('click', function() {
                setTimeout(function() {
                    initializeAllDropdowns();
                }, 500);
            });

            // Watch for DOM mutations (when menu items are added/modified)
            if (window.MutationObserver) {
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.addedNodes.length > 0) {
                            $(mutation.addedNodes).find('select[name^="rcp_menu_item_login_status"]').each(function() {
                                toggleRCPFields(this);
                            });
                        }
                    });
                });

                var menuContainer = document.getElementById('menu-to-edit');
                if (menuContainer) {
                    observer.observe(menuContainer, {
                        childList: true,
                        subtree: true
                    });
                }
            }
        });
        </script>
        <style type="text/css">
        .rcp-conditional-section {
            overflow: hidden;
        }

        .rcp-visibility-accordion-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px;
            background: #f0f0f1;
            border: 1px solid #c3c4c7;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            color: #1d2327;
            text-decoration: none;
            margin: 10px 0;
            transition: background-color 0.2s ease;
        }

        .rcp-visibility-accordion-toggle:hover {
            background: #e8e8e9;
        }

        .rcp-visibility-accordion-toggle.active {
            background: #dcdcde;
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        .rcp-visibility-accordion-toggle .toggle-indicator {
            margin-left: 10px;
            color: #50575e;
        }

        .field-rcp-visibility-content {
            display: none;
            padding: 15px;
            border: 1px solid #c3c4c7;
            border-top: none;
            border-radius: 0 0 4px 4px;
            background: #f9f9f9;
            margin-top: -10px;
            margin-bottom: 10px;
        }

        .field-rcp-visibility-content.open-by-default {
            display: block;
        }
        </style>
        <?php
    }

    /**
     * Display Fields in Menu Editor
     */
    public static function add_custom_fields( $item_id, $item, $depth, $args, $id = 0 ) {
        wp_nonce_field( 'rcp_menu_meta_nonce', '_rcp_menu_meta_nonce' );

        // --- 1. Retrieve Saved Data ---
        $saved_login_status = get_post_meta( $item_id, '_rcp_menu_item_login_status', true );

        $saved_roles = get_post_meta( $item_id, '_rcp_menu_item_roles', true );
        if ( ! is_array( $saved_roles ) ) $saved_roles = array();

        $saved_rcp_levels = get_post_meta( $item_id, '_rcp_menu_item_levels', true );
        if ( ! is_array( $saved_rcp_levels ) ) $saved_rcp_levels = array();

        $saved_access_level = get_post_meta( $item_id, '_rcp_menu_item_access_level', true );

        // --- 2. Get Available Options ---
        $is_rcp_active = self::is_rcp_active();
        $rcp_levels = $is_rcp_active ? rcp_get_membership_levels( array( 'number' => 0 ) ) : array();

        global $wp_roles;
        $all_roles = $wp_roles->get_names(); 

        // --- 3. Determine if accordion should be open by default ---
        // Open if login status is NOT empty (i.e., not "Show to Everyone")
        // OR if any roles are selected
        $should_be_open = ! empty( $saved_login_status ) || ! empty( $saved_roles );

        $accordion_class = $should_be_open ? 'active' : '';
        $content_class = $should_be_open ? 'open-by-default' : '';
        $arrow_direction = $should_be_open ? 'dashicons-arrow-up' : 'dashicons-arrow-down';

        ?>
        <div class="field-rcp-visibility description-wide">

            <!-- Accordion Toggle Button -->
            <a href="#" class="rcp-visibility-accordion-toggle <?php echo esc_attr( $accordion_class ); ?>">
                <span><?php _e( 'Visibility Options', 'advanced-menu-items-visibility-control' ); ?></span>
                <span class="dashicons <?php echo esc_attr( $arrow_direction ); ?> toggle-indicator"></span>
            </a>

            <!-- Accordion Content (Conditionally Open) -->
            <div class="field-rcp-visibility-content <?php echo esc_attr( $content_class ); ?>">

                <!-- 1. LOGIN STATUS -->
                <p style="margin-top:0;"><strong><?php _e( 'Restrict by Login Status:', 'advanced-menu-items-visibility-control' ); ?></strong></p>
                <label style="display: block; margin-bottom: 10px;">
                    <select name="rcp_menu_item_login_status[<?php echo esc_attr( $item_id ); ?>]" class="widefat rcp-login-status-select">
                        <option value="" <?php selected( $saved_login_status, '' ); ?>><?php _e( 'Show to Everyone', 'advanced-menu-items-visibility-control' ); ?></option>
                        <option value="logged_in" <?php selected( $saved_login_status, 'logged_in' ); ?>><?php _e( 'Show only to Logged In Users', 'advanced-menu-items-visibility-control' ); ?></option>
                        <option value="logged_out" <?php selected( $saved_login_status, 'logged_out' ); ?>><?php _e( 'Show only to Logged Out Users', 'advanced-menu-items-visibility-control' ); ?></option>
                    </select>
                </label>

                <!-- 2. USER ROLES -->
                <p style="margin-bottom: 5px;"><strong><?php _e( 'Restrict to User Roles:', 'advanced-menu-items-visibility-control' ); ?></strong></p>
                <?php if ( $all_roles ) : ?>
                    <div style="max-height: 100px; overflow-y: auto; margin-bottom: 10px; border: 1px solid #eee; background: #fff; padding: 5px;">
                        <?php foreach ( $all_roles as $role_slug => $role_name ) : ?>
                            <label style="display: block; margin-bottom: 3px;">
                                <input type="checkbox" 
                                       name="rcp_menu_item_roles[<?php echo esc_attr( $item_id ); ?>][]" 
                                       value="<?php echo esc_attr( $role_slug ); ?>" 
                                       <?php checked( in_array( $role_slug, $saved_roles ) ); ?> 
                                />
                                <?php echo esc_html( $role_name ); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- 3. MEMBERSHIP LEVELS (RCP) - Only if RCP is installed AND logged_in is selected -->
                <?php if ( $is_rcp_active ) : ?>
                    <div class="rcp-conditional-section" style="<?php echo ( $saved_login_status !== 'logged_in' ) ? 'display:none;' : ''; ?>">
                        <p><strong><?php _e( 'Restrict to Membership Levels (RCP):', 'advanced-menu-items-visibility-control' ); ?></strong></p>
                        <?php if ( $rcp_levels ) : ?>
                            <div style="max-height: 100px; overflow-y: auto; margin-bottom: 10px; border: 1px solid #eee; background: #fff; padding: 5px;">
                                <?php foreach ( $rcp_levels as $level ) : ?>
                                    <label style="display: block; margin-bottom: 3px;">
                                        <input type="checkbox" 
                                               name="rcp_menu_item_levels[<?php echo esc_attr( $item_id ); ?>][]" 
                                               value="<?php echo esc_attr( $level->get_id() ); ?>" 
                                               <?php checked( in_array( $level->get_id(), $saved_rcp_levels ) ); ?> 
                                        />
                                        <?php echo esc_html( $level->get_name() ); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php else : ?>
                            <p><?php _e( 'No membership levels found.', 'advanced-menu-items-visibility-control' ); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- 4. ACCESS LEVEL (RCP) - Only if RCP is installed AND logged_in is selected -->
                <?php if ( $is_rcp_active ) : ?>
                    <div class="rcp-conditional-section" style="<?php echo ( $saved_login_status !== 'logged_in' ) ? 'display:none;' : ''; ?>">
                        <p style="margin-bottom: 5px;"><strong><?php _e( 'Restrict to Access Level (RCP):', 'advanced-menu-items-visibility-control' ); ?></strong></p>
                        <label>
                            <select name="rcp_menu_item_access_level[<?php echo esc_attr( $item_id ); ?>]" class="widefat">
                                <option value="" <?php selected( $saved_access_level, '' ); ?>><?php _e( 'Any (No Restriction)', 'advanced-menu-items-visibility-control' ); ?></option>
                                <?php for ( $i = 0; $i <= 10; $i++ ) : ?>
                                    <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $saved_access_level, (string)$i ); ?>>
                                        <?php printf( __( '%d and higher', 'rcp' ), $i ); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </label>
                    </div>
                <?php endif; ?>

                <p class="description" style="margin-top:10px; margin-bottom:0; color:#666;">
                    <?php _e( 'Note: If multiple sections (Roles, Levels, Access) are used, the user must match ALL selected criteria.', 'advanced-menu-items-visibility-control' ); ?>
                </p>

            </div><!-- .field-rcp-visibility-content -->

        </div>
        <?php
    }

    /**
     * Save the Data
     */
    public static function save_custom_fields( $menu_id, $menu_item_db_id, $args ) {
        if ( ! isset( $_POST['_rcp_menu_meta_nonce'] ) || ! wp_verify_nonce( $_POST['_rcp_menu_meta_nonce'], 'rcp_menu_meta_nonce' ) ) {
            return;
        }

        // 1. Save Login Status
        if ( isset( $_POST['rcp_menu_item_login_status'][ $menu_item_db_id ] ) && in_array( $_POST['rcp_menu_item_login_status'][ $menu_item_db_id ], array( 'logged_in', 'logged_out' ) ) ) {
             update_post_meta( $menu_item_db_id, '_rcp_menu_item_login_status', sanitize_text_field( $_POST['rcp_menu_item_login_status'][ $menu_item_db_id ] ) );
        } else {
            delete_post_meta( $menu_item_db_id, '_rcp_menu_item_login_status' );
        }

        // Get the login status to determine if RCP fields should be saved
        $login_status = isset( $_POST['rcp_menu_item_login_status'][ $menu_item_db_id ] ) 
                        ? $_POST['rcp_menu_item_login_status'][ $menu_item_db_id ] 
                        : '';

        // 2. Save User Roles
        if ( isset( $_POST['rcp_menu_item_roles'][ $menu_item_db_id ] ) ) {
            $clean_roles = array_map( 'sanitize_text_field', $_POST['rcp_menu_item_roles'][ $menu_item_db_id ] );
            update_post_meta( $menu_item_db_id, '_rcp_menu_item_roles', $clean_roles );
        } else {
            delete_post_meta( $menu_item_db_id, '_rcp_menu_item_roles' );
        }

        // 3. Save Membership Levels (only if RCP is active AND logged_in is selected)
        if ( self::is_rcp_active() && $login_status === 'logged_in' ) {
            if ( isset( $_POST['rcp_menu_item_levels'][ $menu_item_db_id ] ) ) {
                $clean_levels = array_map( 'intval', $_POST['rcp_menu_item_levels'][ $menu_item_db_id ] );
                update_post_meta( $menu_item_db_id, '_rcp_menu_item_levels', $clean_levels );
            } else {
                delete_post_meta( $menu_item_db_id, '_rcp_menu_item_levels' );
            }
        } else {
            // Clear RCP membership levels if not logged_in
            delete_post_meta( $menu_item_db_id, '_rcp_menu_item_levels' );
        }

        // 4. Save Access Level (only if RCP is active AND logged_in is selected)
        if ( self::is_rcp_active() && $login_status === 'logged_in' ) {
            if ( isset( $_POST['rcp_menu_item_access_level'][ $menu_item_db_id ] ) && $_POST['rcp_menu_item_access_level'][ $menu_item_db_id ] !== '' ) {
                update_post_meta( $menu_item_db_id, '_rcp_menu_item_access_level', intval( $_POST['rcp_menu_item_access_level'][ $menu_item_db_id ] ) );
            } else {
                delete_post_meta( $menu_item_db_id, '_rcp_menu_item_access_level' );
            }
        } else {
            // Clear RCP access level if not logged_in
            delete_post_meta( $menu_item_db_id, '_rcp_menu_item_access_level' );
        }
    }

    /**
     * Frontend Visibility Logic
     */
    public static function filter_menu_items( $items, $menu, $args ) {
        $hidden_items = array();
        $user_id = get_current_user_id();
        $is_logged_in = is_user_logged_in();
        $is_rcp_active = self::is_rcp_active();

        foreach ( $items as $key => $item ) {
            $visible = true;

            // --- PRIMARY CHECK 1: LOGIN STATUS (Logged In/Out) ---
            $required_login_status = get_post_meta( $item->ID, '_rcp_menu_item_login_status', true );

            if ( $required_login_status === 'logged_in' && ! $is_logged_in ) {
                $visible = false;
            } elseif ( $required_login_status === 'logged_out' && $is_logged_in ) {
                $visible = false;
            }

            // If the item is marked for logged-out users, we skip all other checks since they are logged-out.
            if ( $visible && $required_login_status !== 'logged_out' ) {

                // --- CHECK 2: USER ROLES ---
                $required_roles = get_post_meta( $item->ID, '_rcp_menu_item_roles', true );
                if ( $visible && ! empty( $required_roles ) && is_array( $required_roles ) ) {
                    if ( ! $is_logged_in ) {
                        $visible = false;
                    } else {
                        $user = wp_get_current_user();
                        $role_matches = array_intersect( $required_roles, (array) $user->roles );
                        if ( empty( $role_matches ) ) $visible = false;
                    }
                }

                // --- CHECK 3: MEMBERSHIP LEVELS (only if RCP is active AND logged_in status is selected) ---
                if ( $is_rcp_active && $required_login_status === 'logged_in' ) {
                    $required_levels = get_post_meta( $item->ID, '_rcp_menu_item_levels', true );
                    if ( $visible && ! empty( $required_levels ) && is_array( $required_levels ) ) {
                        if ( ! $is_logged_in ) {
                            $visible = false;
                        } else {
                            $user_level_ids = rcp_get_customer_membership_level_ids( $user_id );
                            $matches = array_intersect( $required_levels, $user_level_ids );
                            if ( empty( $matches ) ) $visible = false;
                        }
                    }
                }

                // --- CHECK 4: ACCESS LEVEL (only if RCP is active AND logged_in status is selected) ---
                if ( $is_rcp_active && $visible && $required_login_status === 'logged_in' ) {
                    $required_access = get_post_meta( $item->ID, '_rcp_menu_item_access_level', true );
                    if ( $required_access !== '' && $required_access !== false ) {
                        if ( ! $is_logged_in ) {
                            $visible = false;
                        } else {
                            if ( ! rcp_user_has_access_level( $user_id, intval( $required_access ) ) ) {
                                $visible = false;
                            }
                        }
                    }
                }
            } // End of conditional checks for logged-in/all users

            // --- PARENT / CHILD CLEANUP ---
            if ( ! $visible || ( isset( $item->menu_item_parent ) && isset( $hidden_items[ $item->menu_item_parent ] ) ) ) {
                $hidden_items[ $item->ID ] = true;
                unset( $items[ $key ] );
            }
        }

        return $items;
    }
}

// Initialize
RCP_Menu_Items_Visibility::load();
