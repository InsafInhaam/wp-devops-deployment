<?php

// Backup and Revert Page
function wp_devops_backup_revert_page() {
    // Fetch the saved options
    $token = get_option('wp_devops_github_token', '');
    $selected_repo = get_option('wp_devops_selected_repo', '');
    $selected_branch = get_option('wp_devops_selected_branch', '');

    // Validate repository and token
    if (!$token || !$selected_repo || !$selected_branch) {
        echo '<div class="notice notice-error"><p>Please configure your GitHub settings in the main WP DevOps menu first.</p></div>';
        return;
    }

    // Parse repository details
    list($owner, $repo_name) = explode('/', $selected_repo);
    $repo_url = escapeshellarg("https://$token@github.com/$owner/$repo_name.git");
    $repo_path = WP_DEVOPS_PLUGIN_DIR . "repos/$repo_name"; // Adjust as needed
    $backup_dir = WP_DEVOPS_PLUGIN_DIR . 'backups';

    // Handle form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = sanitize_text_field($_POST['action']);

        if ($action === 'backup') {
            $output = wp_devops_backup_current_state($repo_path, $backup_dir);
            echo '<div class="notice notice-success"><p>' . esc_html($output) . '</p></div>';
        } elseif ($action === 'revert') {
            $commit_sha = sanitize_text_field($_POST['commit']);
            $output = wp_devops_revert_to_commit($repo_path, $commit_sha);
            echo '<div class="notice notice-success"><p>' . esc_html($output) . '</p></div>';
        }
    }

    // Fetch commits
    $commits = wp_devops_fetch_commits($owner, $repo_name, $token);

    echo '<div class="wrap">';
    echo '<h1>Backup & Revert</h1>';
    echo '<form method="post">';

    // Backup button
    echo '<button type="submit" name="action" value="backup" class="button">Backup Current State</button><br><br>';

    // Revert section
    if ($commits) {
        echo '<label for="commit">Select a Commit to Revert:</label><br>';
        echo '<select id="commit" name="commit">';
        foreach ($commits as $commit) {
            echo '<option value="' . esc_attr($commit['sha']) . '">' . esc_html($commit['commit']['message']) . ' (' . esc_html($commit['sha']) . ')</option>';
        }
        echo '</select><br><br>';
        echo '<button type="submit" name="action" value="revert" class="button button-primary">Revert to Commit</button>';
    } else {
        echo '<p>No commits found. Please check your repository settings.</p>';
    }

    echo '</form>';
    echo '</div>';
}

// Fetch commit history from GitHub
function wp_devops_fetch_commits($owner, $repo_name, $token) {
    $url = "https://api.github.com/repos/$owner/$repo_name/commits";
    $headers = [
        'User-Agent: WP-DevOps-Plugin',
        'Authorization: Bearer ' . $token
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// Backup current state
function wp_devops_backup_current_state($local_path, $backup_dir) {
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }

    $timestamp = date('Y-m-d_H-i-s');
    $backup_path = $backup_dir . '/backup_' . $timestamp;

    $command = 'cp -r ' . escapeshellarg($local_path) . ' ' . escapeshellarg($backup_path);
    exec($command, $output, $status);

    return $status === 0 ? "Backup created at $backup_path" : 'Error: Failed to create backup.';
}

// Revert to a specific commit
function wp_devops_revert_to_commit($repo_path, $commit_sha) {
    $repo_path = escapeshellarg($repo_path);
    $commit_sha = escapeshellarg($commit_sha);

    $command = "cd $repo_path && git reset --hard $commit_sha";
    exec($command, $output, $status);

    return $status === 0 ? implode("\n", $output) : 'Error: Failed to revert to the specified commit.';
}