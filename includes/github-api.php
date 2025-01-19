<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Fetch GitHub repositories using the API.
function wp_devops_fetch_repositories($token) {
    $url = 'https://api.github.com/user/repos';
    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'User-Agent' => 'WP-DevOps-Plugin'
        ]
    ];

    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['message']) && $data['message'] === 'Bad credentials') {
        return [];
    }

    return $data;
}

// Fetch branches for a repository.
function wp_devops_fetch_branches($token, $owner, $repo) {
    $url = "https://api.github.com/repos/$owner/$repo/branches";
    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'User-Agent' => 'WP-DevOps-Plugin'
        ]
    ];

    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    return $data;
}

// Pull files from the selected branch.
function wp_devops_pull_files($token, $owner, $repo, $branch) {
    $url = "https://api.github.com/repos/$owner/$repo/contents?ref=$branch";
    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'User-Agent' => 'WP-DevOps-Plugin'
        ]
    ];

    $response = wp_remote_get($url, $args);

    if (is_wp_error($response)) {
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true);
}

// Push a file to GitHub.
function wp_devops_push_file($token, $owner, $repo, $path, $content, $branch) {
    $url = "https://api.github.com/repos/$owner/$repo/contents/$path";
    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'User-Agent' => 'WP-DevOps-Plugin',
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode([
            'message' => 'Updated via WP DevOps Plugin',
            'content' => base64_encode($content),
            'branch' => $branch
        ])
    ];

    $response = wp_remote_request($url, ['method' => 'PUT'] + $args);

    if (is_wp_error($response)) {
        return false;
    }

    return true;
}
