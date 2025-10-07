<?php
if (!defined('ABSPATH')) exit;

/**
 * Add admin menu page
 */
add_action('admin_menu', function() {
    add_menu_page(
        'CI API Settings',
        'CI API Settings',
        'manage_options',
        'ci-api-settings',
        'ci_api_settings_page_html',
        'dashicons-admin-generic',
        90
    );
});

/**
 * Register setting
 */
add_action('admin_init', function() {
    register_setting('ci_api_options_group', 'ci_user_id');
});

/**
 * HTML for settings page
 */
function ci_api_settings_page_html() {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="wrap">
        <h1>CodeIgniter API Integration</h1>
        <form method="post" action="options.php">
            <?php settings_fields('ci_api_options_group'); ?>
            <?php do_settings_sections('ci_api_options_group'); ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">User ID</th>
                    <td>
                        <input type="text" name="ci_user_id" 
                               value="<?php echo esc_attr(get_option('ci_user_id', '')); ?>" 
                               style="width: 100px;" />
                    </td>
                </tr>
            </table>

            <?php submit_button('Save Settings'); ?>
        </form>
    </div>
    <?php
}