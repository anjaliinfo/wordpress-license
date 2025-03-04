<?php
/*
Plugin Name: My Custom Plugin
Description: This plugin demonstrates license key activation and auto-updates.
Version: 1.0
Author: Your Name
*/

// Define constants
define('MY_PLUGIN_VERSION', '1.0');
define('MY_PLUGIN_SLUG', 'my-plugin');

// Activation hook: Generate and store a license key
register_activation_hook(__FILE__, 'my_plugin_activate');
function my_plugin_activate() {
    $license_key = generate_license_key(); // Function to generate a license key
    update_option('my_plugin_license_key', $license_key);
}

// Load plugin functionalities
add_action('init', 'my_plugin_init');
function my_plugin_init() {
    // Initialize your plugin functionalities here
    // Example: add_shortcode(), add_action(), etc.
}

// Admin menu: Settings page for license key activation and updates
add_action('admin_menu', 'my_plugin_add_menu');
function my_plugin_add_menu() {
    add_menu_page('My Plugin Settings', 'My Plugin', 'manage_options', MY_PLUGIN_SLUG, 'my_plugin_settings_page');
}

// Settings page content
function my_plugin_settings_page() {
    ?>
    <div class="wrap">
        <h2>My Plugin Settings</h2>
        <form method="post" action="">
            <label for="license_key">Enter License Key:</label>
            <input type="text" id="license_key" name="license_key" value="<?php echo esc_attr(get_option('my_plugin_license_key')); ?>">
            <input type="submit" name="activate_license" value="Activate License">
        </form>
    </div>
    <?php
}

// Process license activation
add_action('admin_init', 'my_plugin_process_activation');
function my_plugin_process_activation() {
    if (isset($_POST['activate_license'])) {
        $license_key = sanitize_text_field($_POST['license_key']);

        if (validate_license_key($license_key)) {
            update_option('my_plugin_license_key', $license_key);
            echo '<div class="updated"><p>License activated successfully!</p></div>';
        } else {
            echo '<div class="error"><p>Invalid license key!</p></div>';
        }
    }
}

// Example function to validate license key (replace with your validation logic)
function validate_license_key($key) {
    // Replace with your actual validation logic (check against database, remote server, etc.)
    $stored_key = get_option('my_plugin_license_key');
    return ($key === $stored_key);
}

// Function to check for plugin updates
add_filter('pre_set_site_transient_update_plugins', 'my_plugin_check_for_updates');
function my_plugin_check_for_updates($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    $license_key = get_option('my_plugin_license_key');
    $current_version = MY_PLUGIN_VERSION;

    // Replace with your actual update server URL and parameters
    $args = array(
        'license_key' => $license_key,
        'version' => $current_version,
    );
    $request = wp_remote_get('https://your-update-server.com/check-update.php?' . http_build_query($args));

    if (is_wp_error($request)) {
        return $transient;
    }

    $response = wp_remote_retrieve_body($request);
    if (!empty($response)) {
        $update_data = json_decode($response);
        if (version_compare($current_version, $update_data->new_version, '<')) {
            $transient->response[plugin_basename(__FILE__)] = (object) array(
                'slug' => MY_PLUGIN_SLUG,
                'new_version' => $update_data->new_version,
                'url' => '',
                'package' => $update_data->package_url,
            );
        }
    }

    return $transient;
}

// Function to handle plugin updates
add_filter('plugins_api', 'my_plugin_plugin_info', 20, 3);
function my_plugin_plugin_info($false, $action, $args) {
    if ($action !== 'plugin_information') {
        return false;
    }

    if (MY_PLUGIN_SLUG !== $args->slug) {
        return false;
    }

    $license_key = get_option('my_plugin_license_key');
    $current_version = MY_PLUGIN_VERSION;

    // Replace with your actual update server URL and parameters
    $args = array(
        'license_key' => $license_key,
        'version' => $current_version,
    );
    $request = wp_remote_get('https://your-update-server.com/plugin-info.php?' . http_build_query($args));

    if (is_wp_error($request)) {
        return false;
    }

    $response = wp_remote_retrieve_body($request);
    if (!empty($response)) {
        $plugin_info = json_decode($response);
        return $plugin_info;
    }

    return false;
}
