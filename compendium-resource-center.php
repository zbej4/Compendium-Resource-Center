<?php
/**
 * Plugin Name: Compendium Resource Center
 * Description: Provides the functionality to create and maintain a resource center that integrates several post types and categories.
 * Version:	 0.4.2
 * Author: Brandon Jones
 * Text Domain: compendium-resource-center
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
global $compendium_active_external;
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
foreach($post_types as $type) {
    $compendium_active_external[] = array(
        'description'   => $type,
        'db_name'       => 'external-'.$type,
        'init'          => '0'
    );
}

//Get Post types after load
add_action( 'wp_loaded', 'compendium_get_post_types');
function compendium_get_post_types(){
    global $compendium_active_posts, $compendium_active_external;
    $post_types = get_post_types(['public' => true]);
    //reset active posts to prevent duplicates of those that have already initialized
    $compendium_active_posts = array();
    $compendium_active_external = array();

    foreach ($post_types as $type) {
        $compendium_active_posts[] = array(
            'description'   => $type,
            'db_name'       => 'active-'.$type,
            'init'          => '0'
        );
    }

    foreach ($post_types as $type) {
        $compendium_active_external[] = array(
            'description'   => $type,
            'db_name'       => 'external-'.$type,
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
    global $compendium_active_posts, $compendium_save_as, $compendium_active_external;

    $init_options = array();
    $init_external = array();

    //Initialize active post types
    foreach($compendium_active_posts as $option) {
        $init_options[$option['db_name']] = $option['init'];
    }
    //Initialize external url fields
    foreach($compendium_active_external as $external) {
        $init_external[$external['db_name']] = $external['init'];
    }

    add_option('compendium-posts-per-page', array('description' => 'Posts per page', 'value' => 22));
    add_option( 'compendium-enable-icons', array('description' => 'Enable Icons', 'value' => 0));
    add_option( 'compendium-title', array('description' => 'Page Title', 'value' => 'Resource Center'));
    add_option( 'compendium-external-url', $init_external);
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
function compendium_register_external_url() {
    //Get selected post types for external field
    $compendium_external_url = get_option('compendium-external-url');
    //Convert to array of post types
    $activeExternal = array();
    $prefix = 'external-';
    foreach ($compendium_external_url as $post_type => $value){
        if ($value === '1') {
            if (substr($post_type, 0, strlen($prefix)) == $prefix) {
                $post_type = substr($post_type, strlen($prefix));
            }
            $activeExternal[] = $post_type;
        }
    }

    $location_array = array();
    foreach ($activeExternal as $post_type) {
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
add_action('wp_loaded', 'compendium_register_external_url');


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
    global $compendium_active_posts, $compendium_save_as, $compendium_active_external;


    $hidden_field_name = 'compendium_submit_hidden';


    // Read in existing option value from database
    $compendium_options = get_option($compendium_save_as);
    $compendium_external_url = get_option('compendium-external-url');
    $compendium_posts_per_page = get_option('compendium-posts-per-page');
    $compendium_enable_icons = get_option('compendium-enable-icons');
    $compendium_title = get_option('compendium-title');

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
        //Get external url post types
        foreach($compendium_active_external as $external) {
            if(isset($_POST[$external['db_name']])) {
                $compendium_external_url[$external['db_name']] = $_POST[$external['db_name']];
            }
            else {
                $compendium_external_url[$external['db_name']] = "0";
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
        update_option( 'compendium-external-url', $compendium_external_url);
        update_option('compendium-posts-per-page', $compendium_posts_per_page);
        update_option( 'compendium-enable-icons', $compendium_enable_icons);
        update_option( 'compendium-title', $compendium_title);

        // Display a "settings saved" message on the screen
        echo '<div class="updated"><p><strong>Settings saved.</strong></p></div>';

    }
    ?>


    <div class="wrap">
        <h2>Compendium Resource Center Settings</h2>
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
                    <h3>External URL Field</h3>
                    <h4>Please select the posts types to add an external URL field to the post meta.</h4>

                    <?php
                    foreach($compendium_active_external as $external) {
                        ?>
                        <p>
                            <input name="<?=$external['db_name']?>" type="checkbox" value="1" <?php if ($compendium_external_url[$external['db_name']] === '1') { echo ' checked="checked"'; } ?> />
                            &nbsp; <?=$external['description']?>
                        </p>

                        <?php
                    }

                    ?>
                </div>
            </div>

            <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
            </p>

            <div class="metabox">
                <div class="inside">
                    <h3>Icons currently available</h3>
                    <table class="svg-list">
                        <tr>
                            <th>Post-type slug(s)</th>
                            <th>Icon</th>
                        </tr>
                        <tr>
                            <td>analyst_report</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;}</style><circle class="st0 line" cx="87" cy="59.8" r="7.8"/><circle class="st0 line" cx="87" cy="87.2" r="7.8"/><circle class="st0 line" cx="55.8" cy="75.4" r="7.8"/><path class="st0 line" d="M62.7 71.9l17.4-8.6M63.1 78.2l16.6 6.2"/><path class="st1 line" d="M46 87.2H4.9V5H48l19.5 19.6v33.2"/><path class="st0 line" d="M48 5v19.6h19.5M16.6 32.4v39.1h23.5M16.6 55.9l10.8-10.8 7.8 7.8 12.7-12.7"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>assets</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-miterlimit:10;}</style><path class="st0" d="M26.7 17.7h46.6v64.1H26.7z"/><path class="st0 line" d="M26.7 81.8v6c0 5.5 3.7 9.9 8.3 9.9h30c4.6 0 8.3-4.5 8.3-9.9v-6H26.7zM50 93.1c-1.8 0-3.3-1.5-3.3-3.3s1.5-3.3 3.3-3.3 3.3 1.5 3.3 3.3-1.5 3.3-3.3 3.3zM73.3 17.7v-5.5c0-5.5-3.7-9.9-8.3-9.9H35c-4.6 0-8.3 4.5-8.3 9.9v5.5h46.6zM41.7 8.5h16.7c.9 0 1.7.9 1.7 2s-.7 2-1.7 2H41.7c-.9 0-1.7-.9-1.7-2s.7-2 1.7-2z"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>audit_report</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M85.6 70.7c0 8.2-6.7 14.9-14.9 14.9s-14.9-6.7-14.9-14.9 6.7-14.9 14.9-14.9c8.2.1 14.9 6.7 14.9 14.9z"/><path class="st1 line" d="M81.2 81.3L94.9 95"/><path class="st0 line" d="M51.8 87.2H5L4.9 5H48l19.5 19.6v21.5"/><path class="st1 line" d="M48 5v19.6h19.5M16.7 32.4v39.1h23.5M16.7 55.9l10.7-10.8 7.9 7.8L48 40.2"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>benchmark_report</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M94.9 71.5c0 13-10.5 23.5-23.5 23.5C58.5 95 48 84.5 48 71.5S58.5 48 71.4 48c13 0 23.5 10.5 23.5 23.5z"/><path class="st1 line" d="M84.2 65.1L69.3 81.5 58.6 70.8"/><path class="st0 line" d="M46 87.2H4.9V5H48l19.5 19.6v17.6"/><path class="st1 line" d="M48 5v19.6h19.5M16.7 32.4v39.1h23.4M16.7 55.9l10.7-10.8 7.8 7.8L48 40.2"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>brochure</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M34.9 95l-30-21.4V5l30 21.4L64.9 5l30 21.4V95l-30-21.4zM34.9 26.4V95M64.9 73.6V5.1"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>business_intelligence</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M51.4 45.8h8.2v16.4h-8.2zM67.7 25.3h8.2v36.8h-8.2zM24 48.6c0 3.8 3.1 6.9 6.9 6.9s6.9-3.1 6.9-6.9c0-3.8-3.1-6.9-6.9-6.9S24 38.6 24 34.8c0-3.8 3.1-6.9 6.9-6.9s6.9 3.1 6.9 6.9M30.9 55.5v4.6M30.9 23.3v36.8"/><path class="st0 line" d="M82.7 19.2v49.1H17.2V19.2zM9 76.5V17.1c0-3.4 2.7-6.1 6.1-6.1h69.6c3.4 0 6.1 2.7 6.1 6.1v59.3"/><path class="st0 line" d="M58.2 76.5v4.1H41.8v-4.1H4.9v8.2c0 2.3 1.8 4.1 4.1 4.1h81.9c2.3 0 4.1-1.8 4.1-4.1v-8.2H58.2z"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>calendar</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-miterlimit:10;}</style><path class="st0 line" d="M92 5H8c-3.2 0-5.9 2.6-5.9 5.9v78.2C2.1 92.3 4.7 95 8 95h84c3.2 0 5.9-2.6 5.9-5.9V10.9C97.9 7.7 95.3 5 92 5zM2.1 24.1h95.8M27.5 46.6s9.2-10.1 16.1-1.3c.7.8 4.6 9.1-5.6 17.8s-10.6 12-10.6 12h21.3M57.3 50l8.8-8.3h1v35"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>call_accounting</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M32.3 42.2c3-3.1 3-8 0-11.1L24 22.9c-3-3.1-8-3.1-11.1 0l-4.5 4.6c-3.9 3.9-4.6 10.1-1.5 14.8 13.2 20 30.9 37.6 50.9 50.9 4.6 3.1 10.8 2.5 14.8-1.5l4.6-4.5c3.1-3.1 3.1-8 0-11.1l-8.3-8.3c-3.1-3.1-8-3.1-11.1 0L55 70.6C45.8 62.9 37 54.1 29.5 45l2.8-2.8z"/><path class="st1 line" d="M88 45.1c-9.2 9.2-24 9.2-33.2 0-9.2-9.2-9.2-24 0-33.2 9.2-9.2 24-9.2 33.2 0 9.2 9.1 9.2 24 0 33.2z"/><path class="st0 line" d="M65.1 34.9c0 3.5 2.9 6.4 6.4 6.4 3.5 0 6.4-2.9 6.4-6.4 0-3.5-2.9-6.4-6.4-6.4-3.5 0-6.4-2.9-6.4-6.4 0-3.5 2.9-6.4 6.4-6.4 3.5 0 6.4 2.9 6.4 6.4M71.4 11.8v33.3"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>case_study, case-study</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;} .st2{fill:#000;}</style><path class="st0 line" d="M16.7 28.5h17.6M16.7 40.2H48M16.7 51.9h23.4M16.7 63.7h19.5"/><path class="st1 line" d="M94.9 71.5c0 13-10.5 23.5-23.5 23.5C58.5 95 48 84.5 48 71.5S58.5 48 71.4 48c13 0 23.5 10.5 23.5 23.5zM94.9 71.5c0 13-10.5 23.5-23.5 23.5C58.5 95 48 84.5 48 71.5S58.5 48 71.4 48c13 0 23.5 10.5 23.5 23.5zM46 87.2H4.9V5H48l19.5 19.6v17.6"/><path class="st0 line" d="M48 5v19.6h19.5"/><path class="st2 text" d="M64.2 62.1c-2.5 0-4.5.8-6 2.5s-2.2 4-2.2 6.9c0 3 .7 5.3 2.1 7s3.4 2.5 6 2.5c1.6 0 3.4-.3 5.5-.9v2.3c-1.6.6-3.6.9-5.9.9-3.4 0-6-1-7.8-3.1s-2.8-5-2.8-8.7c0-2.4.4-4.4 1.3-6.2.9-1.8 2.2-3.1 3.8-4.1 1.7-1 3.6-1.4 5.9-1.4 2.4 0 4.5.4 6.3 1.3l-1.1 2.3c-1.6-.9-3.3-1.3-5.1-1.3zM87.6 76.9c0 2-.7 3.6-2.2 4.7-1.5 1.1-3.5 1.7-6 1.7-2.7 0-4.8-.4-6.3-1.1v-2.6c.9.4 2 .7 3.1.9 1.1.2 2.2.3 3.3.3 1.8 0 3.1-.3 4-1 .9-.7 1.3-1.6 1.3-2.8 0-.8-.2-1.4-.5-2s-.9-1-1.6-1.4c-.7-.4-1.9-.9-3.4-1.5-2.1-.8-3.7-1.7-4.6-2.7-.9-1-1.4-2.4-1.4-4.1 0-1.8.7-3.2 2-4.2s3.1-1.6 5.3-1.6c2.3 0 4.4.4 6.3 1.3l-.8 2.3c-1.9-.8-3.7-1.2-5.5-1.2-1.4 0-2.5.3-3.3.9s-1.2 1.4-1.2 2.5c0 .8.1 1.4.4 2s.8 1 1.5 1.4c.7.4 1.8.9 3.2 1.4 2.4.9 4.1 1.8 5 2.8 1 1.1 1.4 2.4 1.4 4z"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>cost_allocation</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M69.5 30.5l12.2-12.1"/><path class="st0 line" d="M95 12.8c0 4.3-3.5 7.8-7.8 7.8s-7.8-3.5-7.8-7.8S82.9 5 87.2 5 95 8.5 95 12.8zM73.3 73.4l8.3 8.3"/><path class="st0 line" d="M87.2 95c-4.3 0-7.8-3.5-7.8-7.8s3.5-7.8 7.8-7.8 7.8 3.5 7.8 7.8-3.5 7.8-7.8 7.8zM30.4 30.5L18.3 18.4"/><path class="st0 line" d="M5 12.8c0 4.3 3.5 7.8 7.8 7.8s7.8-3.5 7.8-7.8S17.1 5 12.8 5 5 8.5 5 12.8zM26.7 73.4l-8.3 8.3"/><path class="st0 line" d="M12.8 95c4.3 0 7.8-3.5 7.8-7.8s-3.5-7.8-7.8-7.8S5 82.9 5 87.2c-.1 4.3 3.4 7.8 7.8 7.8zM67.6 48h11.7"/><path class="st0 line" d="M95 48c0 4.3-3.5 7.8-7.8 7.8s-7.8-3.5-7.8-7.8 3.5-7.8 7.8-7.8S95 43.7 95 48zM32.4 48H20.6"/><path class="st0 line" d="M5 48c0 4.3 3.5 7.8 7.8 7.8s7.8-3.5 7.8-7.8-3.5-7.8-7.8-7.8S5 43.7 5 48zM43.4 56.6c0 3.6 3 6.6 6.6 6.6 3.6 0 6.6-3 6.6-6.6 0-3.6-3-6.6-6.6-6.6-3.6 0-6.6-3-6.6-6.6 0-3.6 3-6.6 6.6-6.6 3.6 0 6.6 3 6.6 6.6M50 63.2v4.4M50 32.4v4.4"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>expense_management</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-miterlimit:10;}</style><path class="st0 line" d="M97.7 54.3v-8.5L87 44c-.4-2.5-1.1-4.9-1.9-7.2l8.4-6.9-4.3-7.4L79 26.3c-1.6-1.9-3.4-3.7-5.3-5.3l3.8-10.2-7.4-4.3-6.9 8.4C61 14 58.5 13.4 56 13L54.3 2.3h-8.5L44 13c-2.5.4-4.9 1.1-7.2 1.9l-6.9-8.4-7.4 4.3L26.3 21c-1.9 1.6-3.7 3.4-5.3 5.3l-10.2-3.8-4.3 7.4 8.4 6.9C14 39 13.4 41.5 13 44L2.3 45.7v8.5L13 56c.4 2.5 1.1 4.9 1.9 7.2l-8.4 6.9 4.3 7.4L21 73.7c1.6 1.9 3.4 3.7 5.3 5.3l-3.8 10.2 7.4 4.3 6.9-8.4c2.2.9 4.7 1.5 7.2 1.9l1.8 10.7h8.5L56 87c2.5-.4 4.9-1.1 7.2-1.9l6.9 8.4 7.4-4.3L73.7 79c1.9-1.6 3.7-3.4 5.3-5.3l10.2 3.8 4.3-7.4-8.4-6.9C86 61 86.6 58.5 87 56l10.7-1.7zM50 77.4c-15.2 0-27.4-12.3-27.4-27.4 0-15.2 12.3-27.4 27.4-27.4 15.2 0 27.4 12.3 27.4 27.4 0 15.2-12.2 27.4-27.4 27.4zM56.5 42.3s.5-6.8-6.5-6.8-6.8 5.4-6.8 5.4-.6 6.2 7.2 8.3c7.7 2.1 7 8.8 7 8.8s-.2 6.7-7.3 6.7-7.4-5.9-7.4-5.9M50 30.2v5.3M50 64.7V70"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>fact_sheet</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M16.7 28.5h17.6M16.7 40.2H48M16.7 52h23.5M16.7 63.7h19.5"/><path class="st1 line" d="M95 71.4c0 13-10.5 23.5-23.5 23.5S48 84.4 48 71.4s10.5-23.5 23.5-23.5S95 58.5 95 71.4z"/><path class="st0 line" d="M84.3 65L69.4 81.4 58.7 70.7"/><path class="st1 line" d="M46.1 87.2H4.9V5H48l19.6 19.6v17.6"/><path class="st0 line" d="M48 5v19.6h19.6"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>infographic</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M30.4 51.9v34.8l-11.7 8.2L7 86.7V51.9"/><path class="st0 line" d="M30.4 51.9l-11.7 7.9L7 51.9l11.7-7.8zM18.7 59.8v35.1M61.6 32.4v54.3l-11.7 8.2-11.7-8.2V32.4"/><path class="st0 line" d="M61.6 32.4l-11.7 7.8-11.7-7.8 11.7-7.8zM49.9 40.2v54.7M92.9 12.9v73.8l-11.8 8.2-11.7-8.2V12.9"/><path class="st0 line" d="M92.9 12.9l-11.8 7.8-11.7-7.8 11.7-7.8zM81.1 20.7v74.2"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>insight_analytics</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><circle class="st0 line" cx="39.8" cy="43.8" r="16.4"/><path class="st0 line" d="M31.7 29.5l8.1 14.3h16.4M39.8 43.8l2.7 16.1M64.5 27.4h12M64.5 35.6h12M64.5 43.8h12"/><path class="st0 line" d="M82.8 19.2v49.1H17.2V19.2zM9 76.5V17.1c0-3.4 2.8-6.1 6.1-6.1h69.6c3.4 0 6.1 2.8 6.1 6.1v59.4"/><path class="st0 line" d="M58.2 76.5v4.1H41.8v-4.1H4.9v8.2c0 2.3 1.8 4.1 4.1 4.1h82c2.3 0 4.1-1.8 4.1-4.1v-8.2H58.2z"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>item</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M81.3 12.8v47H18.7v-47zM10.9 67.6V10.9c0-3.2 2.6-5.9 5.9-5.9h66.5c3.2 0 5.9 2.6 5.9 5.9v56.7"/><path class="st0 line" d="M57.8 67.6v3.9H42.2v-3.9H6.9v7.8c0 2.2 1.8 3.9 3.9 3.9H89c2.2 0 3.9-1.8 3.9-3.9v-7.8H57.8zM7 95h85.9M50 95V79.3"/><circle class="st0 line" cx="63.7" cy="26.5" r="5.9"/><circle class="st0 line" cx="63.7" cy="46.1" r="5.9"/><circle class="st0 line" cx="36.3" cy="36.3" r="5.9"/><path class="st1 line" d="M41.6 33.8L58 27.3M41.6 38.8L58 45.3"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>managed</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:3;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:3;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st4{fill:none;stroke:#000;stroke-width:3;stroke-miterlimit:10;}</style><path class="st0 line" d="M49 91.3H4.5v-89h46.6l21.2 21.2v36"/><path class="st1 line" d="M51.1 2.3v21.2h21.2"/><path class="st4 line" d="M62.81 79.39l15.98-15.98 10.747 10.748-15.98 15.98zM58.4 84.8l-3.1 10.8c-.2.6 0 1.2.4 1.6.3.3.7.5 1.2.5.2 0 .3 0 .5-.1l10.8-3.1-9.8-9.7zM95.1 66.3l-8.4-8.4c-.7-.7-1.7-.7-2.4 0l-5.5 5.5 10.8 10.8 5.5-5.5c.6-.7.6-1.7 0-2.4zM19.4 35.4l6.3 6.3 10.5-10.5M40.7 39.7h21M40.7 56.7h21M19.4 52.5l6.3 6.3 10.5-10.5"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>mms</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:#000;} .st3{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M48 59.8v27.4c0 4.3-3.5 7.8-7.8 7.8H12.8C8.5 95 5 91.5 5 87.2V28.5c0-4.3 3.5-7.8 7.8-7.8h11.7M24.5 36.3H4.9M48 79.3H4.9M20.6 28.5h3.9"/><path class="st0 line" d="M94.9 34.3c0 3.2-2.6 5.9-5.9 5.9H67.5L55.8 51.9V40.2H42.1c-3.2 0-5.9-2.6-5.9-5.9V10.9c0-3.2 2.6-5.9 5.9-5.9H89c3.2 0 5.9 2.6 5.9 5.9v23.4z"/><path class="st1 line" d="M67.5 18.7c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM79.3 18.7c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2c-.1-1.1-1-2-2-2zM55.9 18.7c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/><path class="st3 line" d="M28.4 87.2c0 1.1-.9 2-2 2s-2-.9-2-2 .9-2 2-2 2 .9 2 2z"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>on-prem</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0 line{fill:none;stroke:#000;stroke-width:2;stroke-miterlimit:10;}</style><path class="st0 line" d="M91.9 81.4c3.5 0 6.3-2.8 6.3-6.3v-8.4c0-1.2-.9-2.1-2.1-2.1H94V10.2c0-4.6-3.8-8.4-8.4-8.4H14.4C9.8 1.8 6 5.6 6 10.2v54.4H3.9c-1.2 0-2.1.9-2.1 2.1v8.4c0 3.5 2.8 6.3 6.3 6.3h39.8V94H4c-1.2 0-2.1.9-2.1 2.1 0 1.2.9 2.1 2.1 2.1h91.9c1.2 0 2.1-.9 2.1-2.1 0-1.2-.9-2.1-2.1-2.1H52.1V81.4h39.8zM14.4 12.3c0-1.2.9-2.1 2.1-2.1h67c1.2 0 2.1.9 2.1 2.1v46.1c0 1.2-.9 2.1-2.1 2.1h-67c-1.2 0-2.1-.9-2.1-2.1V12.3zM6 75.1v-6.3h33.5v2.1c0 1.2.9 2.1 2.1 2.1h16.8c1.2 0 2.1-.9 2.1-2.1v-2.1H94v6.3c0 1.2-.9 2.1-2.1 2.1H8.1C7 77.2 6 76.3 6 75.1zM37.4 52.1h25.1c1.2 0 2.1-.9 2.1-2.1V33.2c0-1.2-.9-2.1-2.1-2.1H37.4c-1.2 0-2.1.9-2.1 2.1V50c0 1.2 1 2.1 2.1 2.1zM40.2 27.9c0-5.4 4.4-9.8 9.8-9.8s9.8 4.4 9.8 9.8v3.3H40.2v-3.3z"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>play</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}</style><path class="st0 line" d="M36.2 73.5v-47L77.3 50zM42.3 5.6c30.8-5.1 57.1 21.2 52 52-3.1 18.6-18.1 33.7-36.7 36.7-30.8 5-57-21.2-52-52 3-18.5 18-33.6 36.7-36.7z"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>rss</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M22.6 44.2c18.4 0 33.3 14.9 33.3 33.3M22.6 24.6c29.2 0 52.9 23.7 52.9 52.9"/><circle class="st0 line" cx="30.4" cy="69.6" r="5.9"/><path class="st0 line" d="M95 83.3C95 89.8 89.7 95 83.2 95H16.7C10.2 95 5 89.7 5 83.3V16.7C4.9 10.3 10.2 5 16.7 5h66.6C89.7 5 95 10.3 95 16.7v66.6z"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>press_release</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M75.4 57.9V7H24.5v50.9M36.3 18.7h3.9M44.1 26.6h19.6M36.3 34.4h27.4M36.3 42.2h27.4M36.3 50.1h27.4"/><path class="st0 line" d="M95 93.1H5V65.7h29.4v3.9c0 4.3 3.5 7.8 7.8 7.8h17.6c4.3 0 7.8-3.5 7.8-7.8v-3.9H95v27.4zM4.9 65.7l13.7-23.5h5.9M75.4 42.2h5.9L95 65.7"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>research</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M46.1 87.3H4.9V4.9h43.2l19.6 19.6v17.7"/><path class="st1 line" d="M48.1 4.9v19.6h19.6"/><path class="st1 line" d="M16.7 52h7.8v15.7h-7.8zM32.4 44.1h7.8v23.5h-7.8zM24.6 32.3h7.8v35.3h-7.8zM12.8 67.7h27.5"/><path class="st0 line" d="M85.8 70.8c0 8.2-6.7 14.9-14.9 14.9S56 79 56 70.8c0-8.3 6.7-14.9 14.9-14.9s14.9 6.7 14.9 14.9z"/><path class="st1 line" d="M81.4 81.4l13.8 13.7"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>saas</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-miterlimit:10;}</style><path class="st0 line" d="M69.8 13.7c-4-7-11.6-11.4-19.8-11.4-11.2 0-20.6 8-22.3 18.5-7.3-.4-13.5 5.5-13.5 12.6 0 10 8.6 12.6 13.7 12.6h42.9c5.2-.7 15-4.9 15-16.2 0-8.7-7.1-15.8-16-16.1z"/><circle class="st0 line" cx="30.1" cy="63.9" r="2"/><path class="st0 line" d="M85.8 73.9V56c0-1.1-.9-2-2-2H16.2c-1.1 0-2 .9-2 2v17.9h71.6zm-15.9-14c2.2 0 4 1.8 4 4s-1.8 4-4 4-4-1.8-4-4 1.8-4 4-4zm-11.9 0c2.2 0 4 1.8 4 4s-1.8 4-4 4-4-1.8-4-4 1.8-4 4-4z"/><circle class="st0 line" cx="30.1" cy="87.8" r="2"/><path class="st0 line" d="M14.2 77.8v17.9c0 1.1.9 2 2 2h67.6c1.1 0 2-.9 2-2V77.8H14.2zm43.8 14c-2.2 0-4-1.8-4-4s1.8-4 4-4 4 1.8 4 4c-.1 2.2-1.9 4-4 4zm11.9 0c-2.2 0-4-1.8-4-4s1.8-4 4-4 4 1.8 4 4-1.8 4-4 4z"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>service_support</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-miterlimit:10;}</style><path class="st0 line" d="M75.3 11.3c8.8 6.8 14.1 17.4 14.1 26.9 0 8.7-3.7 16.4-8.8 22.5-4.8 5.8-3.5 24.5 1.7 24.5 1.1 0 2.4 1.1 2.4 2.4v7.8c0 1.3-1.1 2.4-2.4 2.4H43.2c-1.3 0-2.4-1.1-2.4-2.4 0-1.4 9.8-8.3 3.3-14.6-2.6-2.2-19.3 2.4-19.3-3.5.2-1.1 0-2.8.3-2.9m36-69.2c-.4 0-4.8-.4-5.2-.4-13.1 0-24.4 7.5-29.9 18.5-1.8 3.6-2.3 6.1-2.3 10.9 0 2.3.6 4.4.6 6.2 0 2.1-1.5 4.2-1.5 4.4-.4.8-1.8 3.4-4.9 7.8-1.3 1.5-1 3.3 0 3.9 1.4.8 3.6.9 3.6 2 0 1.6-1.6 2.2-1.6 3.5 0 1.3 1.2 1.4 1.2 1.7 0 .4-.9.3-.9 1.5s1.4 1.5 1.7 1.7c0 0 .4 2.7.6 3.5"/><path class="st0 line" d="M8.6 74.7c0 2.7 2.2 4.8 4.8 4.8 2 0 3.8-1.3 4.5-3.1 12.8-2.3 28.2-10.5 35.8-21.6 1.4-2 4.5-1.8 6.6-1.8 6.5 0 11.8-5.3 11.8-11.8 0-4.4-2.4-8.2-5.9-10.2L76.9 8c.4-1.3-.3-2.6-1.6-2.9L65.1 2.2c-1.3-.3-2.5.4-2.8 1.7l-1.7 25.4h-.3c-6.5 0-11.8 5.3-11.8 11.8 0 2.1.6 4.1 1.6 5.9.6 1.5.9 3.3-.3 5C42.9 62.1 29 69.8 17.2 71.7c-.9-1.1-2.2-1.8-3.7-1.8-2.7 0-4.9 2.1-4.9 4.8zm47.1-33.6c0-2.5 2.1-4.6 4.6-4.6 2.5 0 4.6 2.1 4.6 4.6s-2.1 4.6-4.6 4.6c-2.5 0-4.6-2-4.6-4.6z"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>share_service</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-miterlimit:10;}</style><path class="st0 line" d="M25.5 60.8c13-8.1 13.2-20.2 13.2-20.7M75.4 60.8c-13-8.1-13.2-20.2-13.2-20.7"/><path class="st0 line" d="M49.9 15.8c3.8 0 7-3.1 7-7 0-3.8-3.1-7-7-7-3.8 0-7 3.1-7 7 .1 3.9 3.2 7 7 7zM66 31.9c0-5.3-1.8-11.2-11.4-11.6l-4.8 4.5-4.7-4.5c-9.7.3-11.3 6.5-11.4 11.6v2.4H66v-2.4zM18.6 81.3c3.5 0 6.3-2.8 6.3-6.3s-2.8-6.3-6.3-6.3-6.3 2.8-6.3 6.3c-.1 3.4 2.8 6.3 6.3 6.3zM32.1 95.9c0-4.4-1.6-9.5-9.6-9.7l-4 3.8-3.9-3.8c-8.2.3-9.5 5.5-9.6 9.7v2h27.2v-2zM43.6 74.9c0 3.5 2.8 6.3 6.3 6.3s6.3-2.8 6.3-6.3-2.8-6.3-6.3-6.3c-3.4 0-6.3 2.9-6.3 6.3zM63.5 95.9c0-4.4-1.6-9.5-9.6-9.7l-4 3.8-4-3.8c-8.2.3-9.5 5.5-9.6 9.7v2h27.2v-2zM74.9 74.9c0 3.5 2.8 6.3 6.3 6.3s6.3-2.8 6.3-6.3-2.8-6.3-6.3-6.3-6.3 2.9-6.3 6.3zM81.2 89.9l-3.9-3.8c-8.2.3-9.5 5.5-9.6 9.7v2h27.2v-2c0-4.4-1.6-9.5-9.6-9.7l-4.1 3.8zM49.9 40.1v22.2"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>tem</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M43.1 38.4c2.5-2.5 2.7-6.3.4-8.5l-6-6c-2.2-2.2-6-2-8.5.4L25.3 28c-3.2 3.2-3.9 8-1.7 11.4C33 54.2 45.8 67.1 60.7 76.5c3.4 2.2 8.2 1.5 11.4-1.7l3.7-3.7c2.5-2.5 2.7-6.3.4-8.5l-6-6c-2.2-2.2-6-2-8.5.4l-2.2 2.2C52.8 53.8 46.3 47.4 41 40.7l2.1-2.3zM73.4 73.4c2 2 6.1 21.5-23.4 21.5C25.2 94.9 5.1 74.8 5.1 50 5.1 25.2 25.2 5.1 50 5.1S94.9 25.2 94.9 50c0 9.4-2.9 18.2-7.9 25.4"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>usage</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-miterlimit:10;}</style><path class="st0 line" d="M3.8 80.6c-1.5 1.2-1.5 3.4-1.5 4.9v6.6h24.2v-6.6c0-1.5.3-4.2.7-5.2.4-1 3.4-6.7 3.4-6.7H12.8c0-.1-7.3 5.7-9 7zM34.1 34.8L22.5 23.2 10.9 34.8c-1 1.1-.4 2 .5 2H14v30.7h17V36.8h2.6c.8 0 1.4-.9.5-2zM62.5 80.3l-3.4-6.7H41.4L38 80.3c-.7 1.5-.7 3.8-.7 5.2v10.9h25.8V85.5c.1-1.5 0-3.8-.6-5.2zM37.3 21h4.5v46.4h17V21h4.5c1.5 0 1.9-1.2.9-2.3L50.3 4.1 36.4 18.7c-1 1.1-.6 2.3.9 2.3zM78.1 67.4h8.5V32.3h2.6c.9 0 1.5-.9.5-2L78.1 18.7 66.5 30.3c-1 1.1-.4 2 .5 2h2.6v35.1h8.5zM74 85.5v6.6h24.2v-6.6c0-1.5-.3-3.8-1.5-4.9l-9.1-7.1H70l3.4 6.7c.7 1.4.6 3.8.6 5.3z"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>video</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;}</style><circle class="st0 line" cx="69.5" cy="73.5" r="21.5"/><path class="st0 line" d="M46 55.9H8.9C6.7 55.9 5 54.1 5 52V9c-.1-2.2 1.7-4 3.9-4h50.9c2.2 0 3.9 1.8 3.9 3.9V42"/><path class="st1 line" d="M94.9 51.9L71.4 40.2V20.7L94.9 8.9z"/><path class="st0 line" d="M63.6 63.7v19.6l17.7-9.8z"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>webinar</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M8.9 74.7V21.9c0-3.2 2.6-5.9 5.9-5.9h70.5c3.2 0 5.9 2.6 5.9 5.9v52.9"/><path class="st0 line" d="M59.8 74.7v3.9H40.2v-3.9H4.9v7.8c0 2.2 1.8 3.9 3.9 3.9H91c2.2 0 3.9-1.8 3.9-3.9v-7.8H59.8zM59.5 35.1c1.8 7.3-4.6 13.7-11.9 11.9-3.4-.8-6.2-3.6-7.1-7.1-1.8-7.3 4.6-13.7 11.9-11.9 3.4.9 6.2 3.7 7.1 7.1zM65.6 63H34.3c0-8.6 7-15.7 15.7-15.7s15.6 7 15.6 15.7z"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>wem</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M92.5 87.2c0 4.3-3.5 7.8-7.8 7.8H49.5c-4.3 0-7.8-3.5-7.8-7.8V12.8c0-4.3 3.5-7.8 7.8-7.8h35.2c4.3 0 7.8 3.5 7.8 7.8v74.4zM92.5 20.7H41.6M92.5 79.3H41.6M57.3 12.8h19.5"/><path class="st1 line" d="M69 87.2c0 1.1-.9 2-2 2s-2-.9-2-2 .9-2 2-2 2 .9 2 2zM38.8 83.2c-17.3-23.1-17.3-47.6 0-66.4M10.1 63.7h31.5M11.6 32.4h30M7 48h34.6"/><path class="st1 line" d="M41.6 16.8c-.5 0-.9-.1-1.4-.1-18.4 0-33.3 14.1-33.3 32.5s14.7 34 33.2 34c.5 0 1-.1 1.5-.1V16.8z"/> </svg></div></td>
                        </tr>
                        <tr>
                            <td>whitepaper, white-paper</td>
                            <td><div class="resource__icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;} .st2{fill:#000;}</style><path class="st0 line" d="M16.7 28.5h17.6M16.7 40.3H48M16.7 52h23.5M16.7 63.8h19.6"/><path class="st1 line" d="M95 71.5C95 84.5 84.5 95 71.5 95 58.6 95 48 84.5 48 71.5S58.6 48 71.5 48C84.5 48 95 58.5 95 71.5zM46.1 87.3H4.9V5H48l19.6 19.6v17.6"/><path class="st0 line" d="M48 5v19.6h19.6"/><path class="st2 line" d="M70.9 81.6h-2.1l-3.6-12c-.2-.5-.4-1.2-.6-2-.2-.8-.3-1.3-.3-1.5-.2 1.1-.5 2.3-.9 3.5l-3.4 12h-2l-4.8-17.9h2.2l2.8 11c.4 1.5.7 2.9.9 4.2.2-1.5.5-2.9 1-4.4l3.2-10.9h2.2l3.4 11c.4 1.3.7 2.7 1 4.3.2-1.2.4-2.6.9-4.2l2.8-11h2.2l-4.9 17.9zM89.9 69c0 1.8-.6 3.2-1.9 4.2s-3 1.5-5.3 1.5h-2.1v7h-2.1V63.8h4.6c4.5 0 6.8 1.7 6.8 5.2zm-9.3 3.8h1.9c1.8 0 3.2-.3 4-.9.8-.6 1.2-1.5 1.2-2.9 0-1.2-.4-2.1-1.2-2.6-.8-.6-2-.9-3.6-.9h-2.3v7.3z"/> </svg></div></td>
                        </tr>
                    </table>
                </div>
            </div>

        </form>
    </div>
    <?php
}
