<?php
/*
Plugin Name: WP DevOps Plugin
Description: A simple WordPress plugin to integrate with GitHub for version control.
Version: 1.0
Author: Insaf Inhaam
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin constants.
define('WP_DEVOPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_DEVOPS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include GitHub API handler.
require_once WP_DEVOPS_PLUGIN_DIR . 'includes/github-api.php';

// Include WP DevOps File Manager.
require_once WP_DEVOPS_PLUGIN_DIR . 'includes/wp-devops-file-manager.php';

// Enqueue the custom CSS file
add_action('admin_enqueue_scripts', 'wp_devops_enqueue_styles');

function wp_devops_enqueue_styles($hook)
{
    // Load the CSS only on your plugin's admin page.
    if ('toplevel_page_wp-devops' === $hook) {
        wp_enqueue_style('wp-devops-styles', WP_DEVOPS_PLUGIN_URL . 'assets/style.css');
    }
    wp_enqueue_style('font-awesome', plugin_dir_url(__FILE__) . 'assets/fontawesome/css/all.min.css', array(), null);
}

// Add menu item to the WordPress admin dashboard.
require_once WP_DEVOPS_PLUGIN_DIR . 'includes/wp_devops_add_menu.php';


// Include WP DevOps Clone Repo.
require_once WP_DEVOPS_PLUGIN_DIR . 'includes/wp_devops_clone_repo.php';


// Include WP DevOps Git Sync Page.
require_once WP_DEVOPS_PLUGIN_DIR . 'includes/wp_devops_git_sync_page.php';


// Include WP DevOps Deployment Page.
require_once WP_DEVOPS_PLUGIN_DIR . 'includes/wp_devops_deployment_page.php';

