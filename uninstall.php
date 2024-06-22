<?php

if (!defined('WP_UNINSTALL_PLUGIN'))
    exit();


function go_delete_now()
{
    global $wpdb;

    $posts = get_posts(array(
        'numberposts' => -1,
        'post_type' => 'meow_bookmark',
        'post_status' => 'any'
    ));

    foreach ($posts as $post) {
        wp_delete_post($post->ID, true);
    }
}

go_delete_now();

// Set global
global $wpdb;
// Delete terms
$wpdb->query("
    DELETE FROM
    {$wpdb->terms}
    WHERE term_id IN
    ( SELECT * FROM (
        SELECT {$wpdb->terms}.term_id
        FROM {$wpdb->terms}
        JOIN {$wpdb->term_taxonomy}
        ON {$wpdb->term_taxonomy}.term_id = {$wpdb->terms}.term_id
        WHERE taxonomy = 'meow_bookmark_taxonomy'
    ) as T
    );
");
// Delete taxonomies
$wpdb->query("DELETE FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'meow_bookmark_taxonomy'");
