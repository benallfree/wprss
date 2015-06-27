<?php

//
class PublisherViewFilter
{
  function __construct()
  {
    add_filter( 'wpv_filter_query', function($query_args, $view_settings, $view_id) {
      if($view_id!=35) return $query_args;

      if(!isset($query_args['meta_query'])) $query_args['meta_query']=[];

      $query_args['meta_query'][] = [
        'key'=>'wpcf-podcast-is-featured',
        'value'=>[0,1],
    		'compare' => 'IN',
      ];

      $query_args['meta_query'][] = [
        'key'=>'wpcf-episode_count',
        'value'=>-1,
    		'compare' => '>',
      ];
      
      add_filter('posts_orderby', [$this, 'orderby']);
      
      return $query_args;
    }, 99, 3);
    
    add_filter('wpv_filter_query_post_process', function($query, $view_settings, $view_id) {
      if($view_id!=35) return $query;
      
      remove_filter('posts_orderby', [$this, 'orderby']);
      return $query;
    }, 99, 3);
  }
  
  function orderby($orderby)
  {
    return "abs(wp_7qpvry_postmeta.meta_value) DESC, abs(mt1.meta_value) DESC ";
  }
}

new PublisherViewFilter();