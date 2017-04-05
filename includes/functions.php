<?php

/*
 * WordPress functions
 */
function wp_get_stats() {
    $stats = array();
    $stats['num_users'] = count(get_users());
    $stats['num_networks'] = count(get_sites());

    return $stats;
}
