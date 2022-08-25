<?php

if ( ! defined('ABSPATH') ) {
    die('Direct access not permitted.');
}

/**
 * Add citation editor
 * */
function meta_box_citation()
{
    add_meta_box('citation_box_id', __( 'Citation' ), 'box_editor', 'post', 'normal', 'default');
}

/**
 * @param object $post WP_Post get post object
 * @return void
 * */
function box_editor($post)
{
    $content = get_post_meta($post->ID, 'citation', true);
    wp_nonce_field("citation_validate", "citation_nonce");

    wp_editor( $content, 'citation', array('textarea_rows' => 3, 'media_buttons' => true, 'tinymce' => true) );
}

/** 
 * save post citation
 * @param int $post_id identifier of the publication to save
 * @return void
 * */
function save_post_box_citation($post_id)
{
    if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    if (!isset($_POST['citation_nonce']) || !wp_verify_nonce($_POST['citation_nonce'], 'citation_validate')) return;

    if (isset($_POST['citation'])) {
        update_post_meta($post_id, 'citation', sanitize_text_field($_POST['citation']));
    }
}

/**
 * shortcode [mc-citation post_id="1025"] or [mc-citation] show snippet of a post citation
 * @param array $atts all shortcode attributes
 * @return (mixed) citation content
 * */
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

/**
 * When activating the plugin, a table is created for the broken links
 * @return void
 * */
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

/**
 * menu view for bad links
 * @return void
 * */
function admin_menu_url()
{
    $Table_Wrong_Url = new Table_Wrong_Url();
?>
    <h1>Wrong Links</h1>
<?php
    $Table_Wrong_Url->prepare_items();
    $Table_Wrong_Url->display();
}

/**
 * activate the cronjob to fulfill the function
 * @return void
 * */
function cron_active()
{
    if( ! wp_next_scheduled( 'cron_hook' ) ) {
        wp_schedule_event( time(), 'hourly', 'cron_hook' );
    }
}

/**
 * function used to obtain all the url according to the error pattern
 * @return void
 * */
function get_all_url()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'wrong_url';

    //get the id of the already analyzed post
    $check_old = $wpdb->get_results("SELECT origen FROM {$table_name}", ARRAY_A);
    
    //get all posts except the ones that were already parsed
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

                //insert link information with error found
                $wpdb->insert( $table_name, array(
                    'url' => $mach[1],
                    'estado' => $status,
                    'origen' => $ID)
                );
            }
        }
    }
}

/**
 * disable the cronjob
 * @return void
 * */
function deactivation_cron()
{
    wp_clear_scheduled_hook( 'cron_hook' );
}
