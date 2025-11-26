![Plugin Screenshot](https://github.com/guilamu/Advanced-Menu-Items-Visibility-Control/blob/main/screenshot.png)

# Advanced Menu Items Visibility Control

A WordPress plugin that provides advanced visibility controls for navigation menu items based on user authentication status, roles, and Restrict Content Pro membership settings.

## Description

**Advanced Menu Items Visibility Control** extends WordPress's native menu system by adding granular visibility options to individual menu items. Control which users see specific menu items based on their login status, WordPress roles, RCP membership levels, and access levels.

This plugin integrates seamlessly with [Restrict Content Pro](https://restrictcontentpro.com/) to provide membership-based menu visibility, while also working independently for role-based and login-status controls.

## Features

- **Login Status Control**: Show menu items to everyone, only logged-in users, or only logged-out users
- **WordPress Role Restrictions**: Limit menu visibility to specific WordPress user roles
- **RCP Membership Level Integration**: Show menu items only to members with specific membership levels
- **RCP Access Level Support**: Control visibility based on RCP access levels (0-10+)
- **Multiple Criteria Support**: Combine multiple restrictions (all criteria must match)
- **Translation Ready**: Full internationalization support with included French translation
- **Clean Admin UI**: Accordion-style interface in the menu editor
- **Smart Conditionals**: RCP options only appear when "Logged In" is selected
- **Automatic Child Hiding**: Child menu items are hidden when parent items are hidden

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- [Restrict Content Pro](https://restrictcontentpro.com/) (optional, required only for membership/access level features)

## Installation

### Manual Installation

1. Download the plugin files
2. Upload the `advanced-menu-items-visibility-control` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to Appearance → Menus to configure visibility options

### GitHub Installation

```bash
cd wp-content/plugins
git clone https://github.com/guilamu/Advanced-Menu-Items-Visibility-Control.git
```

Then activate the plugin in WordPress.

## Translation

The plugin is fully translation-ready and includes the following translations:

### Available Languages

- **English** (default)
- **French (Français)** - Complete translation included

### Translation Files

The plugin includes:
- `languages/advanced-menu-items-visibility-control.pot` - Template file for translators
- `languages/advanced-menu-items-visibility-control-fr_FR.po` - French translation source
- `languages/advanced-menu-items-visibility-control-fr_FR.mo` - French translation (compiled)

### Contributing Translations

To contribute a translation:

1. Copy the `.pot` file from the `languages/` folder
2. Rename it to `advanced-menu-items-visibility-control-{locale}.po` (e.g., `de_DE.po` for German)
3. Use [Poedit](https://poedit.net/) or another translation tool to translate the strings
4. Compile the `.po` file to `.mo` format
5. Submit a pull request with both `.po` and `.mo` files

All translatable strings are wrapped with WordPress internationalization functions (`__()`, `_e()`, `esc_html_e()`).

## ## Usage

### Basic Setup

1. Go to **Appearance → Menus** in your WordPress dashboard
2. Select a menu to edit or create a new one
3. Click on any menu item to expand its settings
4. Find the **"Visibility Options"** accordion section
5. Configure your desired visibility rules

### Visibility Options

#### 1. Login Status

Choose one of the following:
- **Show to Everyone** (default): No restrictions
- **Show only to Logged In Users**: Menu item visible only to authenticated users
- **Show only to Logged Out Users**: Menu item visible only to guests

#### 2. User Roles

Select one or more WordPress user roles. The menu item will only be visible to users who have at least one of the selected roles.

Available roles include:
- Administrator
- Editor
- Author
- Contributor
- Subscriber
- Custom roles (if defined)

#### 3. Membership Levels (Requires RCP + "Logged In" status)

Select specific Restrict Content Pro membership levels. Only members with one of the selected membership levels will see the menu item.

*Note: This option only appears when Restrict Content Pro is active and "Show only to Logged In Users" is selected.*

#### 4. Access Levels (Requires RCP + "Logged In" status)

Set a minimum RCP access level (0-10+). Only users with the specified access level or higher will see the menu item.

*Note: This option only appears when Restrict Content Pro is active and "Show only to Logged In Users" is selected.*

### Logic Rules

When multiple restrictions are applied:
- **Login Status** is checked first
- If "Logged Out" is selected, all other checks are skipped
- If "Logged In" is selected, the user must match **ALL** selected criteria:
  - Must have at least one of the selected **roles** (if any specified)
  - Must have at least one of the selected **membership levels** (if any specified)
  - Must meet the minimum **access level** (if specified)

## Screenshots

The plugin adds an accordion-style "Visibility Options" section to each menu item in the WordPress menu editor, providing an intuitive interface for configuring all visibility rules.

## Frequently Asked Questions

### Does this plugin require Restrict Content Pro?

No. The plugin works independently for login status and role-based restrictions. RCP is only required if you want to use membership level or access level features.

### What happens if I deactivate Restrict Content Pro?

The plugin will continue to work for login status and role-based restrictions. Membership and access level restrictions will be ignored.

### Can I use multiple restrictions together?

Yes. When multiple restrictions are set, users must satisfy **ALL** criteria to see the menu item (AND logic, not OR).

### Will child menu items be hidden automatically?

Yes. If a parent menu item is hidden, all its children are automatically hidden as well.

### Does this affect menu performance?

The plugin uses efficient WordPress hooks and only processes menu items on the frontend. Performance impact is minimal.

## Changelog

### 1.2
- Added multilingual support (i18n)
- Included French translation (fr_FR)
- Added POT file for translators
- Improved text domain implementation

### 1.1
- Added plugin information API for update details modal
- Enhanced GitHub update integration

### 1.0
- Initial release
- Login status control (everyone/logged in/logged out)
- WordPress role-based restrictions
- Restrict Content Pro membership level integration
- Restrict Content Pro access level support
- Accordion-style admin interface
- Automatic parent-child menu handling
  
## Author

**Guilamu**

## License

This plugin is provided as-is. Please test thoroughly before using in production environments. AGPL-3.0 license.

## Support

For bug reports, feature requests, or contributions, please visit the [GitHub repository](https://github.com/guilamu/Advanced-Menu-Items-Visibility-Control).

---

**Note**: This plugin modifies menu visibility on the frontend. Always test your menu configurations with different user roles and login states to ensure they work as expected.
