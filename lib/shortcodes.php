<?php
use Carbon\Carbon;

  
add_shortcode('post-parent-title', function($atts) {
  $parent_id = get_post_meta(get_the_ID(), '_wpcf_belongs_publisher_id', true);
  $post = get_post($parent_id);
  return $post->post_title;
});

add_shortcode('post-parent-url', function($atts) {
  $parent_id = get_post_meta(get_the_ID(), '_wpcf_belongs_publisher_id', true);
  return get_permalink($parent_id);
});

add_shortcode('child-count', function($atts) {
  $args = array(
    'posts_per_page' => 5,
    'post_type' => 'episode',
    'meta_query' => array(array('key' => '_wpcf_belongs_publisher_id', 'value' => get_the_ID()))
  );
  $myquery = new WP_Query($args);
  
  return $myquery->found_posts;
});

add_shortcode('ago', function($atts) {
  $dt = get_post_meta(get_the_ID(), 'wpcf-episode-publish-date', true);
  $dt = Carbon::createFromTimeStampUTC($dt);
  return $dt->toFormattedDateString();
});
