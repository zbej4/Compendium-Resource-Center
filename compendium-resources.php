<?php
class Compendium_Resources
{
    /**--------------------------------------------------------
     *
     *  Create the resources template layers
     *
     *-------------------------------------------------------*/
    public static function do_test($activePosts){
        return print_r($activePosts);
    }
    public static function do_resources($activePosts)
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

        Compendium_Resources::populate_resources($args);

        echo $layers_close;
    }

    /**--------------------------------------------------------
     *
     *  Populate Resources Page.
     *
     *-------------------------------------------------------*/
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

    /**--------------------------------------------------------
     *
     *  Stacked filter for Knowledge Center
     *
     *-------------------------------------------------------*/
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

    /**--------------------------------------------------------
     *
     *  Return the svg icon based on name
     *
     *-------------------------------------------------------*/
    public static function get_icon( $name )
    {
        switch( $name )
        {
            case 'analyst_report':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;}</style><circle class="st0 line" cx="87" cy="59.8" r="7.8"/><circle class="st0 line" cx="87" cy="87.2" r="7.8"/><circle class="st0 line" cx="55.8" cy="75.4" r="7.8"/><path class="st0 line" d="M62.7 71.9l17.4-8.6M63.1 78.2l16.6 6.2"/><path class="st1 line" d="M46 87.2H4.9V5H48l19.5 19.6v33.2"/><path class="st0 line" d="M48 5v19.6h19.5M16.6 32.4v39.1h23.5M16.6 55.9l10.8-10.8 7.8 7.8 12.7-12.7"/></svg>';

            case 'assets':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-miterlimit:10;}</style><path class="st0" d="M26.7 17.7h46.6v64.1H26.7z"/><path class="st0 line" d="M26.7 81.8v6c0 5.5 3.7 9.9 8.3 9.9h30c4.6 0 8.3-4.5 8.3-9.9v-6H26.7zM50 93.1c-1.8 0-3.3-1.5-3.3-3.3s1.5-3.3 3.3-3.3 3.3 1.5 3.3 3.3-1.5 3.3-3.3 3.3zM73.3 17.7v-5.5c0-5.5-3.7-9.9-8.3-9.9H35c-4.6 0-8.3 4.5-8.3 9.9v5.5h46.6zM41.7 8.5h16.7c.9 0 1.7.9 1.7 2s-.7 2-1.7 2H41.7c-.9 0-1.7-.9-1.7-2s.7-2 1.7-2z"/></svg>';

            case 'audit_report':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M85.6 70.7c0 8.2-6.7 14.9-14.9 14.9s-14.9-6.7-14.9-14.9 6.7-14.9 14.9-14.9c8.2.1 14.9 6.7 14.9 14.9z"/><path class="st1 line" d="M81.2 81.3L94.9 95"/><path class="st0 line" d="M51.8 87.2H5L4.9 5H48l19.5 19.6v21.5"/><path class="st1 line" d="M48 5v19.6h19.5M16.7 32.4v39.1h23.5M16.7 55.9l10.7-10.8 7.9 7.8L48 40.2"/></svg>';

            case 'benchmark_report':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M94.9 71.5c0 13-10.5 23.5-23.5 23.5C58.5 95 48 84.5 48 71.5S58.5 48 71.4 48c13 0 23.5 10.5 23.5 23.5z"/><path class="st1 line" d="M84.2 65.1L69.3 81.5 58.6 70.8"/><path class="st0 line" d="M46 87.2H4.9V5H48l19.5 19.6v17.6"/><path class="st1 line" d="M48 5v19.6h19.5M16.7 32.4v39.1h23.4M16.7 55.9l10.7-10.8 7.8 7.8L48 40.2"/></svg>';

            case 'brochure':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M34.9 95l-30-21.4V5l30 21.4L64.9 5l30 21.4V95l-30-21.4zM34.9 26.4V95M64.9 73.6V5.1"/></svg>';

            case 'business_intelligence':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M51.4 45.8h8.2v16.4h-8.2zM67.7 25.3h8.2v36.8h-8.2zM24 48.6c0 3.8 3.1 6.9 6.9 6.9s6.9-3.1 6.9-6.9c0-3.8-3.1-6.9-6.9-6.9S24 38.6 24 34.8c0-3.8 3.1-6.9 6.9-6.9s6.9 3.1 6.9 6.9M30.9 55.5v4.6M30.9 23.3v36.8"/><path class="st0 line" d="M82.7 19.2v49.1H17.2V19.2zM9 76.5V17.1c0-3.4 2.7-6.1 6.1-6.1h69.6c3.4 0 6.1 2.7 6.1 6.1v59.3"/><path class="st0 line" d="M58.2 76.5v4.1H41.8v-4.1H4.9v8.2c0 2.3 1.8 4.1 4.1 4.1h81.9c2.3 0 4.1-1.8 4.1-4.1v-8.2H58.2z"/></svg>';

            case 'calendar':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-miterlimit:10;}</style><path class="st0 line" d="M92 5H8c-3.2 0-5.9 2.6-5.9 5.9v78.2C2.1 92.3 4.7 95 8 95h84c3.2 0 5.9-2.6 5.9-5.9V10.9C97.9 7.7 95.3 5 92 5zM2.1 24.1h95.8M27.5 46.6s9.2-10.1 16.1-1.3c.7.8 4.6 9.1-5.6 17.8s-10.6 12-10.6 12h21.3M57.3 50l8.8-8.3h1v35"/></svg>';

            case 'call_accounting':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M32.3 42.2c3-3.1 3-8 0-11.1L24 22.9c-3-3.1-8-3.1-11.1 0l-4.5 4.6c-3.9 3.9-4.6 10.1-1.5 14.8 13.2 20 30.9 37.6 50.9 50.9 4.6 3.1 10.8 2.5 14.8-1.5l4.6-4.5c3.1-3.1 3.1-8 0-11.1l-8.3-8.3c-3.1-3.1-8-3.1-11.1 0L55 70.6C45.8 62.9 37 54.1 29.5 45l2.8-2.8z"/><path class="st1 line" d="M88 45.1c-9.2 9.2-24 9.2-33.2 0-9.2-9.2-9.2-24 0-33.2 9.2-9.2 24-9.2 33.2 0 9.2 9.1 9.2 24 0 33.2z"/><path class="st0 line" d="M65.1 34.9c0 3.5 2.9 6.4 6.4 6.4 3.5 0 6.4-2.9 6.4-6.4 0-3.5-2.9-6.4-6.4-6.4-3.5 0-6.4-2.9-6.4-6.4 0-3.5 2.9-6.4 6.4-6.4 3.5 0 6.4 2.9 6.4 6.4M71.4 11.8v33.3"/></svg>';

            case 'case_study':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;} .st2{fill:#000;}</style><path class="st0 line" d="M16.7 28.5h17.6M16.7 40.2H48M16.7 51.9h23.4M16.7 63.7h19.5"/><path class="st1 line" d="M94.9 71.5c0 13-10.5 23.5-23.5 23.5C58.5 95 48 84.5 48 71.5S58.5 48 71.4 48c13 0 23.5 10.5 23.5 23.5zM94.9 71.5c0 13-10.5 23.5-23.5 23.5C58.5 95 48 84.5 48 71.5S58.5 48 71.4 48c13 0 23.5 10.5 23.5 23.5zM46 87.2H4.9V5H48l19.5 19.6v17.6"/><path class="st0 line" d="M48 5v19.6h19.5"/><path class="st2 text" d="M64.2 62.1c-2.5 0-4.5.8-6 2.5s-2.2 4-2.2 6.9c0 3 .7 5.3 2.1 7s3.4 2.5 6 2.5c1.6 0 3.4-.3 5.5-.9v2.3c-1.6.6-3.6.9-5.9.9-3.4 0-6-1-7.8-3.1s-2.8-5-2.8-8.7c0-2.4.4-4.4 1.3-6.2.9-1.8 2.2-3.1 3.8-4.1 1.7-1 3.6-1.4 5.9-1.4 2.4 0 4.5.4 6.3 1.3l-1.1 2.3c-1.6-.9-3.3-1.3-5.1-1.3zM87.6 76.9c0 2-.7 3.6-2.2 4.7-1.5 1.1-3.5 1.7-6 1.7-2.7 0-4.8-.4-6.3-1.1v-2.6c.9.4 2 .7 3.1.9 1.1.2 2.2.3 3.3.3 1.8 0 3.1-.3 4-1 .9-.7 1.3-1.6 1.3-2.8 0-.8-.2-1.4-.5-2s-.9-1-1.6-1.4c-.7-.4-1.9-.9-3.4-1.5-2.1-.8-3.7-1.7-4.6-2.7-.9-1-1.4-2.4-1.4-4.1 0-1.8.7-3.2 2-4.2s3.1-1.6 5.3-1.6c2.3 0 4.4.4 6.3 1.3l-.8 2.3c-1.9-.8-3.7-1.2-5.5-1.2-1.4 0-2.5.3-3.3.9s-1.2 1.4-1.2 2.5c0 .8.1 1.4.4 2s.8 1 1.5 1.4c.7.4 1.8.9 3.2 1.4 2.4.9 4.1 1.8 5 2.8 1 1.1 1.4 2.4 1.4 4z"/></svg>';

            case 'case-study':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;} .st2{fill:#000;}</style><path class="st0 line" d="M16.7 28.5h17.6M16.7 40.2H48M16.7 51.9h23.4M16.7 63.7h19.5"/><path class="st1 line" d="M94.9 71.5c0 13-10.5 23.5-23.5 23.5C58.5 95 48 84.5 48 71.5S58.5 48 71.4 48c13 0 23.5 10.5 23.5 23.5zM94.9 71.5c0 13-10.5 23.5-23.5 23.5C58.5 95 48 84.5 48 71.5S58.5 48 71.4 48c13 0 23.5 10.5 23.5 23.5zM46 87.2H4.9V5H48l19.5 19.6v17.6"/><path class="st0 line" d="M48 5v19.6h19.5"/><path class="st2 text" d="M64.2 62.1c-2.5 0-4.5.8-6 2.5s-2.2 4-2.2 6.9c0 3 .7 5.3 2.1 7s3.4 2.5 6 2.5c1.6 0 3.4-.3 5.5-.9v2.3c-1.6.6-3.6.9-5.9.9-3.4 0-6-1-7.8-3.1s-2.8-5-2.8-8.7c0-2.4.4-4.4 1.3-6.2.9-1.8 2.2-3.1 3.8-4.1 1.7-1 3.6-1.4 5.9-1.4 2.4 0 4.5.4 6.3 1.3l-1.1 2.3c-1.6-.9-3.3-1.3-5.1-1.3zM87.6 76.9c0 2-.7 3.6-2.2 4.7-1.5 1.1-3.5 1.7-6 1.7-2.7 0-4.8-.4-6.3-1.1v-2.6c.9.4 2 .7 3.1.9 1.1.2 2.2.3 3.3.3 1.8 0 3.1-.3 4-1 .9-.7 1.3-1.6 1.3-2.8 0-.8-.2-1.4-.5-2s-.9-1-1.6-1.4c-.7-.4-1.9-.9-3.4-1.5-2.1-.8-3.7-1.7-4.6-2.7-.9-1-1.4-2.4-1.4-4.1 0-1.8.7-3.2 2-4.2s3.1-1.6 5.3-1.6c2.3 0 4.4.4 6.3 1.3l-.8 2.3c-1.9-.8-3.7-1.2-5.5-1.2-1.4 0-2.5.3-3.3.9s-1.2 1.4-1.2 2.5c0 .8.1 1.4.4 2s.8 1 1.5 1.4c.7.4 1.8.9 3.2 1.4 2.4.9 4.1 1.8 5 2.8 1 1.1 1.4 2.4 1.4 4z"/></svg>';

            case 'cost_allocation':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M69.5 30.5l12.2-12.1"/><path class="st0 line" d="M95 12.8c0 4.3-3.5 7.8-7.8 7.8s-7.8-3.5-7.8-7.8S82.9 5 87.2 5 95 8.5 95 12.8zM73.3 73.4l8.3 8.3"/><path class="st0 line" d="M87.2 95c-4.3 0-7.8-3.5-7.8-7.8s3.5-7.8 7.8-7.8 7.8 3.5 7.8 7.8-3.5 7.8-7.8 7.8zM30.4 30.5L18.3 18.4"/><path class="st0 line" d="M5 12.8c0 4.3 3.5 7.8 7.8 7.8s7.8-3.5 7.8-7.8S17.1 5 12.8 5 5 8.5 5 12.8zM26.7 73.4l-8.3 8.3"/><path class="st0 line" d="M12.8 95c4.3 0 7.8-3.5 7.8-7.8s-3.5-7.8-7.8-7.8S5 82.9 5 87.2c-.1 4.3 3.4 7.8 7.8 7.8zM67.6 48h11.7"/><path class="st0 line" d="M95 48c0 4.3-3.5 7.8-7.8 7.8s-7.8-3.5-7.8-7.8 3.5-7.8 7.8-7.8S95 43.7 95 48zM32.4 48H20.6"/><path class="st0 line" d="M5 48c0 4.3 3.5 7.8 7.8 7.8s7.8-3.5 7.8-7.8-3.5-7.8-7.8-7.8S5 43.7 5 48zM43.4 56.6c0 3.6 3 6.6 6.6 6.6 3.6 0 6.6-3 6.6-6.6 0-3.6-3-6.6-6.6-6.6-3.6 0-6.6-3-6.6-6.6 0-3.6 3-6.6 6.6-6.6 3.6 0 6.6 3 6.6 6.6M50 63.2v4.4M50 32.4v4.4"/></svg>';

            case 'expense_management':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-miterlimit:10;}</style><path class="st0 line" d="M97.7 54.3v-8.5L87 44c-.4-2.5-1.1-4.9-1.9-7.2l8.4-6.9-4.3-7.4L79 26.3c-1.6-1.9-3.4-3.7-5.3-5.3l3.8-10.2-7.4-4.3-6.9 8.4C61 14 58.5 13.4 56 13L54.3 2.3h-8.5L44 13c-2.5.4-4.9 1.1-7.2 1.9l-6.9-8.4-7.4 4.3L26.3 21c-1.9 1.6-3.7 3.4-5.3 5.3l-10.2-3.8-4.3 7.4 8.4 6.9C14 39 13.4 41.5 13 44L2.3 45.7v8.5L13 56c.4 2.5 1.1 4.9 1.9 7.2l-8.4 6.9 4.3 7.4L21 73.7c1.6 1.9 3.4 3.7 5.3 5.3l-3.8 10.2 7.4 4.3 6.9-8.4c2.2.9 4.7 1.5 7.2 1.9l1.8 10.7h8.5L56 87c2.5-.4 4.9-1.1 7.2-1.9l6.9 8.4 7.4-4.3L73.7 79c1.9-1.6 3.7-3.4 5.3-5.3l10.2 3.8 4.3-7.4-8.4-6.9C86 61 86.6 58.5 87 56l10.7-1.7zM50 77.4c-15.2 0-27.4-12.3-27.4-27.4 0-15.2 12.3-27.4 27.4-27.4 15.2 0 27.4 12.3 27.4 27.4 0 15.2-12.2 27.4-27.4 27.4zM56.5 42.3s.5-6.8-6.5-6.8-6.8 5.4-6.8 5.4-.6 6.2 7.2 8.3c7.7 2.1 7 8.8 7 8.8s-.2 6.7-7.3 6.7-7.4-5.9-7.4-5.9M50 30.2v5.3M50 64.7V70"/></svg>';

            case 'fact_sheet':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M16.7 28.5h17.6M16.7 40.2H48M16.7 52h23.5M16.7 63.7h19.5"/><path class="st1 line" d="M95 71.4c0 13-10.5 23.5-23.5 23.5S48 84.4 48 71.4s10.5-23.5 23.5-23.5S95 58.5 95 71.4z"/><path class="st0 line" d="M84.3 65L69.4 81.4 58.7 70.7"/><path class="st1 line" d="M46.1 87.2H4.9V5H48l19.6 19.6v17.6"/><path class="st0 line" d="M48 5v19.6h19.6"/></svg>';

            case 'infographic':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M30.4 51.9v34.8l-11.7 8.2L7 86.7V51.9"/><path class="st0 line" d="M30.4 51.9l-11.7 7.9L7 51.9l11.7-7.8zM18.7 59.8v35.1M61.6 32.4v54.3l-11.7 8.2-11.7-8.2V32.4"/><path class="st0 line" d="M61.6 32.4l-11.7 7.8-11.7-7.8 11.7-7.8zM49.9 40.2v54.7M92.9 12.9v73.8l-11.8 8.2-11.7-8.2V12.9"/><path class="st0 line" d="M92.9 12.9l-11.8 7.8-11.7-7.8 11.7-7.8zM81.1 20.7v74.2"/></svg>';

            case 'insight_analytics':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><circle class="st0 line" cx="39.8" cy="43.8" r="16.4"/><path class="st0 line" d="M31.7 29.5l8.1 14.3h16.4M39.8 43.8l2.7 16.1M64.5 27.4h12M64.5 35.6h12M64.5 43.8h12"/><path class="st0 line" d="M82.8 19.2v49.1H17.2V19.2zM9 76.5V17.1c0-3.4 2.8-6.1 6.1-6.1h69.6c3.4 0 6.1 2.8 6.1 6.1v59.4"/><path class="st0 line" d="M58.2 76.5v4.1H41.8v-4.1H4.9v8.2c0 2.3 1.8 4.1 4.1 4.1h82c2.3 0 4.1-1.8 4.1-4.1v-8.2H58.2z"/></svg>';

            case 'item':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M81.3 12.8v47H18.7v-47zM10.9 67.6V10.9c0-3.2 2.6-5.9 5.9-5.9h66.5c3.2 0 5.9 2.6 5.9 5.9v56.7"/><path class="st0 line" d="M57.8 67.6v3.9H42.2v-3.9H6.9v7.8c0 2.2 1.8 3.9 3.9 3.9H89c2.2 0 3.9-1.8 3.9-3.9v-7.8H57.8zM7 95h85.9M50 95V79.3"/><circle class="st0 line" cx="63.7" cy="26.5" r="5.9"/><circle class="st0 line" cx="63.7" cy="46.1" r="5.9"/><circle class="st0 line" cx="36.3" cy="36.3" r="5.9"/><path class="st1 line" d="M41.6 33.8L58 27.3M41.6 38.8L58 45.3"/></svg>';

            case 'managed':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:3;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:3;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st4{fill:none;stroke:#000;stroke-width:3;stroke-miterlimit:10;}</style><path class="st0 line" d="M49 91.3H4.5v-89h46.6l21.2 21.2v36"/><path class="st1 line" d="M51.1 2.3v21.2h21.2"/><path class="st4 line" d="M62.81 79.39l15.98-15.98 10.747 10.748-15.98 15.98zM58.4 84.8l-3.1 10.8c-.2.6 0 1.2.4 1.6.3.3.7.5 1.2.5.2 0 .3 0 .5-.1l10.8-3.1-9.8-9.7zM95.1 66.3l-8.4-8.4c-.7-.7-1.7-.7-2.4 0l-5.5 5.5 10.8 10.8 5.5-5.5c.6-.7.6-1.7 0-2.4zM19.4 35.4l6.3 6.3 10.5-10.5M40.7 39.7h21M40.7 56.7h21M19.4 52.5l6.3 6.3 10.5-10.5"/></svg>';

            case 'mms':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:#000;} .st3{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M48 59.8v27.4c0 4.3-3.5 7.8-7.8 7.8H12.8C8.5 95 5 91.5 5 87.2V28.5c0-4.3 3.5-7.8 7.8-7.8h11.7M24.5 36.3H4.9M48 79.3H4.9M20.6 28.5h3.9"/><path class="st0 line" d="M94.9 34.3c0 3.2-2.6 5.9-5.9 5.9H67.5L55.8 51.9V40.2H42.1c-3.2 0-5.9-2.6-5.9-5.9V10.9c0-3.2 2.6-5.9 5.9-5.9H89c3.2 0 5.9 2.6 5.9 5.9v23.4z"/><path class="st1 line" d="M67.5 18.7c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM79.3 18.7c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2c-.1-1.1-1-2-2-2zM55.9 18.7c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/><path class="st3 line" d="M28.4 87.2c0 1.1-.9 2-2 2s-2-.9-2-2 .9-2 2-2 2 .9 2 2z"/></svg>';

            case 'on-prem':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0 line{fill:none;stroke:#000;stroke-width:2;stroke-miterlimit:10;}</style><path class="st0 line" d="M91.9 81.4c3.5 0 6.3-2.8 6.3-6.3v-8.4c0-1.2-.9-2.1-2.1-2.1H94V10.2c0-4.6-3.8-8.4-8.4-8.4H14.4C9.8 1.8 6 5.6 6 10.2v54.4H3.9c-1.2 0-2.1.9-2.1 2.1v8.4c0 3.5 2.8 6.3 6.3 6.3h39.8V94H4c-1.2 0-2.1.9-2.1 2.1 0 1.2.9 2.1 2.1 2.1h91.9c1.2 0 2.1-.9 2.1-2.1 0-1.2-.9-2.1-2.1-2.1H52.1V81.4h39.8zM14.4 12.3c0-1.2.9-2.1 2.1-2.1h67c1.2 0 2.1.9 2.1 2.1v46.1c0 1.2-.9 2.1-2.1 2.1h-67c-1.2 0-2.1-.9-2.1-2.1V12.3zM6 75.1v-6.3h33.5v2.1c0 1.2.9 2.1 2.1 2.1h16.8c1.2 0 2.1-.9 2.1-2.1v-2.1H94v6.3c0 1.2-.9 2.1-2.1 2.1H8.1C7 77.2 6 76.3 6 75.1zM37.4 52.1h25.1c1.2 0 2.1-.9 2.1-2.1V33.2c0-1.2-.9-2.1-2.1-2.1H37.4c-1.2 0-2.1.9-2.1 2.1V50c0 1.2 1 2.1 2.1 2.1zM40.2 27.9c0-5.4 4.4-9.8 9.8-9.8s9.8 4.4 9.8 9.8v3.3H40.2v-3.3z"/></svg>';

            case 'play':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}</style><path class="st0 line" d="M36.2 73.5v-47L77.3 50zM42.3 5.6c30.8-5.1 57.1 21.2 52 52-3.1 18.6-18.1 33.7-36.7 36.7-30.8 5-57-21.2-52-52 3-18.5 18-33.6 36.7-36.7z"/></svg>';

            case 'rss':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M22.6 44.2c18.4 0 33.3 14.9 33.3 33.3M22.6 24.6c29.2 0 52.9 23.7 52.9 52.9"/><circle class="st0 line" cx="30.4" cy="69.6" r="5.9"/><path class="st0 line" d="M95 83.3C95 89.8 89.7 95 83.2 95H16.7C10.2 95 5 89.7 5 83.3V16.7C4.9 10.3 10.2 5 16.7 5h66.6C89.7 5 95 10.3 95 16.7v66.6z"/></svg>';

            case 'press_release':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M75.4 57.9V7H24.5v50.9M36.3 18.7h3.9M44.1 26.6h19.6M36.3 34.4h27.4M36.3 42.2h27.4M36.3 50.1h27.4"/><path class="st0 line" d="M95 93.1H5V65.7h29.4v3.9c0 4.3 3.5 7.8 7.8 7.8h17.6c4.3 0 7.8-3.5 7.8-7.8v-3.9H95v27.4zM4.9 65.7l13.7-23.5h5.9M75.4 42.2h5.9L95 65.7"/></svg>';

            case 'research':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M46.1 87.3H4.9V4.9h43.2l19.6 19.6v17.7"/><path class="st1 line" d="M48.1 4.9v19.6h19.6"/><path class="st1 line" d="M16.7 52h7.8v15.7h-7.8zM32.4 44.1h7.8v23.5h-7.8zM24.6 32.3h7.8v35.3h-7.8zM12.8 67.7h27.5"/><path class="st0 line" d="M85.8 70.8c0 8.2-6.7 14.9-14.9 14.9S56 79 56 70.8c0-8.3 6.7-14.9 14.9-14.9s14.9 6.7 14.9 14.9z"/><path class="st1 line" d="M81.4 81.4l13.8 13.7"/></svg>';

            case 'saas':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-miterlimit:10;}</style><path class="st0 line" d="M69.8 13.7c-4-7-11.6-11.4-19.8-11.4-11.2 0-20.6 8-22.3 18.5-7.3-.4-13.5 5.5-13.5 12.6 0 10 8.6 12.6 13.7 12.6h42.9c5.2-.7 15-4.9 15-16.2 0-8.7-7.1-15.8-16-16.1z"/><circle class="st0 line" cx="30.1" cy="63.9" r="2"/><path class="st0 line" d="M85.8 73.9V56c0-1.1-.9-2-2-2H16.2c-1.1 0-2 .9-2 2v17.9h71.6zm-15.9-14c2.2 0 4 1.8 4 4s-1.8 4-4 4-4-1.8-4-4 1.8-4 4-4zm-11.9 0c2.2 0 4 1.8 4 4s-1.8 4-4 4-4-1.8-4-4 1.8-4 4-4z"/><circle class="st0 line" cx="30.1" cy="87.8" r="2"/><path class="st0 line" d="M14.2 77.8v17.9c0 1.1.9 2 2 2h67.6c1.1 0 2-.9 2-2V77.8H14.2zm43.8 14c-2.2 0-4-1.8-4-4s1.8-4 4-4 4 1.8 4 4c-.1 2.2-1.9 4-4 4zm11.9 0c-2.2 0-4-1.8-4-4s1.8-4 4-4 4 1.8 4 4-1.8 4-4 4z"/></svg>';

            case 'service_support':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-miterlimit:10;}</style><path class="st0 line" d="M75.3 11.3c8.8 6.8 14.1 17.4 14.1 26.9 0 8.7-3.7 16.4-8.8 22.5-4.8 5.8-3.5 24.5 1.7 24.5 1.1 0 2.4 1.1 2.4 2.4v7.8c0 1.3-1.1 2.4-2.4 2.4H43.2c-1.3 0-2.4-1.1-2.4-2.4 0-1.4 9.8-8.3 3.3-14.6-2.6-2.2-19.3 2.4-19.3-3.5.2-1.1 0-2.8.3-2.9m36-69.2c-.4 0-4.8-.4-5.2-.4-13.1 0-24.4 7.5-29.9 18.5-1.8 3.6-2.3 6.1-2.3 10.9 0 2.3.6 4.4.6 6.2 0 2.1-1.5 4.2-1.5 4.4-.4.8-1.8 3.4-4.9 7.8-1.3 1.5-1 3.3 0 3.9 1.4.8 3.6.9 3.6 2 0 1.6-1.6 2.2-1.6 3.5 0 1.3 1.2 1.4 1.2 1.7 0 .4-.9.3-.9 1.5s1.4 1.5 1.7 1.7c0 0 .4 2.7.6 3.5"/><path class="st0 line" d="M8.6 74.7c0 2.7 2.2 4.8 4.8 4.8 2 0 3.8-1.3 4.5-3.1 12.8-2.3 28.2-10.5 35.8-21.6 1.4-2 4.5-1.8 6.6-1.8 6.5 0 11.8-5.3 11.8-11.8 0-4.4-2.4-8.2-5.9-10.2L76.9 8c.4-1.3-.3-2.6-1.6-2.9L65.1 2.2c-1.3-.3-2.5.4-2.8 1.7l-1.7 25.4h-.3c-6.5 0-11.8 5.3-11.8 11.8 0 2.1.6 4.1 1.6 5.9.6 1.5.9 3.3-.3 5C42.9 62.1 29 69.8 17.2 71.7c-.9-1.1-2.2-1.8-3.7-1.8-2.7 0-4.9 2.1-4.9 4.8zm47.1-33.6c0-2.5 2.1-4.6 4.6-4.6 2.5 0 4.6 2.1 4.6 4.6s-2.1 4.6-4.6 4.6c-2.5 0-4.6-2-4.6-4.6z"/></svg>';

            case 'share_service':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-miterlimit:10;}</style><path class="st0 line" d="M25.5 60.8c13-8.1 13.2-20.2 13.2-20.7M75.4 60.8c-13-8.1-13.2-20.2-13.2-20.7"/><path class="st0 line" d="M49.9 15.8c3.8 0 7-3.1 7-7 0-3.8-3.1-7-7-7-3.8 0-7 3.1-7 7 .1 3.9 3.2 7 7 7zM66 31.9c0-5.3-1.8-11.2-11.4-11.6l-4.8 4.5-4.7-4.5c-9.7.3-11.3 6.5-11.4 11.6v2.4H66v-2.4zM18.6 81.3c3.5 0 6.3-2.8 6.3-6.3s-2.8-6.3-6.3-6.3-6.3 2.8-6.3 6.3c-.1 3.4 2.8 6.3 6.3 6.3zM32.1 95.9c0-4.4-1.6-9.5-9.6-9.7l-4 3.8-3.9-3.8c-8.2.3-9.5 5.5-9.6 9.7v2h27.2v-2zM43.6 74.9c0 3.5 2.8 6.3 6.3 6.3s6.3-2.8 6.3-6.3-2.8-6.3-6.3-6.3c-3.4 0-6.3 2.9-6.3 6.3zM63.5 95.9c0-4.4-1.6-9.5-9.6-9.7l-4 3.8-4-3.8c-8.2.3-9.5 5.5-9.6 9.7v2h27.2v-2zM74.9 74.9c0 3.5 2.8 6.3 6.3 6.3s6.3-2.8 6.3-6.3-2.8-6.3-6.3-6.3-6.3 2.9-6.3 6.3zM81.2 89.9l-3.9-3.8c-8.2.3-9.5 5.5-9.6 9.7v2h27.2v-2c0-4.4-1.6-9.5-9.6-9.7l-4.1 3.8zM49.9 40.1v22.2"/></svg>';

            case 'tem':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M43.1 38.4c2.5-2.5 2.7-6.3.4-8.5l-6-6c-2.2-2.2-6-2-8.5.4L25.3 28c-3.2 3.2-3.9 8-1.7 11.4C33 54.2 45.8 67.1 60.7 76.5c3.4 2.2 8.2 1.5 11.4-1.7l3.7-3.7c2.5-2.5 2.7-6.3.4-8.5l-6-6c-2.2-2.2-6-2-8.5.4l-2.2 2.2C52.8 53.8 46.3 47.4 41 40.7l2.1-2.3zM73.4 73.4c2 2 6.1 21.5-23.4 21.5C25.2 94.9 5.1 74.8 5.1 50 5.1 25.2 25.2 5.1 50 5.1S94.9 25.2 94.9 50c0 9.4-2.9 18.2-7.9 25.4"/></svg>';

            case 'usage':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-miterlimit:10;}</style><path class="st0 line" d="M3.8 80.6c-1.5 1.2-1.5 3.4-1.5 4.9v6.6h24.2v-6.6c0-1.5.3-4.2.7-5.2.4-1 3.4-6.7 3.4-6.7H12.8c0-.1-7.3 5.7-9 7zM34.1 34.8L22.5 23.2 10.9 34.8c-1 1.1-.4 2 .5 2H14v30.7h17V36.8h2.6c.8 0 1.4-.9.5-2zM62.5 80.3l-3.4-6.7H41.4L38 80.3c-.7 1.5-.7 3.8-.7 5.2v10.9h25.8V85.5c.1-1.5 0-3.8-.6-5.2zM37.3 21h4.5v46.4h17V21h4.5c1.5 0 1.9-1.2.9-2.3L50.3 4.1 36.4 18.7c-1 1.1-.6 2.3.9 2.3zM78.1 67.4h8.5V32.3h2.6c.9 0 1.5-.9.5-2L78.1 18.7 66.5 30.3c-1 1.1-.4 2 .5 2h2.6v35.1h8.5zM74 85.5v6.6h24.2v-6.6c0-1.5-.3-3.8-1.5-4.9l-9.1-7.1H70l3.4 6.7c.7 1.4.6 3.8.6 5.3z"/></svg>';

            case 'video':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;}</style><circle class="st0 line" cx="69.5" cy="73.5" r="21.5"/><path class="st0 line" d="M46 55.9H8.9C6.7 55.9 5 54.1 5 52V9c-.1-2.2 1.7-4 3.9-4h50.9c2.2 0 3.9 1.8 3.9 3.9V42"/><path class="st1 line" d="M94.9 51.9L71.4 40.2V20.7L94.9 8.9z"/><path class="st0 line" d="M63.6 63.7v19.6l17.7-9.8z"/></svg>';

            case 'webinar':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M8.9 74.7V21.9c0-3.2 2.6-5.9 5.9-5.9h70.5c3.2 0 5.9 2.6 5.9 5.9v52.9"/><path class="st0 line" d="M59.8 74.7v3.9H40.2v-3.9H4.9v7.8c0 2.2 1.8 3.9 3.9 3.9H91c2.2 0 3.9-1.8 3.9-3.9v-7.8H59.8zM59.5 35.1c1.8 7.3-4.6 13.7-11.9 11.9-3.4-.8-6.2-3.6-7.1-7.1-1.8-7.3 4.6-13.7 11.9-11.9 3.4.9 6.2 3.7 7.1 7.1zM65.6 63H34.3c0-8.6 7-15.7 15.7-15.7s15.6 7 15.6 15.7z"/></svg>';

            case 'wem':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;}</style><path class="st0 line" d="M92.5 87.2c0 4.3-3.5 7.8-7.8 7.8H49.5c-4.3 0-7.8-3.5-7.8-7.8V12.8c0-4.3 3.5-7.8 7.8-7.8h35.2c4.3 0 7.8 3.5 7.8 7.8v74.4zM92.5 20.7H41.6M92.5 79.3H41.6M57.3 12.8h19.5"/><path class="st1 line" d="M69 87.2c0 1.1-.9 2-2 2s-2-.9-2-2 .9-2 2-2 2 .9 2 2zM38.8 83.2c-17.3-23.1-17.3-47.6 0-66.4M10.1 63.7h31.5M11.6 32.4h30M7 48h34.6"/><path class="st1 line" d="M41.6 16.8c-.5 0-.9-.1-1.4-.1-18.4 0-33.3 14.1-33.3 32.5s14.7 34 33.2 34c.5 0 1-.1 1.5-.1V16.8z"/></svg>';

            case 'whitepaper':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;} .st2{fill:#000;}</style><path class="st0 line" d="M16.7 28.5h17.6M16.7 40.3H48M16.7 52h23.5M16.7 63.8h19.6"/><path class="st1 line" d="M95 71.5C95 84.5 84.5 95 71.5 95 58.6 95 48 84.5 48 71.5S58.6 48 71.5 48C84.5 48 95 58.5 95 71.5zM46.1 87.3H4.9V5H48l19.6 19.6v17.6"/><path class="st0 line" d="M48 5v19.6h19.6"/><path class="st2 line" d="M70.9 81.6h-2.1l-3.6-12c-.2-.5-.4-1.2-.6-2-.2-.8-.3-1.3-.3-1.5-.2 1.1-.5 2.3-.9 3.5l-3.4 12h-2l-4.8-17.9h2.2l2.8 11c.4 1.5.7 2.9.9 4.2.2-1.5.5-2.9 1-4.4l3.2-10.9h2.2l3.4 11c.4 1.3.7 2.7 1 4.3.2-1.2.4-2.6.9-4.2l2.8-11h2.2l-4.9 17.9zM89.9 69c0 1.8-.6 3.2-1.9 4.2s-3 1.5-5.3 1.5h-2.1v7h-2.1V63.8h4.6c4.5 0 6.8 1.7 6.8 5.2zm-9.3 3.8h1.9c1.8 0 3.2-.3 4-.9.8-.6 1.2-1.5 1.2-2.9 0-1.2-.4-2.1-1.2-2.6-.8-.6-2-.9-3.6-.9h-2.3v7.3z"/></svg>';

            case 'white-paper':
                return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" enable-background="new 0 0 100 100"><style type="text/css">.st0{fill:none;stroke:#000;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;} .st1{fill:none;stroke:#000;stroke-width:2;stroke-linejoin:round;stroke-miterlimit:10;} .st2{fill:#000;}</style><path class="st0 line" d="M16.7 28.5h17.6M16.7 40.3H48M16.7 52h23.5M16.7 63.8h19.6"/><path class="st1 line" d="M95 71.5C95 84.5 84.5 95 71.5 95 58.6 95 48 84.5 48 71.5S58.6 48 71.5 48C84.5 48 95 58.5 95 71.5zM46.1 87.3H4.9V5H48l19.6 19.6v17.6"/><path class="st0 line" d="M48 5v19.6h19.6"/><path class="st2 line" d="M70.9 81.6h-2.1l-3.6-12c-.2-.5-.4-1.2-.6-2-.2-.8-.3-1.3-.3-1.5-.2 1.1-.5 2.3-.9 3.5l-3.4 12h-2l-4.8-17.9h2.2l2.8 11c.4 1.5.7 2.9.9 4.2.2-1.5.5-2.9 1-4.4l3.2-10.9h2.2l3.4 11c.4 1.3.7 2.7 1 4.3.2-1.2.4-2.6.9-4.2l2.8-11h2.2l-4.9 17.9zM89.9 69c0 1.8-.6 3.2-1.9 4.2s-3 1.5-5.3 1.5h-2.1v7h-2.1V63.8h4.6c4.5 0 6.8 1.7 6.8 5.2zm-9.3 3.8h1.9c1.8 0 3.2-.3 4-.9.8-.6 1.2-1.5 1.2-2.9 0-1.2-.4-2.1-1.2-2.6-.8-.6-2-.9-3.6-.9h-2.3v7.3z"/></svg>';
        }
    }

}
