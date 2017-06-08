<?php
/**
 * Plugin Name: Compendium Resource Center
 * Description: Provides the functionality to create and maintain a resource center that integrates several post types and categories.
 * Version:	 0.7
 * Author: Brandon Jones
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
require_once dirname( __FILE__ ) .'/compendium-resources.php';

/**--------------------------------------------------------
 *
 *  The default settings for the plugin.
 *
 *-------------------------------------------------------*/
global $compendium_save_as; // the `option_name` field in the `wp_options` table
global $compendium_active_posts;
$compendium_save_as = 'compendiumresourcecenter';

//Get Post types after load
add_action( 'wp_loaded', 'compendium_get_post_types');
function compendium_get_post_types(){
    global $compendium_active_posts;
    $post_types = get_post_types();


    foreach ($post_types as $type) {
        $compendium_active_posts[] = array(
            'description'   => $type,
            'db_name'       => 'active-'.$type,
            'init'          => '0'
        );
    }
}




/**--------------------------------------------------------
 *
 *  Enqueue styles and scripts
 *
 *-------------------------------------------------------*/
function compendium_scripts() {
    wp_register_script( 'compendium-js', plugins_url( '/js/scripts.js', __FILE__ ), array( 'jquery' ) );
    wp_register_script( 'compendium-jquery-ui', 'https://code.jquery.com/ui/1.12.1/jquery-ui.min.js',  array( 'jquery' ), null, true );
    wp_register_script( 'compendium-page-js', plugins_url( '/js/page-scripts.js', __FILE__ ), array( 'jquery' ) );
}
add_action( 'wp_enqueue_scripts', 'compendium_scripts', 10 );

function compendium_styles() {
    wp_register_style( 'compendium-css', plugins_url( '/css/styles.css', __FILE__ ) );
    wp_register_style( 'jquery-ui', plugins_url('css/jquery-ui.min.css', __FILE__ ), array(), null );
    wp_register_style( 'jquery-ui-structure', plugins_url('css/jquery-ui.structure.min.css', __FILE__ ), array('jquery-ui'), null );
    wp_register_style( 'jquery-ui-theme', plugins_url('css/jquery-ui.theme.min.css', __FILE__ ), array('jquery-ui'), null );
}
add_action( 'wp_enqueue_scripts', 'compendium_styles' );
function compendium_admin_styles() {
    wp_register_style( 'compendium-admin-css', plugins_url('/css/admin-styles.css', __FILE__ ) );
    wp_enqueue_style( 'compendium-admin-css' );
}
add_action( 'admin_enqueue_scripts', 'compendium_admin_styles' );

/**--------------------------------------------------------
 *
 *  Activation function
 *
 *-------------------------------------------------------*/
function compendium_activate() {
    global $compendium_active_posts, $compendium_save_as;

    $init_options = array();

    foreach($compendium_active_posts as $option) {
        $init_options[$option['db_name']] = $option['init'];
    }

    add_option('compendium-posts-per-page', array('description' => 'Posts per page', 'value' => 22));
    add_option( 'compendium-enable-icons', array('description' => 'Enable Icons', 'value' => 0));
    add_option( 'compendium-title', array('description' => 'Page Title', 'value' => 'Resource Center'));
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
function show_compendium_content($activePosts){
    ob_start();
    echo Compendium_Resources::do_resources($activePosts);

    return ob_get_clean();
}

/**--------------------------------------------------------
 *
 *  Register shortcode to display resource center
 *
 *-------------------------------------------------------*/
function compendium_resource_center() {
    global $compendium_save_as;

    //Enqueue Scripts
    wp_enqueue_script( 'compendium-js' );
    wp_enqueue_script( 'compendium-jquery-ui' );
    wp_enqueue_script( 'compendium-page-js' );

    //Enqueue Styles
    wp_enqueue_style( 'compendium-css' );
    wp_enqueue_style( 'jquery-ui' );
    wp_enqueue_style( 'jquery-ui-structure' );
    wp_enqueue_style( 'jquery-ui-theme' );

    //Get active post types
    $activePosts = array();
    $prefix = 'active-';
    $compendium_post_types = get_option($compendium_save_as);
    foreach ($compendium_post_types as $post_type => $value){
        if ($value === '1') {
            if (substr($post_type, 0, strlen($prefix)) == $prefix) {
                $post_type = substr($post_type, strlen($prefix));
            }
            $activePosts[] = $post_type;
        }
    }


    return show_compendium_content($activePosts);
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
    global $compendium_active_posts, $compendium_save_as;


    $hidden_field_name = 'compendium_submit_hidden';


    // Read in existing option value from database
    $compendium_options = get_option($compendium_save_as);
    $compendium_posts_per_page = get_option('compendium-posts-per-page');
    $compendium_enable_icons = get_option('compendium-enable-icons');
    $compendium_title = get_option('compendium-title');

    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
        // Read the values
        foreach($compendium_active_posts as $option) {
            if(isset($_POST[$option['db_name']])) {
                $compendium_options[$option['db_name']] = $_POST[$option['db_name']];
            }
            else {
                $compendium_options[$option['db_name']] = "0";
            }
        }
        //Get posts per page value
        $compendium_posts_per_page['value'] = $_POST['posts-per-page'];

        //Get icons enabled
        if(isset($_POST['enable-icons'])){
            $compendium_enable_icons['value'] = $_POST['enable-icons'];
        }
        else {
            $compendium_enable_icons['value'] = 0;
        }

        //Get page title
        $compendium_title['value'] = $_POST['compendium-title'];


        //Get and save category selections
        foreach ($_POST as $key => $value) {
            //If post variable name matches the beginning of the radio button names
            if (strpos($key, 'enable-category') === 0){
                update_option('compendium-'.$key, $value);
            }

        }

        // Save the values in the database
        update_option($compendium_save_as, $compendium_options);
        update_option('compendium-posts-per-page', $compendium_posts_per_page);
        update_option( 'compendium-enable-icons', $compendium_enable_icons);
        update_option( 'compendium-title', $compendium_title);

        // Display a "settings saved" message on the screen
        echo '<div class="updated"><p><strong>Settings saved.</strong></p></div>';

    }
    ?>


    <div class="wrap">
        <h2>Compendium Resource Center Settings</h2>
        <form name="resource_options" method="post" action="">
            <div class="metabox">
                <div class="inside">
                    <h3>General Settings</h3>
                    <p>
                        <?=$compendium_posts_per_page['description']?>
                        <input name="posts-per-page" type="number" value="<?=$compendium_posts_per_page['value'] ?>" />
                    </p>

                    <p>
                        <?=$compendium_enable_icons['description']?>
                        <input name="enable-icons" type="checkbox" value="1" <?php if( $compendium_enable_icons['value'] === '1'){ echo ' checked="checked"'; } ?> />
                    </p>

                    <p>
                        <?=$compendium_title['description']?>
                        <input name="compendium-title" type="text" value="<?=$compendium_title['value'] ?>" />
                    </p>
                </div>
            </div>
            <div class="metabox">
                <div class="inside">
                    <h3>Enabled Post Types</h3>
                    <h4>Please select the posts types to be displayed in resource center.</h4>
                    <input type="hidden" name="<?=$hidden_field_name?>" value="Y">

                    <?php
                    foreach($compendium_active_posts as $option) {
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
            <?php
            foreach($compendium_active_posts as $post_type) {
                $taxonomies = get_object_taxonomies($post_type['description']);

                if ($compendium_options['active-'.$post_type['description']] === '1'){
                    ?>
                    <div class="metabox">
                        <div class="inside">
                            <h3><?=$post_type['description']?> - Category Taxonomy</h3>
                            <h4>Please select the taxonomy below that contains the categories for this post type.</h4>
                            <p>
                            <?php
                            foreach($taxonomies as $tax) {
                                ?>
                                <input name="enable-category-<?=$post_type['description']?>" type="radio" value="<?=$tax?>" <?php if (get_option('compendium-enable-category-'.$post_type['description']) == $tax ){ echo 'checked';}?> /> <?=$tax?><br>
                                <?php
                            }
                            ?>
                            </p>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>

            <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
            </p>

            <p>Add the shortcode [compendium] to the page you wish it to be displayed on.</p>

        </form>
    </div>
    <?php
}
