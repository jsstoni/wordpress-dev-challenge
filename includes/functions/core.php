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
    $content = get_post_meta($post->ID, 'citation', true);
    wp_nonce_field("citation_validate", "citation_nonce");

    var_dump(get_option('wrong_urls'));

    wp_editor( $content, 'citation', array('textarea_rows' => 3, 'media_buttons' => true, 'tinymce' => true) );
}

function save_post_box_citation($post_id)
{
    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    if (!isset($_POST['citation_nonce']) || !wp_verify_nonce($_POST['citation_nonce'], 'citation_validate')) return;

    if (isset($_POST['citation'])) {
        update_post_meta($post_id, 'citation', sanitize_text_field($_POST['citation']));
    }
}

function short_code_mc_citacion($atts)
{
    $post = get_post();
    $post_id = $post->ID;

    $atts = shortcode_atts( array(
        'post_id' => $post_id
    ), $atts, 'mc-citacion' );

    $content = get_post_meta($atts['post_id'], 'citation', true);
    return $content;
}

function create_wrong_links()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'wrong_url';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        url text NOT NULL,
        estado text NOT NULL,
        origen text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

function admin_menu_url()
{
    //Menu
}

function cron_active()
{
    if( ! wp_next_scheduled( 'cron_hook' ) ) {
        wp_schedule_event( time(), '60seconds', 'cron_hook' );
    }
}

function get_all_url()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'wrong_url';
    $check_old = $wpdb->get_results("SELECT origen FROM {$table_name}", ARRAY_A);
    
    $args = array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'numberposts' => -1,
        'exclude' => array_column($check_old, 'origen')
    );
    $posts = array_column(get_posts($args), 'post_content', 'ID');

    $status = "";
    $data = array();
    foreach($posts as $ID => $content) {
        if (preg_match_all('/<a .*?href=.*?"(.*?)"(.|\n)*?>((.|\n)*?)<.*?\/a.*?>/', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $k => $mach) {
                if (!filter_var($mach[1], FILTER_VALIDATE_URL)) {
                    $status = "Mal formado";
                }else if (!preg_match('/^(?:https?\:)\/\//', $mach[1])) {
                    $status = "No protocolo";
                }else if (preg_match('/^(?:http?\:)\/\//', $mach[1])) {
                    $status = "Inseguro";
                }else {
                    $ch = curl_init($mach[1]);
                    curl_setopt($ch, CURLOPT_HEADER, true);
                    curl_setopt($ch, CURLOPT_NOBODY, true);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    $output = curl_exec($ch);
                    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    if ($httpcode != 200) {
                        $status = "{$httpcode} Not found";
                    }else {
                        continue;
                    }
                }
                $wpdb->insert( $table_name, array(
                    'url' => $mach[1],
                    'estado' => $status,
                    'origen' => $ID)
                );
            }
        }
    }
}

function wp_cron_schedules($schedules) {
    $schedules['60seconds'] = array(
        'interval' => 60,
        'display'  => '60 segundos'
    );
     return $schedules;
}

function deactivation_cron()
{
    wp_clear_scheduled_hook( 'cron_hook' );
}