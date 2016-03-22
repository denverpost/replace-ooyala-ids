<?php
/*/
Plugin Name: Ooyala/Inform ID replacer - CANNABIST edition
Plugin URI: http://www.denverpost.com
Description: Takes a CSV of Ooyala and Inform video IDs and swaps them in posts' custom fields on The Cannabist.
Version: 0.2b
Author: Daniel J. Schneider
Author URI: http://schneidan.com
/*/

/**
 * Everything up here is about the plugin options in the admin area
 */

defined('ABSPATH') or die("No script kiddies please!");

function dpoo_install_callback(){
    global $dpoo_options;
}
register_activation_hook(__FILE__, 'dpoo_install_callback');

// add the admin settings and such
function dpoo_admin_init(){
    register_setting( 'dpoo_options', 'dpoo_options', 'dpoo_options_validate' );
    add_settings_section('dpoo_test', 'Test Mode Settings', 'dpoo_test_text', 'dpoo');
    add_settings_field('dpoo_test_mode', 'Enable test mode?', 'dpoo_test_string', 'dpoo', 'dpoo_test');
    add_settings_field('dpoo_test_rows', 'Limit test rows (applies only during tests)', 'dpoo_test_rows_string', 'dpoo', 'dpoo_test');
    add_settings_section('dpoo_input', 'Input CSV', 'dpoo_csv_text', 'dpoo');
    add_settings_field('dpoo_csv', 'Paste in contents of CSV file', 'dpoo_csv_string', 'dpoo', 'dpoo_input');
}
add_action('admin_init', 'dpoo_admin_init');

// display the admin options page
function dpoo_options_page() {
    if( isset($_GET['settings-updated']) ) {
        $options = get_option('dpoo_options');
        add_settings_error('results','results','<p>IDs updated. Full report:</p>' ."\n"."\n". $options['dpoo_results'],'updated');
    } ?>
    <div>
    <h2>Denver Post Ooyala/Inform video ID replacer</h2>
    <?php settings_errors(); ?>
    <form action="options.php" method="POST">
    <?php settings_fields('dpoo_options'); ?>
    <?php do_settings_sections('dpoo'); ?>
     
    <input name="Submit" type="submit" value="<?php esc_attr_e('Replace Oyala IDs'); ?>" />
    </form></div>
<?php
}

// add the admin options page
function dpoo_admin_add_page() {
    add_options_page('Ooyala/Inform replacer Plugin Page', 'Ooyala/Inform replacer', 'manage_options', 'dpoo_options', 'dpoo_options_page');
}
add_action('admin_menu', 'dpoo_admin_add_page');

function dpoo_test_text() {
    echo '<p>Test mode reports the IDs of the posts it finds that it would change. Run a test first. For speed, you can run a limited test by entering the number of iterations to run for.</p>';
}
function dpoo_test_string() {
    $options = get_option('dpoo_options');
    echo "<input id='dpoo_test_mode_enable' name='dpoo_options[dpoo_test_mode_enable]' type='checkbox' value='1' " . checked( $options['dpoo_test_mode_enable'], 1, false) . " />";
}

function dpoo_test_rows_string() {
    $options = get_option('dpoo_options');
    echo "<input id='dpoo_limit_rows' name='dpoo_options[dpoo_limit_rows]' size='40' type='text' value='{$options['dpoo_limit_rows']}' /> (<i>Enter 0 or leave blank to remove limit</i>)";
}

function dpoo_csv_text() {
    echo '<p>Paste in an appropriately formatted CSV file in the format: <strong>Inform ID, Ooyala ID,</strong> (yep, just those two columns -- and both commas)</p>';
}
function dpoo_csv_string() {
    $options = get_option('dpoo_options');
    echo '<textarea id="dpoo_csv_input" name="dpoo_options[dpoo_csv_input]" style="width:100%;height:500px;">' . $options['dpoo_csv_input'] . '</textarea>';
}


// validate our options
function dpoo_options_validate($input) {
    $newinput['dpoo_csv_input'] = '';
    $newinput['dpoo_limit_rows'] = ( !empty($input['dpoo_limit_rows']) && is_numeric($input['dpoo_limit_rows']) ) ? (int)$input['dpoo_limit_rows'] : false;
    $newinput['dpoo_test_mode_enable'] = ( $input['dpoo_test_mode_enable'] ) ? 1 : 0;
    $newinput['dpoo_results'] = run_replace($input['dpoo_csv_input'],$newinput['dpoo_test_mode_enable'],$newinput['dpoo_limit_rows']);
    return $newinput;
}

/**
 * Actually doing stuff down here
 */



function new_markup($video_id) {
    return '<div id="mainplayer" class="ndn_embed" data-config-widget-id="2" data-config-type="VideoPlayer/Single" data-config-tracking-group="90115" data-config-playlist-id="18497" data-config-video-id="' . $video_id . '" data-config-site-section="denverpost" data-config-height="9/16w"></div>';
}

function run_replace( $csv, $test, $test_rows ) {
    if ($test == 1) {
        $limit = ( $test_rows && $test_rows > 0 ) ? (int)$test_rows : false;
        $result = ( $limit ) ? '<p><strong>Test limited to ' . $limit . ' rows.</strong></p>' : '<p><strong>Test mode.</strong></p>';
    }
    $i=0;
    $lines = array();
    foreach (preg_split('/\r\n?|\n/', $csv) as $line) {
        $lines[] = str_getcsv($line);
    }
    foreach ($lines as $line) {
        if (!$limit || $i < $limit) {
            $post_ids_videos = get_post_from_video($line[1]);
            if (!empty($post_ids_videos[0])) {
                foreach ($post_ids_videos as $post_id_video) {
                    $result .= '<p><i>Found video id ' . $line[1] . ' attached to post: <a target="_blank" href="post.php?post=' . $post_id_video . '&action=edit">' . $post_id_video . '</a></i></p>';
                    $new_video_markup = new_markup($line[0]);
                    if (!$test) {
                        update_post_meta($post_id_video,'vid_embed',$new_video_markup);
                    }
                }
            } else {
                $result .= 'No posts found for video id ' . $line[1] . '</p>';
            }
        } else {
            break;
        }
        $i++;
    }
    return $result;
}

function get_post_from_video($videoid) {
    $ids = array();
    $args = array(
        'post_type'     => 'post',
        'meta_query'    => array(
            'relation'      => 'AND',
            array(
                'key'       => 'vid_embed',
                'value'     => $videoid,
                'compare'   => 'LIKE',
                ),
            array(
                'key'       => 'vid_embed',
                'value'     => 'ooyalaplayer',
                'compare'   => 'LIKE',
                ),
            ),
        );
    $post_query = new WP_Query($args);
    if ($post_query->have_posts()) {
        while ($post_query->have_posts()) {
            $post_query->the_post();
            $ids[] = get_the_ID();
        }
    }
    wp_reset_postdata();
    return $ids;
}

?>