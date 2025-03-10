<?php
function my_theme_archive_title( $title ) {
	if ( is_category() ) {
		$title = single_cat_title( '', false );
	} elseif ( is_tag() ) {
		$title = single_tag_title( '', false );
	} elseif ( is_author() ) {
		$title = '<span class="vcard">' . get_the_author() . '</span>';
	} elseif ( is_post_type_archive() ) {
		$title = post_type_archive_title( '', false );
	} elseif ( is_tax() ) {
		$title = single_term_title( '', false );
	}
 
	return $title;
}

add_filter( 'get_the_archive_title', 'my_theme_archive_title' );

//events list shortcode
add_shortcode( 'events_list_shortcode', 'display_events_list' );

function display_events_list($atts){
	$cat_name = get_the_archive_title();
	$obj = get_queried_object();
	$cat_slug = $obj->slug;
	$args = array(
		'post_type' => 'post',
		'post_status' => 'publish',
		'category_name' => $cat_slug,
		'orderby' => 'date',
		'order' => 'DESC',
		'posts_per_page' => 10
	);

	$string = '';
	$location_SVG = '<svg height="10px" width="10px" version="1.1" id="Capa_1" viewBox="0 0 297 297" xml:space="preserve">
										<path d="M148.5,0C87.43,0,37.747,49.703,37.747,110.797c0,91.026,99.729,179.905,103.976,183.645  c1.936,1.705,4.356,2.559,6.777,2.559c2.421,0,4.841-0.853,6.778-2.559c4.245-3.739,103.975-92.618,103.975-183.645  C259.253,49.703,209.57,0,148.5,0z M148.5,79.693c16.964,0,30.765,13.953,30.765,31.104c0,17.151-13.801,31.104-30.765,31.104  c-16.964,0-30.765-13.953-30.765-31.104C117.735,93.646,131.536,79.693,148.5,79.693z"></path>
									</svg>';
	$query = new WP_Query( $args );
	if( $query->have_posts() ){
		$string .= '<ul class="dates-list" data-page="1" data-category="' .$cat_name. '">';
		while( $query->have_posts() ){
			$query->the_post();
			$event_date = get_field('event_date_time');
			$string .= '<li class="ticket-date-row">
						<div class="ticket-des"><div class="ticket-title">'.get_the_title().'</div><div class="ticket-date-desc">' . substr(get_the_excerpt(), 0, 45) . '</div></div>
						<a href="'.get_the_permalink().'" class="ticket-link">View Tickets</a>
						</li>';
		}
		$string .= '</ul>';
	}
	
	if ( $query->max_num_pages > 1 ) {
		$string .= '<div class="gsb-show-more">
				<span class="gsb-textnode">Show More</span>
				<svg class="arrow-down gsb-arrow-down" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
					<path d="M233.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L256 338.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z"></path>
				</svg>
				<span class="gsb-loading"></span>
			</div>';
	}
	wp_reset_postdata();
	return $string;
}

//ajax posts pagination
add_action("wp_ajax_get_event_pages", "get_event_pages");
add_action("wp_ajax_nopriv_get_event_pages", "get_event_pages");
function get_event_pages() {
	
	$categoryName = $_REQUEST["dataCategory"];
	$dataListPage = $_REQUEST["dataListPage"];
	$a_args = array(
		'post_type' => 'post',
		'post_status' => 'publish',
		'category_name' => $categoryName,
		'orderby' => 'date',
		'order' => 'DESC',
		'posts_per_page' => 10,
		'paged' => $dataListPage
	);
	
	$string = '';
	$a_query = new WP_Query( $a_args );
	if( $a_query->have_posts() ){
		while( $a_query->have_posts() ){
			$a_query->the_post();
			$event_date = get_field('event_date_time');
			$string .= '<li class="ticket-date-row">
						<div class="ticket-des"><div class="ticket-title">'.get_the_title().'</div><div class="ticket-date-desc">' . substr(get_the_excerpt(), 0, 45) . '</div></div>
						<a href="'.get_the_permalink().'" class="ticket-link">View Tickets</a>
						</li>';
		}
		
		$big = 999999999;

		$pages = paginate_links( array(
		  'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
		  'format' => '?paged=%#%',
		  'current' => max( 1, get_query_var('paged') ),
		  'total' => $a_query->max_num_pages,


		) );
		$next_link = next_posts_link(__('Newer Entries &raquo;'));
		$result['nextPage'] = false;
		$result['dataCategory'] = $categoryName;
		$result['dataListPage'] = $dataListPage;
		if($pages){
			$result['nextPage'] = true;
		}
		$result['content'] = $string;
		wp_send_json_success(json_encode($result));
	}
	
	wp_reset_postdata();
	wp_die();  //die();
}

//lastest post grid
add_shortcode( 'lastposts_list_shortcode', 'display_lastest_posts' );
function display_lastest_posts($atts){
	$args = array(
		'post_type' => 'post',
		'post_status' => 'publish',
		'orderby' => 'post_date',
		'order' => 'DESC',
		'posts_per_page' => 16,
		'tax_query' => array(
			array(
				'taxonomy' => 'category',
				'field' => 'slug',
				'terms' => 'venues',
				'operator' => 'NOT IN'
			)
		)
	);
	
	$query = new WP_Query( $args );
	$string = '';
	if( $query->have_posts() ){
		$string .= '<ul class="categories-list">';
		while( $query->have_posts() ){
			$query->the_post();
			$featuredImg = '';
			$categories = get_the_category(get_the_ID());
			$postFeaturedImg = get_the_post_thumbnail();
			if($postFeaturedImg){
				$featuredImg = $postFeaturedImg;
			}else{
				$firstCat = $categories[0];
				$categoryImage = get_field('featured_image', $firstCat);
				if($categoryImage){
					$featuredImg = wp_get_attachment_image( $categoryImage, 'thumbnail' );
				}
			}
			$string .= '<li class="category-cell">
				<a href="' . esc_url( get_the_permalink() ) . '">
				<div class="category-cell-wrap">
					<div class="category-cell-image">' .$featuredImg.'</div>
					<div class="category-cell-content">
						<div class="category-cell-title">'.get_the_title().'</div>

					</div>
				</div>
				</a>
			</li>';
		}
		$string .= '</ul>';
	}
	
	return $string;
	
	wp_reset_postdata();
	wp_die();  //die();
}

//Venues post grid
add_shortcode( 'venuesposts_list_shortcode', 'display_venuesposts_posts' );
function display_venuesposts_posts($atts){
	$args = array(
		'post_type' => 'post',
		'post_status' => 'publish',
		'orderby' => 'post_date',
		'order' => 'DESC',
		'category_name' => 'venues',
		'posts_per_page' => 16
	);
	
	$query = new WP_Query( $args );
	$string = '';
	if( $query->have_posts() ){
		$string .= '<ul class="categories-list">';
		while( $query->have_posts() ){
			$query->the_post();
			$featuredImg = '';
			$categories = get_the_category(get_the_ID());
			$postFeaturedImg = get_the_post_thumbnail();
			if($postFeaturedImg){
				$featuredImg = $postFeaturedImg;
			}else{
				$firstCat = $categories[0];
				$categoryImage = get_field('featured_image', $firstCat);
				if($categoryImage){
					$featuredImg = wp_get_attachment_image( $categoryImage, 'thumbnail' );
				}
			}
			$string .= '<li class="category-cell">
				<a href="' . esc_url( get_the_permalink() ) . '">
				<div class="category-cell-wrap">
					<div class="category-cell-image">' .$featuredImg.'</div>
					<div class="category-cell-content">
						<div class="category-cell-title">'.get_the_title().'</div>

					</div>
				</div>
				</a>
			</li>';
		}
		$string .= '</ul>';
	}
	
	return $string;
	
	wp_reset_postdata();
	wp_die();  //die();
}
//category list shortcode
add_shortcode( 'categories_list_shortcode', 'display_categories_list' );
function display_categories_list($atts){
	$args = array(
		'post_type' => 'post',
		'post_status' => 'publish',
		'orderby' => 'date',
		'order' => 'DESC',
		'posts_per_page' => 5
	);
	$string = '';
	$categories = get_categories();
	$string .= '<ul class="categories-list">';
	foreach($categories as $category) {
		if($category->name != 'Venues'){
			$categoryImage = get_field('featured_image', $category);
			$image  = '';
			if($categoryImage){
				$image = wp_get_attachment_image( $categoryImage, 'thumbnail' );
			}
			//<div class="category-cell-des">'.$category->description.'</div>
			$string .= '<li class="category-cell">
							<a href="' . esc_url( get_category_link( $category->term_id ) ) . '">
							<div class="category-cell-wrap">
								<div class="category-cell-image">' .$image.'</div>
								<div class="category-cell-content">
									<div class="category-cell-title">'.$category->name.'</div>

								</div>
							</div>
							</a>
						</li>';
		}
	}
	$string .= '</ul>';
	return $string;
}

//post featured image shortcode
add_shortcode( 'post_featured_img_shortcode', 'post_featured_img' );
function post_featured_img($atts){
	global $post;
	$categories = get_the_category($post->ID);
	$featuredImg = '';
	$postFeaturedImg = get_the_post_thumbnail($post);
	if($postFeaturedImg){
		$featuredImg = $postFeaturedImg;
	}else{
		$firstCat = $categories[0];
		$categoryImage = get_field('featured_image', $firstCat);
		if($categoryImage){
			$featuredImg = wp_get_attachment_image( $categoryImage, 'thumbnail' );
		}
	}
	$string = '';	
	if($featuredImg != ''){
		$string .= '<div class="post-featured-image">' .$featuredImg.'</div>';
	}
	return $string;
}

// ajax call js in function.php
function load_more_js() {
	?>
	<script>
		jQuery(document).ready( function($) {
			$('.gsb-show-more').on('click', function(e){
				let dateList = $('.dates-list');
				let showMoreCta = $(this);
				let dataListPage = parseInt(dateList.attr('data-page'));
				let dataCategory = dateList.attr('data-category');
				showMoreCta.addClass('active');
				$.ajax({
				 type : "post",
				 dataType : "json",
				 url : "/wp-admin/admin-ajax.php",
				 data : {action: "get_event_pages", dataListPage: dataListPage + 1, dataCategory: dataCategory},
				 success: function(response) {
					showMoreCta.removeClass('active');
					if(response.success) {
					   let responseData = JSON.parse(response.data);
					   dateList.append(responseData.content);

						if(responseData.nextPage){
							dateList.attr('data-page', dataListPage + 1)
						}else{
							showMoreCta.hide();
						}
					}
				 },
				 error: function() {
                    showMoreCta.addClass('active');
					showMoreCta.hide();
                 }
			  })   
			})
		})
	</script>
		<?php
}
add_action('wp_footer', 'load_more_js', 100);
