/**
 * Advanced Menu Items Visibility Control - Admin JS
 *
 * Handles the visibility options accordion and conditional fields
 * in the WordPress nav menu editor.
 *
 * @package Advanced_Menu_Items_Visibility_Control
 */

(function($) {
    'use strict';

    /**
     * Toggle RCP fields visibility based on login status selection
     */
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

    /**
     * Initialize all existing dropdowns on page load
     */
    function initializeAllDropdowns() {
        $('select[name^="rcp_menu_item_login_status"]').each(function() {
            toggleRCPFields(this);
        });
    }

    /**
     * Document ready handler
     */
    $(document).ready(function() {

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

})(jQuery);
