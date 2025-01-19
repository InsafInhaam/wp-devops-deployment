<?php
function wp_devops_deployment_page()
{
    $config_path = WP_CONTENT_DIR . '/plugins/wp-devops-plugin/deployment-config.json';
    $repo_path = WP_CONTENT_DIR . '/plugins/wp-devops-plugin/repos';
    $log_path = WP_CONTENT_DIR . '/plugins/wp-devops-plugin/logs/wp-devops-plugin.log';
    $themes_path = get_theme_root();

    // Load existing settings
    $settings = file_exists($config_path) ? json_decode(file_get_contents($config_path), true) : [];
    $repos = is_dir($repo_path) ? array_diff(scandir($repo_path), ['.', '..']) : [];
    $test_connection_result = '';

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = sanitize_text_field($_POST['action']);

        if ($action === 'save_settings') {
            $settings = [
                'server_ip' => sanitize_text_field($_POST['server_ip']),
                'username' => sanitize_text_field($_POST['username']),
                'ssh_key' => sanitize_text_field($_POST['ssh_key']),
                'remote_dir' => sanitize_text_field($_POST['remote_dir']),
                'port' => sanitize_text_field($_POST['port']),
            ];
            file_put_contents($config_path, json_encode($settings));
            wp_devops_log_message($log_path, 'Settings saved: ' . json_encode($settings));
            echo '<div class="updated"><p>Settings saved successfully.</p></div>';
        } elseif ($action === 'test_connection') {
            $settings = [
                'server_ip' => sanitize_text_field($_POST['server_ip']),
                'username' => sanitize_text_field($_POST['username']),
                'ssh_key' => sanitize_text_field($_POST['ssh_key']),
                'port' => sanitize_text_field($_POST['port']),
            ];
            $test_connection_result = wp_devops_test_connection($settings);
            wp_devops_log_message($log_path, 'Test Connection Result: ' . $test_connection_result);
        } elseif ($action === 'deploy') {
            $selected_repo = sanitize_text_field($_POST['selected_repo'] ?? '');
            if (!empty($selected_repo)) {
                $repo_full_path = $repo_path . '/' . $selected_repo;
                $output = wp_devops_deploy_files($repo_full_path, $themes_path, $settings, $log_path);
                echo '<h3>Deployment Output:</h3><pre>' . esc_html($output) . '</pre>';
            }
        }
    }

    // Render forms
    echo '<h1>Deploy to Staging/Production</h1>';
    echo '<form method="post">';
    echo '<h2>Deployment Settings</h2>';
    echo '<label for="server_ip">Server IP:</label><br>';
    echo '<input type="text" id="server_ip" name="server_ip" value="' . esc_attr($settings['server_ip'] ?? '') . '" required size="50"><br><br>';
    echo '<label for="username">SSH Username:</label><br>';
    echo '<input type="text" id="username" name="username" value="' . esc_attr($settings['username'] ?? '') . '" required size="50"><br><br>';
    echo '<label for="ssh_key">Path to SSH Private Key:</label><br>';
    echo '<textarea id="ssh_key" name="ssh_key" required rows="4" cols="50">' . esc_textarea($settings['ssh_key'] ?? '') . '</textarea><br><br>';
    echo '<label for="port">SSH Port:</label><br>';
    echo '<input type="text" id="port" name="port" value="' . esc_attr($settings['port'] ?? '') . '" required size="50"><br><br>';
    echo '<label for="remote_dir">Remote Directory:</label><br>';
    echo '<textarea id="remote_dir" name="remote_dir" required rows="4" cols="50">' . esc_textarea($settings['remote_dir'] ?? '') . '</textarea><br><br>';
    echo '<button type="submit" name="action" value="save_settings" class="button button-primary"><i class="fas fa-save"></i> Save Settings</button>';
    echo ' <button type="submit" name="action" value="test_connection" class="button"><i class="fas fa-plug"></i> Test Connection</button>';
    echo '</form><br>';

    if ($test_connection_result) {
        echo '<div class="updated"><p><strong>Test Connection Result:</strong> ' . esc_html($test_connection_result) . '</p></div>';
    }

    echo '<form method="post">';
    echo '<h2>Deploy Repository</h2>';
    echo '<label for="selected_repo">Select Repository:</label><br>';
    echo '<select id="selected_repo" name="selected_repo" required>';
    foreach ($repos as $repo) {
        echo '<option value="' . esc_attr($repo) . '">' . esc_html($repo) . '</option>';
    }
    echo '</select><br><br>';
    echo '<button type="submit" name="action" value="deploy" class="button button-primary"><i class="fas fa-rocket"></i> Deploy</button>';
    echo '</form><br>';

    echo '<h2>View Logs</h2>';
    if (file_exists($log_path)) {
        echo '<pre style="background: #f7f7f7; padding: 10px; margin-right: 20px; border: 1px solid #ccc; max-height: 300px; overflow-y: scroll;">' . esc_html(file_get_contents($log_path)) . '</pre>';
    } else {
        echo '<p>No logs available.</p>';
    }
}

function wp_devops_test_connection($settings)
{
    $server_ip = escapeshellarg($settings['server_ip']);
    $username = escapeshellarg($settings['username']);
    $ssh_key = $settings['ssh_key'];
    $port = escapeshellarg($settings['port']);

    $command = '/usr/bin/ssh -i "' . $ssh_key . '" ' . $username . '@' . $server_ip . ' -p ' . $port . ' "echo Connection successful"';
    exec($command, $output, $status);

    return $status === 0 ? implode("\n", $output) : 'Error: Unable to connect to the server. Output: ' . implode("\n", $output);
}

function wp_devops_log_message($log_path, $message)
{
    $timestamp = date('Y-m-d H:i:s');
    $formatted_message = "[$timestamp] $message\n";
    file_put_contents($log_path, $formatted_message, FILE_APPEND);
}
