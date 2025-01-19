<?php

add_action('admin_menu', 'wp_devops_add_menu');

function wp_devops_add_menu()
{
    // Main menu for WP DevOps.
    add_menu_page(
        'WP DevOps',
        'WP DevOps',
        'manage_options',
        'wp-devops',
        'wp_devops_admin_page',
        'dashicons-cloud', // Icon for the menu.
        80
    );

    // Submenu for File Manager.
    add_submenu_page(
        'wp-devops',
        'DevOps File Manager',
        'File Manager',
        'manage_options',
        'wp-devops-file-manager',
        'wp_devops_file_manager_page'
    );

    // Submenu for Git Synchronization tied to the main menu.
    add_submenu_page(
        'wp-devops',
        'Git Synchronization',
        'Git Sync',
        'manage_options',
        'wp-devops-git-sync',
        'wp_devops_git_sync_page'
    );

    // Add deployment page to the admin menu
    add_submenu_page(
        'wp-devops',
        'Deploy',
        'Deploy',
        'manage_options',
        'wp-devops-deployment',
        'wp_devops_deployment_page'
    );
}