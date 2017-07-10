<?php
/**
 * Plugin Name: Compendium Resource Center
 * Description: Provides the functionality to create and maintain a resource center that integrates several post types and categories.
 * Version:	 0.8
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
    wp_register_script( 'compendium-js', plugins_url( '/js/scripts.js', __FILE__ ), array( 'jquery' ) );
    wp_enqueue_style( 'compendium-admin-css' );
    wp_enqueue_script( 'compendium-js' );
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
    add_option( 'compendium-registered-posts-custom', array());
    add_option($compendium_save_as, $init_options);
}
register_activation_hook( __FILE__, 'compendium_activate' );

/**--------------------------------------------------------
 *
 *  Register any selected types
 *
 *-------------------------------------------------------*/
function compendium_register_enabled_types(){
    $compendium_registered_posts_custom = get_option('compendium-registered-posts-custom');

    //Loop for custom post types
    foreach($compendium_registered_posts_custom as $key => $value){
        $icon = Compendium_Resources::get_meta_info($value['slug'])['dashicons'];
        compendium_register_type($value['slug'],$value['name'],$value['plural'],$icon);
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
    sort($activePosts);

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
                        'name' => 'compendium_external_url',
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
 *  WP Admin Main Settings page
 *
 *-------------------------------------------------------*/
add_action( 'admin_menu', 'admin_resource' );
function admin_resource() {
    add_menu_page( 'Compendium Resource Center Settings', 'Compendium Resource Center', 'manage_options', 'compendium-resource-center', 'compendium_resource_options', 'dashicons-welcome-learn-more', 30 );
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

    if ( isset($_POST['submit'])){
        // Check hidden field is set to verify submitted by user
        if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
            // Get enabled post type values
            $compendium_options = array();
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
            update_option( 'compendium-enable-featured-posts', $compendium_enable_featured);
            update_option( 'compendium-featured-per-page', $compendium_featured_per_page);
            update_option( 'compendium-title', $compendium_title);

            // Display a "settings saved" message on the screen
            echo '<div class="updated"><p><strong>Settings saved.</strong></p></div>';

        }
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

            <p class="submit">
                <input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
            </p>

        </form>
    </div>
    <?php
}

/**--------------------------------------------------------
 *
 *  WP Admin Manage Post Types page
 *
 *-------------------------------------------------------*/
add_action( 'admin_menu', 'admin_resource_posts' );
function admin_resource_posts() {
    add_submenu_page( 'compendium-resource-center', 'Compendium Resource Center Custom Post Types', 'Manage Post Types', 'manage_options', 'compendium-resource-center-manage-types', 'compendium_resource_post_options');
}

function compendium_resource_post_options(){

    if ( !current_user_can( 'manage_options' ) ) {
    wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    $hidden_field_name = 'compendium_submit_hidden';

    // Read in existing option value from database
    $compendium_registered_posts_custom = get_option('compendium-registered-posts-custom');

    if ( isset($_POST['submit'])){
        // Check hidden field is set to verify submitted by user
        if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ){
            //Reset array
            $compendium_registered_posts_custom = array();

            //Add old elements if still available
            if ( isset($_POST['register-type']) ){
                foreach ($_POST['register-type'] as $key => $value) {
                    $compendium_registered_posts_custom[] = $value;
                }
            }
            //Add custom fields to array if not empty
            if ($_POST['register-custom-name'] != '' && $_POST['register-custom-plural'] != '' && $_POST['register-custom-slug'] != '') {
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9-_\.]/','', $_POST['register-custom-slug']));

                $compendium_registered_posts_custom[] = array(
                    'name' => $_POST['register-custom-name'],
                    'plural' => $_POST['register-custom-plural'],
                    'slug' => $slug,
                );
            }
            elseif ( ($_POST['register-custom-name'] === '' || $_POST['register-custom-plural'] === '' || $_POST['register-custom-slug'] === '') && ($_POST['register-custom-name'] != '' || $_POST['register-custom-plural'] != '' || $_POST['register-custom-slug'] != '') ) {
                add_settings_error( 'options-general.php?page=compendium-resource-center', '130', 'You must fill out all three post type fields.');
            }

            update_option( 'compendium-registered-posts-custom', $compendium_registered_posts_custom);

            // Display a "settings saved" message on the screen
            echo '<div class="updated"><p><strong>Settings saved.</strong></p></div>';
            echo "<meta http-equiv='refresh' content='0'>";
        }
    }


    ?>


    <div class="wrap">
        <h2>Compendium Resource Center - Manage Post Types</h2>
        <?php settings_errors() ?>
        <p>Here you can add custom post types so that they will be registered with Wordpress.</p>
        <ul>
            <li>Please note that this does NOT enable them for the resource center by default, and that you must select to enable them in the settings menu.</li>
            <li>The post type will be created along with a category taxonomy that will be titled {slug}-category.</li>
            <li>If the slug matches one of the below supported types then the corresponding icon will display in the resource center.</li>
        </ul>
        <form name="custom-types" method="post" action="">
            <input type="hidden" name="<?=$hidden_field_name?>" value="Y">
            <div class="metabox">
                <div class="inside">
                    <h3>Register New Custom Post Types</h3>
                    <table class="custom-types">
                        <tr>
                            <th></th>
                            <th>Name</th>
                            <th>Plural name</th>
                            <th>Slug</th>
                        </tr>
                        <?php
                        foreach( $compendium_registered_posts_custom as $key => $value) {
                            $name = 'register-type['.$key.'][name]' ;
                            $plural = 'register-type['.$key.'][plural]';
                            $slug = 'register-type['.$key.'][slug]';
                            ?>
                            <tr data-custom-type="<?=$key?>">
                                <td><span class="custom-type-delete" data-custom-delete="<?=$key?>">Delete</span></td>
                                <td><input name="<?=$name?>" type="text" value="<?=$value['name']?>" readonly/></td>
                                <td><input name="<?=$plural?>" type="text" value ="<?=$value['plural']?>" readonly/></td>
                                <td><input name="<?=$slug?>" type="text" value="<?=$value['slug']?>" readonly/></td>
                            </tr>
                            <?php
                        }
                        ?>
                        <tr class="custom-new">
                            <td>Add New:</td>
                            <td><input name="register-custom-name" type="text" placeholder="Example" /></td>
                            <td><input name="register-custom-plural" type="text" placeholder="Examples" /></td>
                            <td><input name="register-custom-slug" type="text" placeholder="example" /></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e('Save Types') ?>" />
                    </p>
                </div>
            </div>

        </form>

        <div class="metabox">
            <div class="inside">
                <h3>Icons to appear in resource center (Optional)</h3>
                <table class="svg-list">
                    <tr>
                        <th>slugs</th>
                        <th>Icon</th>
                    </tr>
                    <tr>
                        <td>Default - slug matches none of the following slugs</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('default')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>analyst_report</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('analyst_report')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>asset</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('asset')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>audit_report</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('audit_report')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>award</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('award')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>bar</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('bar')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>benchmark_report</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('benchmark_report')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>brochure</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('brochure')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>business_intelligence</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('business_intelligence')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>calendar</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('calendar')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>call_accounting</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('call_accounting')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>case_study, case-study</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('case_study')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>cost_allocation</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('cost_allocation')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>expense_management</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('expense_management')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>fact_sheet, checklist</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('fact_sheet')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>infographic</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('infographic')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>insight_analytics</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('insight_analytics')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>item</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('item')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>mms</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('mms')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>on-prem</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('on-prem')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>play</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('play')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>rss, post</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('rss')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>press_release</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('press_release')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>research</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('research')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>restaurant</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('restaurant')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>saas</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('saas')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>service_support</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('service_support')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>share_service</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('share_service')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>staff</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('staff')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>tem</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('tem')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>usage</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('usage')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>video</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('video')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>webinar</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('webinar')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>wem</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('wem')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>whitepaper, white-paper</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('whitepaper')['icon']; ?></div></td>
                    </tr>
                    <tr>
                        <td>youtube</td>
                        <td><div class="resource__icon"><? echo Compendium_Resources::get_meta_info('youtube')['icon']; ?></div></td>
                    </tr>
                </table>
            </div>
        </div>

    </div>
    <?php
}