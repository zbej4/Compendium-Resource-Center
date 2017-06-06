<?php
/**
 * Plugin Name: Compendium Resource Center
 * Description: Provides the functionality to create and maintain a resource center that integrates several post types and categories.
 * Version:	 0.1
 * Author: Brandon Jones
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**--------------------------------------------------------
 *
 *  The default settings for the plugin.
 *
 *-------------------------------------------------------*/
global $compendium_save_as; // the `option_name` field in the `wp_options` table
$compendium_save_as = 'compendiumresourcecenter';

//Get Post types
$post_types = get_post_types();

global $compendium_option_list;
foreach ($post_types as $type) {
    $compendium_option_list[] = array(
        'description'   => $type,
        'db_name'       => 'active-'.$type,
        'init'          => '0'
    );
}



/**--------------------------------------------------------
 *
 *  Enqueue styles and scripts
 *
 *-------------------------------------------------------*/
function compendium_scripts() {
    wp_register_script( 'compendium-js', plugins_url( '/js/scripts.js', __FILE__ ), array( 'jquery' ) );
    wp_register_script( 'compendium-jquery-ui', plugins_url( 'jquery-ui', 'https://code.jquery.com/ui/1.12.1/jquery-ui.min.js', __FILE__ ), array( 'jquery' ), null, true );
    wp_register_script( 'compendium-page-js', plugins_url( '/js/page-scripts.js', __FILE__ ), array( 'jquery' ) );
    wp_enqueue_script( 'compendium-js' );
    wp_enqueue_script( 'compendium-jquery-ui' );
    wp_enqueue_script( 'compendium-page-js' );
}
add_action( 'wp_enqueue_scripts', 'compendium_scripts', 10 );

function resource_styles() {
    wp_register_style( 'compendium-css', plugins_url( '/css/styles.css', __FILE__ ) );
    wp_register_style( 'jquery-ui', plugins_url('css/jquery-ui.min.css', __FILE__ ), array(), null );
    wp_register_style( 'jquery-ui-structure', plugins_url('css/jquery-ui.structure.min.css', __FILE__ ), array('jquery-ui'), null );
    wp_register_style( 'jquery-ui-theme', plugins_url('css/jquery-ui.theme.min.css', __FILE__ ), array('jquery-ui'), null );
    wp_enqueue_style( 'compendium-css' );
    wp_enqueue_style( 'jquery-ui' );
    wp_enqueue_style( 'jquery-ui-structure' );
    wp_enqueue_style( 'jquery-ui-theme' );
}
add_action( 'wp_enqueue_scripts', 'compendium_styles' );

/**--------------------------------------------------------
 *
 *  Activation function
 *
 *-------------------------------------------------------*/
function compendium_activate() {
    global $compendium_option_list, $compendium_save_as;

    $init_options = array();

    foreach($compendium_option_list as $option) {
        $init_options[$option['db_name']] = $option['init'];
    }

    add_option('compendium-posts-per-page', array('description' => 'Posts per page', 'value' => 22));
    add_option($compendium_save_as, $init_options);
}
register_activation_hook( __FILE__, 'compendium_activate' );

/**--------------------------------------------------------
 *
 *  Add a "Settings" link in the Plugin list entry
 *
 *-------------------------------------------------------*/
add_filter('plugin_action_links', 'compendium_plugin_action_links', 10, 2);

function compendium_plugin_action_links($links, $file) {
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=compendium-resource-center">Settings</a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}

/**--------------------------------------------------------
 *
 *  Register Filter/Search Variables for Resource Center
 *
 *-------------------------------------------------------*/
function add_query_vars_filter( $vars ) {
    $vars[] = "topic-filter";
    $vars[] = "type-filter";
    $vars[] = "k_search";
    return $vars;
}
add_filter( 'query_vars', 'add_query_vars_filter' );

/**--------------------------------------------------------
 *
 *  Function that outputs when shortcode is called
 *
 *-------------------------------------------------------*/
function show_compendium_content(){
    global $compendium_option_list;
    ob_start();
    echo '<h3>Compendium Resource Center</h3>'.PHP_EOL;
    global $post; if( !empty($post->post_content) ) : ?>
        <div class="inner clearfix">
            <article class="basic-content editor-styles form-styles">
                <?php
                    while( have_posts() )
                    {
                        the_post();
                        the_content();
                    }
                ?>
            </article>
        </div>
        <?php endif; ?>
    <?php  Compendium_Resources::do_resoures();
    return ob_get_clean();
}

/**--------------------------------------------------------
 *
 *  Register shortcode to display resource center
 *
 *-------------------------------------------------------*/
function compendium_resource_center($atts, $content = null) {
    global $compendium_save_as;
    $options = shortcode_atts( array(
        'adminonly' => 'true'
    ), $atts);

    $compendiumoptions = get_option($compendium_save_as);

    if( is_super_admin() ) {
        return show_compendium_content($compendiumoptions);
    }
    return false;
}
add_shortcode('compendium', 'compendium_resource_center');


/**--------------------------------------------------------
 *
 *  WP Admin page
 *
 *-------------------------------------------------------*/
add_action( 'admin_menu', 'admin_resource' );
function admin_resource() {
    add_options_page( 'Compendium Resource Center Settings', 'Compendium Resource Center', 'manage_options', 'compendium-resource-center', 'compendium_resource_options' );
}


function compendium_resource_options() {
    if ( !current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    // variables for the field and option names
    global $compendium_option_list, $compendium_save_as, $post_types;


    $hidden_field_name = 'compendium_submit_hidden';


    // Read in existing option value from database
    $compendium_options = get_option($compendium_save_as);
    $compendium_posts_per_page = get_option('compendium-posts-per-page');


    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
        // Read the values
        foreach($compendium_option_list as $option) {
            if(isset($_POST[$option['db_name']])) {
                $compendium_options[$option['db_name']] = $_POST[$option['db_name']];
            }
            else {
                $compendium_options[$option['db_name']] = "0";
            }
        }
        $compendium_posts_per_page['value'] = $_POST['posts-per-page'];

        // Save the values in the database
        update_option($compendium_save_as, $compendium_options);
        update_option('compendium-posts-per-page', $compendium_posts_per_page);

        // Display a "settings saved" message on the screen
        echo '<div class="updated"><p><strong>Settings saved.</strong></p></div>';

    }
    ?>


    <div class="wrap">
        <h2>Compendium Resource Center Settings</h2>
        <form name="resource_options" method="post" action="">
            <div class="metabox">
                <div class="inside">
                    <h3>Please select the posts types to be displayed in resource center.</h3>
                    <input type="hidden" name="<?=$hidden_field_name?>" value="Y">

                    <?php
                    foreach($compendium_option_list as $option) {
                        ?>
                        <p>
                            <input name="<?=$option['db_name']?>" type="checkbox" value="1" <?php if ($compendium_options[$option['db_name']] === '1') { echo ' checked="checked"'; } ?> />
                            &nbsp; <?=$option['description']?>
                        </p>

                        <?php
                    }

                    ?>
                </div>
            </div>
            <p>
                <?=$compendium_posts_per_page['description']?>
                <input name="posts-per-page" type="number" value="<?=$compendium_posts_per_page['value'] ?>" />
            </p>

            <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
            </p>

            <p>Add the shortcode [compendium] to the page you wish it to be displayed on.</p>

        </form>
    </div>
    <?php
}