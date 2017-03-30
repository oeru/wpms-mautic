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
