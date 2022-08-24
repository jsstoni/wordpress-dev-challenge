<?php

if ( ! defined('ABSPATH') ) {
    die('Direct access not permitted.');
}

function meta_box_citation()
{
    add_meta_box('citation_box_id', __( 'Citation' ), 'box_editor', 'post', 'normal', 'default');
}

function box_editor($post)
{
    $content = get_post_meta($post->ID, 'citation_id', true);

    wp_editor( $content, 'citation_id', array('textarea_rows' => 3, 'media_buttons' => true, 'tinymce' => true) );
}

function save_post_box_citation($post_id)
{
    if (isset($_POST['citation_id'])) {
        update_post_meta($post_id, 'citation_id', sanitize_text_field($_POST['citation_id']));
    }
}

function short_code_mc_citacion($atts)
{
    $post = get_post();
    $post_id = $post->ID;

    $atts = shortcode_atts( array(
        'post_id' => $post_id
    ), $atts, 'mc-citacion' );

    $content = get_post_meta($atts['post_id'], 'citation_id', true);
    return $content;
}

function admin_menu_url()
{
    echo "URL ERRROS";
}