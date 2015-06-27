<?php
  
use Carbon\Carbon;

class PublisherUpdater
{
  function __construct()
  {
    if(!isset($_GET['eapn']) || !$_GET['eapn']) return;
    
    add_action('init', [$this, 'udpate_publishers']);
  }


  function v()
  {
    $args = func_get_args();
    $target = array_shift($args);
    $xml = array_shift($args);
    foreach($args as $arg)
    {
      $parts = explode(':',$arg);
      if(count($parts)==1)
      {
        $v = $xml->$parts[0]->__toString();
      }
      if($v) break;
    }
    if($v)
    {
      $target = $v;
    }
  }

  function update_episodes($parent_id, $rss)
  {
    foreach($rss->channel->item as $item)
    {
      if(!$item->enclosure) continue;
      $items[] = [
        'title'=>$item->title->__toString(),
        'pubDate'=>new Carbon($item->pubDate->__toString()),
        'guid'=>$item->guid->__toString(),
        'link'=>$item->link->__toString(),
        'description'=>$item->description->__toString(),
        'enclosure'=>[
          'length'=>$item->enclosure->attributes()['length']->__toString(),
          'type'=>$item->enclosure->attributes()['type']->__toString(),
          'url'=>$item->enclosure->attributes()['url']->__toString(),
        ],
        'itunes'=>[
          'image'=>$item->children('itunes', true)->image->attributes()['href']->__toString(),
          'duration'=>$item->children('itunes', true)->duration->__toString(),
          'explicit'=>$item->children('itunes', true)->explicit->__toString(),
          'keywords'=>$item->children('itunes', true)->keywords->__toString(),
          'subtitle'=>$item->children('itunes', true)->subtitle->__toString(),
        ]
        
      ];
    }
    
    usort($items, function($a, $b) {
      if($a['pubDate']==$b['pubDate']) return 0;
      if($a['pubDate'] < $b['pubDate']) return 1;
      return -1;
    });
    
    array_walk_recursive($items, function(&$value) {
      return;
      if(is_object($value)) return;
      $v = $value;
  //    $v = preg_replace ('/<\s*\\/a\s*>/', ' ', $value); 
      $v = preg_replace ('/<[^>]*>/', ' ', $value); 
      $v = preg_replace("/\r/", "", $v);
      $v = preg_replace("/\t/", " ", $v);
      $v = preg_replace("/\\s*\n+\\s*/", "\n", $v);
  //    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', ' ', $v);
      $v = preg_replace('/\s+/', ' ', $v);
      $v = preg_replace("/\n/", "\n\n", $v);
      $v = trim($v);
      $value = $v;
    });
    
    $insert_count = 0;
    $update_count = 0;
    foreach($items as $item)
    {
      $args = array(
      	'posts_per_page'   => 1000,
      	'offset'           => 0,
      	'post_type'        => 'episode',
      	'post_parent'=>$parent_id,
      	'meta_key'=>'wpcf-episode-guid',
      	'meta_value'=>$item['guid'],
      );
      $posts = get_posts( $args );
      if(count($posts)>0)
      {
        $post = $posts[0];
        $post->post_title = $item['title'];
        $post->post_content = $item['description'];
        wp_update_post($post);
        $update_count++;
      } else {
        $post_id = wp_insert_post( [
          'post_title'=>$item['title'],
          'post_description'=>$item['description'],
          'post_status'=>'publish',
          'post_type'=>'episode',
          'post_parent'=>$parent_id,
        ], $err);
        $post = get_post($post_id);
        $insert_count++;
      }
      
      /* GUID */
      update_post_meta($post->ID, '_wpcf_belongs_publisher_id', $parent_id);
      update_post_meta($post->ID, 'wpcf-episode-guid', $item['guid']);
      update_post_meta($post->ID, 'wpcf-episode-publish-date', $item['pubDate']->format('U'));
      update_post_meta($post->ID, 'wpcf-episode-mp3-url', $item['enclosure']['url']);
      update_post_meta($post->ID, 'wpcf-episode-duration', $item['itunes']['duration']);
      update_post_meta($post->ID, 'wpcf-episode-url', $item['link']);
      update_post_meta($post->ID, 'wpcf-episode-summary', $item['itunes']['subtitle']);
      update_post_meta($post->ID, 'wpcf-episode-image', $item['itunes']['image']);
    }
    echo("{$insert_count} inserted, {$update_count} updated\n");
    update_post_meta($parent_id, 'wpcf-episode_count', $insert_count+$update_count);
  }

  function udpate_publishers()
  {
    libxml_use_internal_errors(true);
    $args = array(
    	'posts_per_page'   => 1000,
    	'offset'           => 0,
    	'post_type'        => 'publisher',
    );
    $posts = get_posts( $args );    

    foreach($posts as $post)
    {
      $rss_url = get_post_meta($post->ID, 'wpcf-podcast-rss-url', true);
      
      echo($rss_url."\n");
      
      $xml = file_get_contents($rss_url);
      
      try { 
        $rss = new SimpleXmlElement($xml); 
      } catch(Exception $e) {
        echo("Error\n");
        continue;
      }
      $err = libxml_get_errors();
      if(count($err)>0)
      {
        echo("Error\n");
        continue;
      }
      
      /* Title */
      $title = $rss->channel->title->__toString();
      if($title)
      {
        $post->post_title = $title;
      }
      
      /* Description */
      $content = $rss->channel->children('itunes', true)->summary->__toString();
      if(!$content)
      {
        $content = $rss->channel->description->__toString();
      }
      if($content)
      {
        $post->post_content = $rss->channel->description->__toString();
      }
      
      wp_update_post($post);
      
      /* Logo */
      $logo = $rss->channel->children('itunes', true)->image->__toString();
      if(!$logo)
      {
        $logo = $rss->channel->image->url->__toString();
      }
      if($logo)
      {
        update_post_meta($post->ID, 'wpcf-podcast-logo', $logo);
      }
      
      /* Tagline */
      $v = $rss->channel->children('itunes', true)->subtitle->__toString();
      if($v)
      {
        update_post_meta($post->ID, 'wpcf-tagline', $v);
      }
      
      /* Author */
      $v = $rss->channel->children('itunes', true)->author->__toString();
      if($v)
      {
        update_post_meta($post->ID, 'wpcf-author', $v);
      }
      
      /* RSS Redirect */
      $v = $rss->channel->children('itunes', true)->{"new-feed-url"}->__toString();
      if($v)
      {
        update_post_meta($post->ID, 'wpcf-podcast-rss-url', $v);
      }
      
      /* Website URL */
      $v = $rss->channel->link->__toString();
      if($v)
      {
        update_post_meta($post->ID, 'wpcf-web-site-url', $v);
      }
      
     
      $this->update_episodes($post->ID, $rss);
    }
  }
}

new PublisherUpdater();