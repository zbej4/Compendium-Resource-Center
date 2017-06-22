<?php
/**--------------------------------------------------------
 *
 *  Function to Register each selected post type
 *
 *-------------------------------------------------------*/
function compendium_register_type($slug, $name, $pname, $dicon) {
    $pt_slug = $slug;
    $tax_slug = $slug . '-category';

    register_post_type($pt_slug, array(
        'labels' => array(
            'name' => $pname,
            'singular_name' => $name,
            'add_new' => 'Add New',
            'add_new_item' => 'Add New '.$name,
            'edit_item' => 'Edit '.$name,
            'new_item' => 'New '.$name,
            'view_item' => 'View '.$name,
            'search_items' => 'Search '.$pname,
            'not_found' => 'No '.$pname.' found',
            'not_found_in_trash' => 'No '.$pname.' found in trash',
        ),
        'public' => true,
        'menu_position' => 55,
        'supports' => array('title','editor','thumbnail'),
        'menu_icon' => $dicon,
        'rewrite' => array(
            'slug' => 'resource-center/' . $pt_slug,
            'with_front' => true
        ),
    ));

    register_taxonomy($tax_slug, $pt_slug, array(
        'labels' => array(
            'name' => 'Categories',
            'singular_name' => 'Category',
        ),
        'hierarchical' => true,
    ));
}