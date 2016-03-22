<?php
/*/
Plugin Name: DPO Video ID discoverer
Plugin URI: http://www.denverpost.com
Description: Discovers posts with a 'video_id' custom field, and returns a CSV of URLs and video IDs.
Version: 0.1a
Author: Daniel J. Schneider
Author URI: http://schneidan.com
/*/

/**
 * Everything up here is about the plugin options in the admin area
 */

defined('ABSPATH') or die("No script kiddies please!");

function dpovi_install_callback(){
    global $dpovi_options;
}
register_activation_hook(__FILE__, 'dpovi_install_callback');

// add the admin settings and such
function dpovi_admin_init(){
    register_setting( 'dpovi_options', 'dpovi_options', 'dpovi_options_validate' );
    add_settings_section('dpovi_input', 'CSV', 'dpovi_csv_text', 'dpovi');
    add_settings_field('dpovi_csv', 'Copy resulting CSV', 'dpovi_csv_string', 'dpovi', 'dpovi_input');
}
add_action('admin_init', 'dpovi_admin_init');

// display the admin options page
function dpovi_options_page() {
    if( isset($_GET['settings-updated']) ) {
        add_settings_error('results','results','<p>Found a lot of video IDs. Copy them below.</p>','updated');
    } ?>
    <div>
    <h2>Denver Post Video ID discoverer</h2>
    <?php settings_errors(); ?>
    <form action="options.php" method="POST">
    <?php settings_fields('dpovi_options'); ?>
    <?php do_settings_sections('dpovi'); ?>
     
    <input name="Submit" type="submit" value="<?php esc_attr_e('Discover Video IDs'); ?>" />
    </form></div>
<?php
}

// add the admin options page
function dpovi_admin_add_page() {
    add_options_page('DPO Video ID discoverer Page', 'Video ID discoverer', 'manage_options', 'dpovi_options', 'dpovi_options_page');
}
add_action('admin_menu', 'dpovi_admin_add_page');

function dpovi_csv_text() {
    echo '<p>Copy and paste the CSV from here after running the video ID discovery.</p>';
}
function dpovi_csv_string() {
    $options = get_option('dpovi_options');
    echo '<textarea id="dpovi_csv_result" name="dpovi_options[dpovi_csv_result]" style="width:100%;height:500px;">' . $options['dpovi_csv_result'] . '</textarea>';
}


// validate our options
function dpovi_options_validate($input) {
    $newinput['dpovi_csv_result'] = dpovi_get_post_video_ids($input);
    return $newinput;
}

/**
 * Actually doing stuff down here
 */

function dpovi_get_post_video_ids($input) {
    $output_csv ='';
    $args = array(
        'post_type'     => 'post',
        'posts_per_page'=> -1,
        'meta_query'    => array(
            array(
                'key'       => 'video_id',
                'value'     => '',
                'compare'   => '!=',
                ),
            ),
        );
    $post_query = new WP_Query($args);
    if ($post_query->have_posts()) {
        while ($post_query->have_posts()) {
            $post_query->the_post();
            $output_csv .= get_permalink(get_the_ID()).','.get_post_meta(get_the_ID(),'video_id',true).','."\r\n";
        }
    }
    wp_reset_postdata();
    return $output_csv;
}

?>