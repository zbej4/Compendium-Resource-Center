<?php
/**
 * Plugin Name: Compendium Resource Center
 * Description: Provides the functionality to create and maintain a resource center that integrates several post types and categories.
 * Version:	 0.1
 * Author: Brandon Jones
 */

class Compendium_Resources
{
    /**
     * Do the resources template layers.
     *
     * @access public
     * @static
     * @return void
     */
    public static function do_resources()
    {
        // Markup
        $layers_open = '<article class="layers">';
        $layers_close = '</article>';

        // Get categories selected
        $categories = get_field('resources_categories');
        $blog_categories = get_field('blog_categories');

        // Get document types
        $document_types = get_terms('document-types', array('hide_empty'=>false));

        // Make sure they're not empty
        if( empty($categories) || empty($blog_categories) )
            return;

        $clear_btn = '<a href="'. site_url( "/knowledge-center/" ) . '" title="Reset" class="clear_form_link">Reset</a>';
        $search_icon = '<a href="#" title="Search" class="search_form_link"><i class="fa fa-search"></i></a>';
        $resource_head = '<div class="inner clearfix"><div class="resource_head_bar"><h2 class="resource_head">Latest Resources</h2>';
        $filter_form_open = '<div class="resource_bar_nav"><form action="'. site_url('/knowledge-center/') .'" method="get" class="filter-form" id="filter">';
        $filter_form_close = '<button>Apply</button>'. $clear_btn .$search_icon;
        $topic_menu_open = '<select id="topic-menu" name="topic-filter"><option>Browse by Topic</option>';
        $topic_menu_close = '</select>';
        $type_menu_open = '<select id="type-menu" name="type-filter"><option>Browse by Type</option>';
        $type_menu_close = '</select>';
        $search_form = '<div class="resource_bar_search">
												<div class="resource-search">
													<input type="submit" class="resource-search-btn" value="">
													<input type="text" value="" name="k_search" class="resource-input" placeholder="Search">
													<a href="#" class="search_form_close"><i class="fa fa-close"></i></a>
												</div>
											</div>';

        // Get the category names as an array of ids and names
        $terms = array();
        foreach( $categories as $category )
            $terms[$category] = get_term($category, 'document-category')->name;
        foreach( $blog_categories as $category)
            $terms[$category] = get_term($category, 'category')->name;

        //Combine Categories from post types and create menu options
        $terms = array_unique($terms);
        //Create array for args
        $cat_array = array();
        foreach( $terms as $term=>$value)
            $cat_array[$term] = sanitize_title_with_dashes($value);


        // Start the output
        echo $resource_head . $filter_form_open . $topic_menu_open;
        foreach ($terms as $cat=>$value) {
            echo'<option value="' . sanitize_title_with_dashes($value) .'">'. $value .'</option>';
        }
        echo $topic_menu_close . $type_menu_open;
        echo '<option value="blog-posts">Blog Posts</option>';
        foreach ($document_types as $doctype){
            echo'<option value="' . sanitize_title_with_dashes($doctype->name) .'">'. $doctype->name .'</option>';
        }
        echo $type_menu_close . $filter_form_close . $search_form . '</div></form></div>';

        echo $layers_open;

        // The posts
        $args = array(
            'post_type' => array('document', 'post' ),
            'post_status' => 'publish',
            'posts_per_page' => 22,
            'category_name' => implode(',', $cat_array),
            'tax_query' => array(
                'relation' => 'OR',
                array(
                    'taxonomy' => 'document-category',
                    'field' => 'slug',
                    'terms' => $cat_array
                )
            )
        );

        if (get_query_var('topic-filter')||get_query_var('type-filter')||get_query_var('k_search'))
        {
            $topic = get_query_var('topic-filter');
            $type = get_query_var('type-filter');
            $search_terms = get_query_var('k_search');
            $args = static::knowledge_filter_function($args, $topic, $type, $search_terms);
        }
        /*if (get_query_var('k_search')){
            $search_terms = get_query_var('k_search');
            $args = static::knowledge_search_function($args, $search_terms);

        }*/

        Calero::populate_resources($args);

        echo $layers_close;
    }

    /**
     * Populate Resources Page.
     *
     * @access public
     * @static
     * @param $args Arguments for WP_Query
     * @param $resource_type String specifying featured post or not
     * @return void
     */
    public static function populate_resources($query_args)
    {
        //Store original query Arguments
        $args = $query_args;

        $do_not_duplicate = '';

        //Markup
        $layer_open = '<section class="layer layer--resources editor-styles form-styles"><div class="inner clearfix">';
        $layer_close = '</div></section>';

        $item_close = '</div>';

        $image_open = '<div class="resource__image">';
        $image_close = '</div>';

        $infobar_open = '<a class="info_link" href="%LINK%"><div class="resource__infobar">';
        $infobar_close = '<i class="fa fa-angle-right"></i></div></a>';

        $icon_open = '<div class="resource__icon">';
        $icon_close = '</div>';

        $category_open = '<p class="resource__category">';
        $category_close = '</p>';

        $link_open = '<a class="resource__link" href="%LINK%">';
        $link_close = '</a>';

        $featured_icon = '<div class="resource__icon-featured"><i class="fa fa-star"></i></div>';

        // Do icons?
        $icons = get_field('resources_icons', 6330);

        $output = '';

        $output .= $layer_open;

        if ( !is_paged() ){
            $featured_args = $query_args;

            $featured_args['posts_per_page'] = 12;
            $featured_args['meta_query'] = array(
                'relation' => 'AND',
                'featured' => array(
                    'key' => 'featured-meta-checkbox',
                    'value' => 'yes'
                ),
                'feat-order' => array(
                    'key' => 'featured-meta-order',
                    'type' => 'NUMERIC'
                )
            );
            $featured_args['orderby'] = array('feat-order' => 'ASC');
            /*$featured_args['meta_key'] = 'featured-meta-checkbox';
            $featured_args['meta_value'] = 'yes';*/

            //Featured posts query
            $documents = new WP_Query($featured_args);

            // Make sure we have posts and format them
            if( !is_wp_error($documents) && $documents->have_posts() )
            {
                $featured_exists = true;
                foreach( $documents->posts as $document )
                {
                    $item_open = '<div class="resource featured-resource %TYPE%">';

                    //Store id to not duplicate post
                    $do_not_duplicate[] = $document->ID;

                    $id = $document->ID;

                    // Title
                    //Check if has external link
                    $external_link = get_field('document_external_url', $id) ?: '';
                    $final_link = empty($external_link) ? get_permalink($id) : $external_link;
                    $title = get_the_title($id);
                    $title = (strlen($title) > 60) ? trim(substr($title, 0, 60))."..." : $title;
                    $title = str_replace('%LINK%', $final_link, $link_open) . $title . $link_close;

                    //Info LINK
                    $infobar_open_link = str_replace('%LINK%', $final_link, $infobar_open);

                    // Category
                    $terms = get_the_terms($id, 'document-types');
                    $category = empty($terms) ? '' : $terms[0]->name;
                    //$category = get_field('document_category', $id) ?: $category; // Override if set
                    $category = !empty($category) ? $category_open . $category . $category_close : '';
                    if($document->post_type == 'post')
                    {
                        $category = $category_open . 'Blog Post' . $category_close;
                    }

                    // Icon for Category
                    $icon = '';
                    if($icons)
                    {
                        if($document->post_type == 'post')
                        {
                            $icon = $icon_open . Calero::get_icon('rss') . $icon_close;
                        }
                        else{
                            $icon = $icon_open . Calero::get_icon($terms[0]->slug) . $icon_close;
                        }
                    }

                    //Add class for category
                    if($document->post_type == 'post')
                        $item_open = str_replace( '%TYPE%', 'blog-post', $item_open);
                    else
                        $item_open = str_replace( '%TYPE%', $terms[0]->slug, $item_open);

                    // Social sharing
                    // Share
                    $share = '<a class="resource_social addthis_button_compact" addthis:url="' . get_permalink($document->ID) . '" addthis:title="' . get_the_title($document->ID)	. '"><span>Share</span></a>';

                    // Image
                    $image = get_field('document_thumb_image', $id) ?: '';
                    $image = empty($image) ? '' : $image_open . '<img src="' . $image['url'] . '" alt="' . $image['alt'] . '">' . $share . $image_close;
                    if($document->post_type == 'post')
                    {
                        $image = $image_open . get_the_post_thumbnail($id, 'medium') . $share . $image_close;
                    }

                    $output .= $item_open . $featured_icon . $image . $title . $infobar_open_link . $icon  . $category . $infobar_close . $item_close;
                }

                wp_reset_postdata();
            }
        }

        //Remaining posts query
        $args['post__not_in']=$do_not_duplicate;
        $args['paged'] = get_query_var( 'paged' );
        if (!is_paged()) {
            $args['posts_per_page']= $args['posts_per_page'] - count($do_not_duplicate);
        }

        $documents = new WP_Query($args);

        //Pagination
        $pagination = Em_Blog::display_pagination($documents);

        // Make sure we have posts and format them
        if( !is_wp_error($documents) && $documents->have_posts() )
        {
            foreach( $documents->posts as $document )
            {
                $item_open = '<div class="resource %TYPE%">';
                $id = $document->ID;

                // Title
                //Check if has external link
                $external_link = get_field('document_external_url', $id) ?: '';
                $final_link = empty($external_link) ? get_permalink($id) : $external_link;
                $title = get_the_title($id);
                $title = (strlen($title) > 60) ? trim(substr($title, 0, 60))."..." : $title;
                $title = str_replace('%LINK%', $final_link, $link_open) . $title . $link_close;

                //Info LINK
                $infobar_open_link = str_replace('%LINK%', $final_link, $infobar_open);

                // Category
                $terms = get_the_terms($id, 'document-types');
                $category = empty($terms) ? '' : $terms[0]->name;
                //$category = get_field('document_category', $id) ?: $category; // Override if set
                $category = !empty($category) ? $category_open . $category . $category_close : '';
                if($document->post_type == 'post')
                {
                    $category = $category_open . 'Blog Post' . $category_close;
                }

                // Icon for Category
                $icon = '';
                if($icons)
                {
                    if($document->post_type == 'post')
                    {
                        $icon = $icon_open . Calero::get_icon('rss') . $icon_close;
                    }
                    else{
                        $icon = $icon_open . Calero::get_icon($terms[0]->slug) . $icon_close;
                    }
                }

                //Add class for category
                if($document->post_type == 'post')
                    $item_open = str_replace( '%TYPE%', 'blog-post', $item_open);
                else
                    $item_open = str_replace( '%TYPE%', $terms[0]->slug, $item_open);


                // Social sharing
                // Share
                $share = '<a class="resource_social addthis_button_compact" addthis:url="' . get_permalink($document->ID) . '" addthis:title="' . get_the_title($document->ID)	. '"><span>Share</span></a>';

                // Image
                $image = get_field('document_thumb_image', $id) ?: '';
                $image = empty($image) ? '' : $image_open . '<img src="' . $image['url'] . '" alt="' . $image['alt'] . '">' . $share . $image_close;
                if($document->post_type == 'post')
                {
                    $image = $image_open . get_the_post_thumbnail($id, 'post-thumbnail') . $share . $image_close;
                }

                $output .= $item_open . $image . $title . $infobar_open_link . $icon . $category . $infobar_close . $item_close;

            }

            $output .= '<div class="clearfix"></div>' . $pagination . $layer_close;

            wp_reset_postdata();
        }
        else if (!$documents->have_posts() && !$featured_exists) {
            $output = $layer_open . '<h2 style="text-align:center;">No posts found</h2>' . $layer_close;
        }
        echo $output;
    }

    /**
     * Stacked filter for Knowledge Center
     *
     * @access public
     * @static
     * @param args array
     * @return Args array for WP_Query
     */
    /**************************************/
    public static function knowledge_filter_function($pass_args = null, $topic_filter = null, $type_filter = null, $k_search = null){
        $args = $pass_args;

        //for categories
        //If topic isn't set, set all topics in args
        if($topic_filter != 'Browse by Topic')
        {
            $args['category_name'] = $topic_filter;
            $args['tax_query'] = array(
                'relation' => 'OR',
                array(
                    'taxonomy' => 'document-category',
                    'field' => 'slug',
                    'terms' => $topic_filter
                )
            );
        }

        //for document type
        //If type isn't set, set both post_types in args
        if($type_filter != 'Browse by Type')
        {
            //if type is blog posts
            if ($type_filter == 'blog-posts') {
                $args['post-type'] = 'post';
                $args['tax_query'] = '';
            }
            //type is a document-type
            else {
                $args['category_name'] = '';
                $args['post_type'] = 'document';
                $args['tax_query']['relation'] = 'AND';
                $args['tax_query'][] =
                    array(
                        'taxonomy' => 'document-types',
                        'field' => 'slug',
                        'terms' => $type_filter
                    );
            }
        }

        //Get search terms
        if($k_search != null){
            $args['s'] = $k_search;
        }

        return $args;
    }
}