<?php
  
class SliderFilter
{
  function __construct()
  {
    add_filter( 'wpv_filter_query', function($query_args, $view_settings, $view_id) {
      if($view_id!=4031) return $query_args;
      $args = [
      	'posts_per_page'   => 1000,
      	'offset'           => 0,
      	'post_type'        => 'publisher',
      	'meta_key'=>'wpcf-podcast-is-featured',
      	'meta_value'=>'1',
      ];
      $posts = get_posts( $args );
      $ids = array_map(function($e) { return $e->ID; }, $posts);
      if(!isset($query_args['meta_query'])) $query_args['meta_query']=[];
      $query_args['meta_query']= [
        'key'=>'_wpcf_belongs_publisher_id',
        'value'=>$ids,
    		'compare' => 'IN',
      ];
      return $query_args;
    }, 99, 3 );
  }
}
new SliderFilter();
