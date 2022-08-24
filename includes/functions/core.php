<?php

if ( ! defined('ABSPATH') ) {
    die('Direct access not permitted.');
}

function meta_box_citation($post_type, $post)
{
    add_meta_box('citation_box_id', __( 'Citation' ), 'box_editor', 'post', 'normal', 'default');
}

function box_editor($post) {
    $content = get_post_meta($post->ID, 'citation_id', true);

    wp_editor( $content, 'citation_id', array('textarea_rows' => 3, 'media_buttons' => true, 'tinymce' => true) );
}