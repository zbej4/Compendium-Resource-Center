<?php
/**
 * Plugin Name: Compendium Resource Center
 * Description: Provides the functionality to create and maintain a resource center that integrates several post types and categories.
 * Version:	 0.7
 * Author: Brandon Jones
 * Text Domain: compendium-resource-center
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
require_once dirname( __FILE__ ) .'/compendium-resources.php';
require_once dirname( __FILE__ ) .'/includes/register-types.php';

/**--------------------------------------------------------
 *
 *  The default settings for the plugin.
 *
 *-------------------------------------------------------*/
global $compendium_save_as; // the `option_name` field in the `wp_options` table
global $compendium_active_posts;

$compendium_save_as = 'compendiumresourcecenter';

$post_types = get_post_types(['public' => true]);

//This is needed to initialize the array.  When the admin launches it will run again after the plugins have loaded to ensure all post types are available.
foreach ($post_types as $type) {
    $compendium_active_posts[] = array(
        'description'   => $type,
        'db_name'       => 'active-'.$type,
        'init'          => '0'
    );
}

//Get Post types after load
add_action( 'wp_loaded', 'compendium_get_post_types');
function compendium_get_post_types(){
    global $compendium_active_posts;
    $post_types = get_post_types(['public' => true]);
    //reset active posts to prevent duplicates of those that have already initialized
    $compendium_active_posts = array();

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
 *  Require other plugins
 *
 *-------------------------------------------------------*/
//Include the TGM_Plugin_Activation class.
require_once dirname( __FILE__ ) . '/class-tgm-plugin-activation.php';

add_action( 'tgmpa_register', 'compendium_resource_center_register_required_plugins');
function compendium_resource_center_register_required_plugins(){
    /**
     * Array of plugin arrays. Required keys are name and slug.
     * If the source is NOT from the .org repo, then source is also required.
     */
    $plugins = array(
        array(
            'name' => 'Share Buttons by AddThis',
            'slug' => 'addthis',
            'required' => false,
            'is_callable' => 'addthis_script_to_content',
        ),
        array(
            'name' => 'Advanced Custom Fields',
            'slug' => 'advanced-custom-fields',
            'required' => true,
            'is_callable' => 'acf',
        )
    );
    /**
     * Array of configuration settings. Amend each line as needed.
     */
    $config = array(
        'id'           => 'tgmpa',                 // Unique ID for hashing notices for multiple instances of TGMPA.
        'default_path' => '',                      // Default absolute path to bundled plugins.
        'menu'         => 'tgmpa-install-plugins', // Menu slug.
        'parent_slug'  => 'plugins.php',            // Parent menu slug.
        'capability'   => 'edit_theme_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
        'has_notices'  => true,                    // Show admin notices or not.
        'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
        'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
        'is_automatic' => false,                   // Automatically activate plugins after installation or not.
        'message'      => '',                      // Message to output right before the plugins table.
    );
    tgmpa( $plugins, $config );
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
    wp_register_style( 'compendium-font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
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

    //Initialize active post types
    foreach($compendium_active_posts as $option) {
        $init_options[$option['db_name']] = $option['init'];
    }


    add_option( 'compendium-posts-per-page', array('description' => 'Posts per page', 'value' => 22));
    add_option( 'compendium-enable-icons', array('description' => 'Enable Icons', 'value' => 0));
    add_option( 'compendium-enable-featured-posts', array('description' => 'Enable Featured posts', 'value' => 0));
    add_option( 'compendium-featured-per-page', array('description' => 'Featured posts per page', 'value' => 12));
    add_option( 'compendium-title', array('description' => 'Page Title', 'value' => 'Resource Center'));
    add_option( 'compendium-registered-posts', array());
    add_option($compendium_save_as, $init_options);
}
register_activation_hook( __FILE__, 'compendium_activate' );

/**--------------------------------------------------------
 *
 *  Register any selected types
 *
 *-------------------------------------------------------*/
function compendium_register_enabled_types(){
    $compendium_registered_posts = get_option('compendium-registered-posts');
    foreach($compendium_registered_posts as $key => $value){
        if ($value === '1'){
            $type = Compendium_Resources::get_meta_info($key);
            compendium_register_type($key,$type['name'],$type['plural'],$type['dashicon']);
        }
    }
}
add_action('init', 'compendium_register_enabled_types');

/**--------------------------------------------------------
 *
 *  Add a "Settings" link in the Plugin list entry
 *
 *-------------------------------------------------------*/
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
add_filter('plugin_action_links', 'compendium_plugin_action_links', 10, 2);

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
    wp_enqueue_style( 'compendium-font-awesome' );
    wp_enqueue_style( 'jquery-ui' );
    wp_enqueue_style( 'jquery-ui-structure' );
    wp_enqueue_style( 'jquery-ui-theme' );
    wp_enqueue_style( 'compendium-css' );

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
 *  Register External URL fields
 *
 *-------------------------------------------------------*/
function compendium_register_meta_fields() {
    global $compendium_save_as;
    //Get active post types as array
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
    //Convert to array for locations
    $location_array = array();
    foreach ($activePosts as $post_type) {
        $location_array[] = array (
            array(
                'param' => 'post_type',
                'operator' => '==',
                'value' => $post_type,
            ),
        );
    }

    //Register field with ACF
    if( function_exists('register_field_group') ) {

        register_field_group(
            array(
                'key' => 'group_54ee2ae218097',
                'title' => 'Compendium Resource Center: ',
                'fields' => array(
                    array(
                        'key' => 'field_58003aed89824',
                        'label' => 'External Url',
                        'name' => 'document_external_url',
                        'prefix' => '',
                        'type' => 'text',
                        'instructions' => 'Please enter the external URL to route end-user to, such as https://www.wordpress.org',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'return_format' => 'array',
                        'library' => 'all',
                        'min_size' => 0,
                        'max_size' => 0,
                        'mime_types' => '',
                    ),
                    array (
                        'key' => 'field_59495a80c7297',
                        'label' => 'Featured Post',
                        'name' => 'compendium_featured_post',
                        'type' => 'true_false',
                        'instructions' => 'Make this a featured post in the resource center.',
                        'message' => '',
                        'default_value' => 0,
                    ),
                ),
                'location' => $location_array,
                'options' => array (
                    'position' => 'normal',
                    'layout' => 'default',
                    'hide_on_screen' => array (
                    ),
                ),
                'menu_order' => 0,
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
            )
        );

    }


}
add_action('wp_loaded', 'compendium_register_meta_fields');


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
    $compendium_enable_featured = get_option('compendium-enable-featured-posts');
    $compendium_featured_per_page = get_option('compendium-featured-per-page');
    $compendium_enable_icons = get_option('compendium-enable-icons');
    $compendium_title = get_option('compendium-title');
    $compendium_registered_posts = get_option('compendium-registered-posts');

    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
        // Get enabled post type values
        foreach($compendium_active_posts as $option) {
            if(isset($_POST[$option['db_name']])) {
                $compendium_options[$option['db_name']] = $_POST[$option['db_name']];
            }
            else {
                $compendium_options[$option['db_name']] = "0";
            }
        }
        //Get posts per page value
        if(isset($_POST['posts-per-page']) && $_POST['posts-per-page']>0 ){
            $compendium_posts_per_page['value'] = $_POST['posts-per-page'];
        }
        else {
            add_settings_error( 'options-general.php?page=compendium-resource-center', '110', 'Posts per page must be greater than 0.');
        }

        //Get icons enabled
        if(isset($_POST['enable-icons'])){
            $compendium_enable_icons['value'] = $_POST['enable-icons'];
        }
        else {
            $compendium_enable_icons['value'] = 0;
        }

        //Get featured posts enabled
        if(isset($_POST['enable-featured'])){
            $compendium_enable_featured['value'] = $_POST['enable-featured'];
        }
        else {
            $compendium_enable_featured['value'] = 0;
        }

        //Get featured posts per page
        if(isset($_POST['featured-per-page']) && $_POST['featured-per-page']>0 && $_POST['featured-per-page']<$_POST['posts-per-page'] ){
            $compendium_featured_per_page['value'] = $_POST['featured-per-page'];
        }
        else {
            add_settings_error( 'options-general.php?page=compendium-resource-center', '120', 'Featured posts per page must be greater than 0 and less than the "Posts per page" field.');
        }

        //Get page title
        $compendium_title['value'] = $_POST['compendium-title'];

        //Empty registered posts and reset only those active
        $compendium_registered_posts = array();

        foreach ($_POST as $key => $value) {
            //Get and save category selections
            //If post variable name matches the beginning of the radio button names
            if (strpos($key, 'enable-category') === 0){
                update_option('compendium-'.$key, $value);
            }

            //Get and save types to register
            //If post variable name matches the beginning of the radio button names
            if (strpos($key, 'register-type-') === 0){
                $type_name = substr($key, strlen('register-type-'));
                if(isset($_POST[$key])) {
                    $compendium_registered_posts[$type_name] = $value;
                }
                else {
                    $compendium_registered_posts[$type_name] = 0;
                }
            }
        }

        // Save the values in the database
        update_option($compendium_save_as, $compendium_options);
        update_option('compendium-posts-per-page', $compendium_posts_per_page);
        update_option( 'compendium-enable-icons', $compendium_enable_icons);
        update_option( 'compendium-enable-featured-posts', $compendium_enable_featured);
        update_option( 'compendium-featured-per-page', $compendium_featured_per_page);
        update_option( 'compendium-title', $compendium_title);
        update_option( 'compendium-registered-posts', $compendium_registered_posts);

        // Display a "settings saved" message on the screen
        echo '<div class="updated"><p><strong>Settings saved.</strong></p></div>';

    }
    ?>


    <div class="wrap">
        <h2>Compendium Resource Center Settings</h2>
        <?php settings_errors() ?>
        <p>Add the shortcode [compendium] to the page you wish it to be displayed on.</p>
        <form name="resource_options" method="post" action="">
            <input type="hidden" name="<?=$hidden_field_name?>" value="Y">
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
                    <hr/>
                    <h4>Featured posts will only appear on the first page and will be ordered by most recent post.</h4>
                    <p>
                        <?=$compendium_enable_featured['description']?>
                        <input name="enable-featured" type="checkbox" value="1" <?php if( $compendium_enable_featured['value'] === '1'){ echo ' checked="checked"'; } ?> />
                    </p>
                    <p>
                        <?=$compendium_featured_per_page['description']?>
                        <input name="featured-per-page" type="number" value="<?=$compendium_featured_per_page['value'] ?>" />
                    </p>
                </div>
            </div>
            <div class="metabox">
                <div class="inside">
                    <h3>Enabled Post Types</h3>
                    <h4>Please select the posts types to be displayed in resource center.</h4>

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

            <div class="metabox">
                <div class="inside">
                    <h3>Register Post Types (Optional)</h3>
                    <h4>Enable any of the following to register the corresponding post type (uses first slug in list).  If you choose to create your own, any of the listed slugs will still return the appropriate icon.</h4>
                    <table class="svg-list">
                        <tr>
                            <th>Post Type</th>
                            <th>slugs</th>
                            <th>Icon</th>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-analyst_report" type="checkbox" value="1" <?php if($compendium_registered_posts['analyst_report'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('analyst_report')['name']; ?>
                            </td>
                            <td>analyst_report</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('analyst_report')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-asset" type="checkbox" value="1" <?php if($compendium_registered_posts['asset'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('asset')['name']; ?>
                            </td>
                            <td>asset</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('asset')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-audit_report" type="checkbox" value="1" <?php if($compendium_registered_posts['audit_report'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('audit_report')['name']; ?>
                            </td>
                            <td>audit_report</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('audit_report')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-award" type="checkbox" value="1" <?php if($compendium_registered_posts['award'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('award')['name']; ?>
                            </td>
                            <td>award</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('award')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-bar" type="checkbox" value="1" <?php if($compendium_registered_posts['bar'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('bar')['name']; ?>
                            </td>
                            <td>bar</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('bar')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-benchmark_report" type="checkbox" value="1" <?php if($compendium_registered_posts['benchmark_report'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('benchmark_report')['name']; ?>
                            </td>
                            <td>benchmark_report</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('benchmark_report')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-brochure" type="checkbox" value="1" <?php if($compendium_registered_posts['brochure'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('brochure')['name']; ?>
                            </td>
                            <td>brochure</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('brochure')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-business_intelligence" type="checkbox" value="1" <?php if($compendium_registered_posts['business_intelligence'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('business_intelligence')['name']; ?>
                            </td>
                            <td>business_intelligence</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('business_intelligence')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-calendar" type="checkbox" value="1" <?php if($compendium_registered_posts['calendar'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('calendar')['name']; ?>
                            </td>
                            <td>calendar</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('calendar')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-call_accounting" type="checkbox" value="1" <?php if($compendium_registered_posts['call_accounting'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('call_accounting')['name']; ?>
                            </td>
                            <td>call_accounting</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('call_accounting')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-case_study" type="checkbox" value="1" <?php if($compendium_registered_posts['case_study'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('case_study')['name']; ?>
                            </td>
                            <td>case_study, case-study</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('case_study')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-cost_allocation" type="checkbox" value="1" <?php if($compendium_registered_posts['cost_allocation'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('cost_allocation')['name']; ?>
                            </td>
                            <td>cost_allocation</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('cost_allocation')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-expense_management" type="checkbox" value="1" <?php if($compendium_registered_posts['expense_management'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('expense_management')['name']; ?>
                            </td>
                            <td>expense_management</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('expense_management')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-fact_sheet" type="checkbox" value="1" <?php if($compendium_registered_posts['fact_sheet'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('fact_sheet')['name']; ?>
                            </td>
                            <td>fact_sheet</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('fact_sheet')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-infographic" type="checkbox" value="1" <?php if($compendium_registered_posts['infographic'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('infographic')['name']; ?>
                            </td>
                            <td>infographic</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('infographic')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-insight_analytics" type="checkbox" value="1" <?php if($compendium_registered_posts['insight_analytics'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('insight_analytics')['name']; ?>
                            </td>
                            <td>insight_analytics</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('insight_analytics')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-item" type="checkbox" value="1" <?php if($compendium_registered_posts['item'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('item')['name']; ?>
                            </td>
                            <td>item</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('item')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-managed" type="checkbox" value="1" <?php if($compendium_registered_posts['managed'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('managed')['name']; ?>
                            </td>
                            <td>managed</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('managed')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-mms" type="checkbox" value="1" <?php if($compendium_registered_posts['mms'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('mms')['name']; ?>
                            </td>
                            <td>mms</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('mms')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-on-prem" type="checkbox" value="1" <?php if($compendium_registered_posts['on-prem'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('on-prem')['name']; ?>
                            </td>
                            <td>on-prem</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('on-prem')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-play" type="checkbox" value="1" <?php if($compendium_registered_posts['play'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('play')['name']; ?>
                            </td>
                            <td>play</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('play')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-rss" type="checkbox" value="1" <?php if($compendium_registered_posts['rss'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('rss')['name']; ?>
                            </td>
                            <td>rss, post</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('rss')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-press_release" type="checkbox" value="1" <?php if($compendium_registered_posts['press_release'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('press_release')['name']; ?>
                            </td>
                            <td>press_release</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('press_release')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-research" type="checkbox" value="1" <?php if($compendium_registered_posts['research'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('research')['name']; ?>
                            </td>
                            <td>research</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('research')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-restaurant" type="checkbox" value="1" <?php if($compendium_registered_posts['restaurant'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('restaurant')['name']; ?>
                            </td>
                            <td>restaurant</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('restaurant')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-saas" type="checkbox" value="1" <?php if($compendium_registered_posts['saas'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('saas')['name']; ?>
                            </td>
                            <td>saas</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('saas')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-service_support" type="checkbox" value="1" <?php if($compendium_registered_posts['service_support'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('service_support')['name']; ?>
                            </td>
                            <td>service_support</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('service_support')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-share_service" type="checkbox" value="1" <?php if($compendium_registered_posts['share_service'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('share_service')['name']; ?>
                            </td>
                            <td>share_service</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('share_service')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-staff" type="checkbox" value="1" <?php if($compendium_registered_posts['staff'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('staff')['name']; ?>
                            </td>
                            <td>staff</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('staff')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-tem" type="checkbox" value="1" <?php if($compendium_registered_posts['tem'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('tem')['name']; ?>
                            </td>
                            <td>tem</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('tem')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-usage" type="checkbox" value="1" <?php if($compendium_registered_posts['usage'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('usage')['name']; ?>
                            </td>
                            <td>usage</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('usage')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-video" type="checkbox" value="1" <?php if($compendium_registered_posts['video'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('video')['name']; ?>
                            </td>
                            <td>video</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('video')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-webinar" type="checkbox" value="1" <?php if($compendium_registered_posts['webinar'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('webinar')['name']; ?>
                            </td>
                            <td>webinar</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('webinar')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-wem" type="checkbox" value="1" <?php if($compendium_registered_posts['wem'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('wem')['name']; ?>
                            </td>
                            <td>wem</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('wem')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-whitepaper" type="checkbox" value="1" <?php if($compendium_registered_posts['whitepaper'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('whitepaper')['name']; ?>
                            </td>
                            <td>whitepaper, white-paper</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('whitepaper')['icon']; ?></div></td>
                        </tr>
                        <tr>
                            <td>
                                <input name="register-type-youtube" type="checkbox" value="1" <?php if($compendium_registered_posts['youtube'] === '1') { echo 'checked="checked"'; }?>/>
                                <? echo Compendium_Resources::get_meta_info('youtube')['name']; ?>
                            </td>
                            <td>youtube</td>
                            <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('youtube')['icon']; ?></div></td>
                        </tr>
                    </table>
                </div>
            </div>

            <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
            </p>

        </form>
    </div>
    <?php
}
