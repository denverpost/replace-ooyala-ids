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
    add_settings_section('dpovi_input', 'CSV', 'dpovi_csv_text', 'dpoo');
    add_settings_field('dpovi_csv', 'Copy resulting CSV', 'dpovi_csv_string', 'dpoo', 'dpovi_input');
}
add_action('admin_init', 'dpovi_admin_init');

// display the admin options page
function dpovi_options_page() {
    if( isset($_GET['settings-updated']) ) {
        $options = get_option('dpovi_options');
        add_settings_error('results','results','<p>Found a lod of video IDs. Copy them below.</p>','updated');
    } ?>
    <div>
    <h2>Denver Post Video ID discoverer</h2>
    <?php settings_errors(); ?>
    <form action="options.php" method="POST">
    <?php settings_fields('dpovi_options'); ?>
    <?php do_settings_sections('dpoo'); ?>
     
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
    $output_csv = make_array_csv($options['dpovi_results']);
    echo '<textarea id="dpovi_csv_input" name="dpovi_options[dpovi_csv_input]" style="width:100%;height:500px;">' . $output_csv . '</textarea>';
}


// validate our options
function dpovi_options_validate($input) {
    $newinput['dpovi_csv_input'] = '';
    $newinput['dpovi_limit_rows'] = ( !empty($input['dpovi_limit_rows']) && is_numeric($input['dpovi_limit_rows']) ) ? (int)$input['dpovi_limit_rows'] : false;
    $newinput['dpovi_test_mode_enable'] = ( $input['dpovi_test_mode_enable'] ) ? 1 : 0;
    $newinput['dpovi_results'] = get_post_video_ids();
    return $newinput;
}

/**
 * Actually doing stuff down here
 */

function make_array_csv($inputarray) {
    $returns = '';
    foreach ($inputarray as $line) {
        $returns .= $line[0].','.$line[1].','."\r\n";
    }
    return $returns;
}

function get_post_video_ids() {
    $ids = array();
    $args = array(
        'post_type'     => 'post',
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
            $ids[] = array(the_permalink(),get_post_meta(get_the_ID(),'video_id'));
        }
    }
    wp_reset_postdata();
    return $ids;
}

?>