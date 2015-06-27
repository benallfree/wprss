<?php

class BsCarousel
{
  function __construct() {
    add_shortcode('bs-carousel', [$this, 'carousel']);
    add_shortcode('bs-carousel-item', [$this, 'carousel_item']);
    $this->items = [];
  }
  
  function carousel($atts)
  {
    if(!$atts) $atts = [];
    $defaults = [
      'id'=>'id'.uniqid(),
    ];
    $atts = array_merge($defaults, $atts);
  
    ob_start();
    ?>  
    <div id="<?php echo($atts['id'])?>" class="carousel slide" data-ride="carousel">
      <!-- Indicators -->
      <ol class="carousel-indicators">
        <?php for($i=0;$i<count($this->items);$i++): ?>
          <li data-target="#<?php echo($atts['id'])?>" data-slide-to="<?php echo($i)?>" <?php if($i==0) echo('class="active"') ?>></li>
        <?php endfor; ?>
      </ol>
    
      <!-- Wrapper for slides -->
      <div class="carousel-inner" role="listbox">
        <?php for($i=0;$i<count($this->items);$i++): ?>
          <?php $item = $this->items[$i]; ?>
          <div class="item <?php if($i==0) echo("active")?>">
            <?php echo($item['content']) ?>
            <?php if($item['caption']): ?>
              <div class="carousel-caption">
                <?php echo($item['caption'])?>
              </div>
            <?php endif; ?>
          </div>
        <?php endfor; ?>
      </div>
    
      <!-- Controls -->
      <a class="left carousel-control" href="#<?php echo($atts['id'])?>" role="button" data-slide="prev">
        <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
        <span class="sr-only">Previous</span>
      </a>
      <a class="right carousel-control" href="#<?php echo($atts['id'])?>" role="button" data-slide="next">
        <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
        <span class="sr-only">Next</span>
      </a>
    </div>
    <?php
    
    return ob_get_clean();  
  }
  
  function carousel_item($atts, $content='')
  {
    $defaults = [
      'content'=>do_shortcode($content),
      'caption'=>null,
    ];
    $atts = array_merge($defaults, $atts);
    $atts['caption'] = str_replace('{', '[', $atts['caption']);
    $atts['caption'] = str_replace('}', ']', $atts['caption']);
    $atts['caption'] = do_shortcode($atts['caption']);
    $this->items[] = $atts;
  }
}

new BsCarousel();