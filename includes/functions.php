<?php

/*
 * WordPress functions
 */
function wp_get_stats() {
    $stats = array();
    $stats['num_users'] = get_user_count();
    $stats['num_networks'] = get_blog_count();

    return $stats;
}

/*
 * Utility functions
 */

// log things to the web server log
function logger($message) {
    if (MAUTIC_DEBUG) {
        error_log('DEBUG: '.$message);
    }
}

// construct JSON responses to AJAX queries
function response($a) {
    echo json_encode($a);
    die();
}
