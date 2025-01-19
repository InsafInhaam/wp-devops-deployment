<?php
function wp_devops_git_sync_page()
{
    $base_repo_path = WP_CONTENT_DIR . '/plugins/wp-devops-plugin/repos';

    // Get all available repositories in the `repos` directory.
    $repos = array_filter(glob($base_repo_path . '/*'), 'is_dir');

    if (!$repos) {
        echo '<div class="error"><p>No repositories found in ' . esc_html($base_repo_path) . '</p></div>';
        return;
    }

    // Handle form submission.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $selected_repo = sanitize_text_field($_POST['selected_repo'] ?? '');
        $repo_path = $base_repo_path . '/' . $selected_repo;

        if (!is_dir($repo_path)) {
            echo '<div class="error"><p>Invalid repository selected.</p></div>';
            return;
        }

        $action = sanitize_text_field($_POST['action'] ?? '');
        switch ($action) {
            case 'status':
                $output = wp_devops_git_command('status', $repo_path);
                break;
            case 'pull':
                $output = wp_devops_git_command('pull', $repo_path);
                break;
            case 'commit':
                $commit_message = sanitize_text_field($_POST['commit_message'] ?? '');
                if ($commit_message) {
                    wp_devops_git_command('add .', $repo_path);
                    $output = wp_devops_git_command("commit -m \"$commit_message\"", $repo_path);
                } else {
                    $output = ['Error: Commit message is required.'];
                }
                break;
            case 'push':
                $output = wp_devops_git_command('push', $repo_path);
                break;
            default:
                $output = ['Invalid action'];
                break;
        }
    }

    // Display the Git synchronization form.
    echo '<h1>Git Synchronization</h1>';
    echo '<p>Manage your Git repository synchronization here.</p>';

    // Form for selecting repository and managing Git commands.
    echo '<form method="post">';
    echo '<label for="selected_repo">Select Repository:</label>';
    echo '<select name="selected_repo" id="selected_repo" required style="margin-left: 10px;">';
    foreach ($repos as $repo) {
        $repo_name = basename($repo);
        $selected = isset($_POST['selected_repo']) && $_POST['selected_repo'] === $repo_name ? 'selected' : '';
        echo "<option value=\"$repo_name\" $selected>$repo_name</option>";
    }
    echo '</select>';
    echo '<br><br>';
    echo '<textarea type="text" name="commit_message" placeholder="Commit message" rows="5" cols="58"></textarea>';
    echo '<br><br>';
    echo '<button name="action" value="status" class="button"><i class="fas fa-sync-alt"></i> Git Status</button>';
    echo '&nbsp;';
    echo '<button name="action" value="pull" class="button"><i class="fas fa-download"></i> Git Pull</button>';
    echo '&nbsp;';
    echo '<button name="action" value="commit" class="button"><i class="fas fa-check"></i> Commit Changes</button>';
    echo '&nbsp;';
    echo '<button name="action" value="push" class="button"><i class="fas fa-upload"></i> Push to GitHub</button>';
    echo '</form>';

    // Display the command output, if available.
    if (isset($output)) {
        echo '<h3>Command Output:</h3><pre>' . esc_html(implode("\n", $output)) . '</pre>';
    }
}

// Execute Git commands.
function wp_devops_git_command($command, $repo_path)
{
    $git_path = '/usr/bin/git'; // Update this path based on your system.
    $full_command = "cd \"$repo_path\" && $git_path $command 2>&1";
    exec($full_command, $output, $status);

    if ($status !== 0) {
        $output[] = "Error: Command failed with status $status.";
    }

    return $output;
}
