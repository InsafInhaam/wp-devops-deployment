<?php

function wp_devops_clone_repo($token, $owner, $repo_name)
{
    $repo_url = escapeshellarg("https://$token@github.com/$owner/$repo_name.git");
    $clone_dir = escapeshellarg(WP_DEVOPS_PLUGIN_DIR . "repos/$repo_name");
    
    if (!file_exists($clone_dir)) {
        mkdir($clone_dir, 0755, true);
    }
    
    $output = [];
    $status = null;
    $git_path = '/usr/bin/git'; // Use the correct path to git.
    
    exec("{$git_path} clone $repo_url $clone_dir 2>&1", $output, $status);
    
    // Debug output
    if ($status !== 0) {
        echo "Error cloning repository: " . implode("\n", $output);
    } else {
        echo "<div class='updated'><p>Repository cloned successfully!</p></div>";
    }    
}

// Display the admin page content.
function wp_devops_admin_page()
{
    // Save GitHub token, repository, and branch selection.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['github_token'])) {
            update_option('wp_devops_github_token', sanitize_text_field($_POST['github_token']));
        }

        if (isset($_POST['selected_repo'])) {
            update_option('wp_devops_selected_repo', sanitize_text_field($_POST['selected_repo']));
        }

        if (isset($_POST['selected_branch'])) {
            update_option('wp_devops_selected_branch', sanitize_text_field($_POST['selected_branch']));
        }
    }

    // Fetch the saved options.
    $token = get_option('wp_devops_github_token', '');
    $selected_repo = get_option('wp_devops_selected_repo', '');
    $selected_branch = get_option('wp_devops_selected_branch', '');

    ?>
    <div class="devops-plugin-wrap">
        <h1>Welcome to WP DevOps</h1>

        <p>Connect to GitHub to manage your repositories and perform Git operations.</p>
        <!-- GitHub Token Form -->
        <form method="post" action="">
            <label for="github_token">GitHub Personal Access Token:</label>
            <input type="text" id="github_token" name="github_token" value="<?php echo esc_attr($token); ?>" size="50">
            <button type="submit" class="button button-primary"><i class="fas fa-key"></i> Save Token</button>
        </form>

        <hr>
        <h2>Your GitHub Repositories</h2>
        <?php
        if ($token) {
            $repos = wp_devops_fetch_repositories($token);
            if (!empty($repos)) {
                // Repository Selection Form
                echo '<form method="post" action="">';
                echo '<label for="selected_repo">Select a Repository:</label>';
                echo '<select name="selected_repo" id="selected_repo" onchange="this.form.submit()">';
                echo '<option value="">-- Select Repository --</option>';
                foreach ($repos as $repo) {
                    $selected = ($selected_repo === $repo['full_name']) ? 'selected' : '';
                    echo '<option value="' . esc_attr($repo['full_name']) . '" ' . $selected . '>' . esc_html($repo['name']) . '</option>';
                }
                echo '</select>';
                echo '</form>';

                if ($selected_repo) {
                    $repo_parts = explode('/', $selected_repo);
                    if (count($repo_parts) === 2) {
                        list($owner, $repo_name) = $repo_parts;
                        $branches = wp_devops_fetch_branches($token, $owner, $repo_name);
                        if (!empty($branches)) {
                            // Branch Selection Form
                            echo '<form method="post" action="">';
                            echo '<label for="selected_branch">Select a Branch:</label>';
                            echo '<select name="selected_branch" id="selected_branch">';
                            echo '<option value="">-- Select Branch --</option>';
                            foreach ($branches as $branch) {
                                $selected = ($selected_branch === $branch['name']) ? 'selected' : '';
                                echo '<option value="' . esc_attr($branch['name']) . '" ' . $selected . '>' . esc_html($branch['name']) . '</option>';
                            }
                            echo '</select>';
                            echo '<button type="submit" class="button button-primary"><i class="fas fa-code-branch"></i> Select Branch</button>';
                            echo '</form>';
                        }
                    }
                }

                if ($selected_repo && $selected_branch) {
                    $repo_parts = explode('/', $selected_repo);
                    if (count($repo_parts) === 2) {
                        list($owner, $repo_name) = $repo_parts;
                        $files = wp_devops_pull_files($token, $owner, $repo_name, $selected_branch);
                        if (!empty($files)) {
                            echo '<h3>Files in Branch: ' . esc_html($selected_branch) . '</h3>';
                            echo '<ul>';
                            foreach ($files as $file) {
                                if (isset($file['download_url']) && !empty($file['download_url'])) {
                                    echo '<li>';
                                    echo esc_html($file['name']);
                                    echo ' | <a href="' . esc_url($file['download_url']) . '" target="_blank">View</a>';
                                    echo '</li>';
                                } else {
                                    echo '<li>';
                                    echo '<a href="' . esc_url($file['html_url']) . '" target="_blank">' . esc_html($file['name']) . '</a>';
                                    echo '</li>';
                                }
                            }
                            echo '</ul>';
                        } else {
                            echo '<p>No files found or error occurred.</p>';
                        }
                    }
                }
            } else {
                echo '<p>No repositories found or invalid token.</p>';
            }
        } else {
            echo '<p>Please enter your GitHub Personal Access Token above.</p>';
        }


        if ($selected_repo && $selected_branch) {
            echo '<form method="post" action="">';
            echo '<button type="submit" name="clone_repo" class="button button-primary"><i class="fas fa-clone"></i> Clone Repository</button>';
            echo '</form>';

            if (isset($_POST['clone_repo'])) {
                $result = wp_devops_clone_repo($token, $owner, $repo_name);
                echo '<p>' . esc_html($result) . '</p>';
            }
        }
        ?>
    </div>
    <?php
}