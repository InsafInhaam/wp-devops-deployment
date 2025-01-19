<?php
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue CodeMirror scripts and styles.
add_action('admin_enqueue_scripts', function () {
    wp_enqueue_script('codemirror-js', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.12/codemirror.min.js', [], null, true);
    wp_enqueue_script('codemirror-mode', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.12/mode/javascript/javascript.min.js', ['codemirror-js'], null, true);
    wp_enqueue_style('codemirror-css', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.12/codemirror.min.css', [], null);
});

// File manager page content.
function wp_devops_file_manager_page()
{
    $repo_path = WP_CONTENT_DIR . '/plugins/wp-devops-plugin/repos'; // Path to cloned repository.

    if (!is_dir($repo_path)) {
        echo '<div class="error"><p>Repository not found at ' . esc_html($repo_path) . '</p></div>';
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_content'], $_POST['file_path'])) {
        $file_path = sanitize_text_field($_POST['file_path']);
        $new_content = stripslashes($_POST['file_content']);
        if (file_put_contents($file_path, $new_content)) {
            echo '<div class="updated"><p>File saved successfully!</p></div>';
        } else {
            echo '<div class="error"><p>Failed to save the file.</p></div>';
        }
    }

    if (isset($_GET['file'])) {
        wp_devops_file_editor(urldecode($_GET['file']));
    } else {
        $files = wp_devops_get_files($repo_path);
        echo '<h1>Cloned Repository Files</h1>';
        echo '<p>Manage your cloned repository files here.</p>';
        wp_devops_display_file_tree($files);
    }
}

// Get files and directories in a directory.
function wp_devops_get_files($directory)
{
    $files = [];
    $items = scandir($directory);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $directory . DIRECTORY_SEPARATOR . $item;
        $files[] = [
            'name' => $item,
            'path' => $path,
            'type' => is_dir($path) ? 'directory' : 'file',
        ];
    }
    return $files;
}


// Display a file tree.
function wp_devops_display_file_tree($files)
{
    echo '<div class="wp-devops-file-tree"><ul>';
    foreach ($files as $file) {
        echo '<li>';
        if ($file['type'] === 'directory') {
            echo '<span class="toggle-icon">▶</span>';
            echo '<strong class="toggle-dir-text">' . esc_html($file['name']) . '</strong>';
            $sub_files = wp_devops_get_files($file['path']);
            echo '<div class="sub-files" style="display: none;">';
            wp_devops_display_file_tree($sub_files);
            echo '</div>';
        } else {
            echo '<a href="?page=wp-devops-file-manager&file=' . urlencode($file['path']) . '">' . esc_html($file['name']) . '</a>';
        }
        echo '</li>';
    }
    echo '</ul></div>';
}

echo '<script>
    document.addEventListener("DOMContentLoaded", function () {
        var toggles = document.querySelectorAll(".toggle-icon");
        toggles.forEach(function(toggle) {
            toggle.addEventListener("click", function() {
                var subFiles = this.nextElementSibling.nextElementSibling;
                if (subFiles.style.display === "none") {
                    subFiles.style.display = "block";
                    this.textContent = "▼";
                } else {
                    subFiles.style.display = "none";
                    this.textContent = "▶";
                }
            });
        });
    });
</script>';


// Display file editor.
function wp_devops_file_editor($file_path)
{
    if (!file_exists($file_path)) {
        echo '<div class="error"><p>File not found.</p></div>';
        return;
    }

    $file_content = file_get_contents($file_path);

    echo '<h2>Editing File: ' . esc_html(basename($file_path)) . '</h2>';
    echo '<form method="post">';
    echo '<textarea id="file-editor" name="file_content" rows="20" cols="80">' . esc_textarea($file_content) . '</textarea>';
    echo '<input type="hidden" name="file_path" value="' . esc_attr($file_path) . '">';
    echo '<p><button type="submit" class="button button-primary"><i class="fas fa-check-circle"></i>
 Save Changes</button></p>';
    echo '</form>';

    echo '<script>
        document.addEventListener("DOMContentLoaded", function () {
            var editor = CodeMirror.fromTextArea(document.getElementById("file-editor"), {
                lineNumbers: true,
                mode: "javascript",
                theme: "default",
            });
        });
    </script>';
}

