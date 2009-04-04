<?php
/*
Plugin Name: Pixel Sitemap
Plugin URI: http://seobucks.ru/pixel-sitemap-plugin/
Description: Adds a sidebar widget to easy customize and compact display links to all your posts.
Version: 1.0.0
Author: Seobucks
Author URI: http://seobucks.ru/
*/

if ( !defined('WP_CONTENT_DIR') )
        define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
define('PS_ABSPATH', WP_CONTENT_DIR.'/plugins/' . dirname(plugin_basename(__FILE__)) . '/');

	
//Get, create or clear cach 
function pixel_sitemap_cache(& $data, $method='get'){
	$file = PS_ABSPATH.'pixel-sitemap-cache';
	if($method=='get'){
		if(is_file($file)){
			$temp = file_get_contents($file);
			$data = unserialize($temp);
			return is_array($data) && count($data)>1?true:false;
		}
		return false;
	}elseif($method=='put'){
		if(!is_array($data) || count($data)<=1) return false;
		$temp = serialize($data);
		if(is_writable(PS_ABSPATH)){
			file_put_contents($file, $temp);
			return true;
		}
		return false;
	}elseif($method=='clear'){
		if(is_writable(PS_ABSPATH)){
			unlink($file);
			return true;
		}			
	}
}

function pixel_sitemap_cache_clear(){
	$a = array();
	pixel_sitemap_cache($a,'clear');
}


function pixel_sitemap_css(){
?>
<!-- pixel sitemap 1.0.0 -->
<link rel="stylesheet" href="<?php echo get_option('siteurl'); ?>/wp-content/plugins/pixel-sitemap/pixel-sitemap-style.css" type="text/css" media="screen" />
<!-- /pixel sitemap -->
<?
}

// Put functions into one big function we'll call at the plugins_loaded
// action. This ensures that all required plugin functions are defined.
function pixel_sitemap_init() {
	// Check for the required plugin functions. This will prevent fatal
	// errors occurring when you deactivate the dynamic-sidebar plugin.
	if ( !function_exists('register_sidebar_widget') ) {
		return;
    }

	
	// This is the function that outputs pixel sitemap.
	function pixel_sitemap($args) {
		// $args is an array of strings that help widgets to conform to
		// the active theme: before_widget, before_title, after_widget,
		// and after_title are the array keys. Default tags: li and h2.
		extract($args);
		// Each widget can store its own options. We keep strings here.
		$options = get_option('pixel_sitemap');
		$title = $options['title'];
	    
       $ps_auto	= $options['ps_auto'];
		$ps_count	= !empty($options['ps_count'])?$options['ps_count']:0;
		$ps_sort	= !empty($options['ps_sort'])?$options['ps_sort']:'rand';

		// These lines generate our output. Widgets can be very complex
		// but as you can see here, they can also be very, very simple.
		echo $before_widget;
		if(!empty($title)) {
			echo $before_title . $title . $after_title;  
		}
		
		$ps_posts = array();
		
		if($ps_sort!='rand')
			$sort = $ps_sort=='old'?'asc':'desc';
		else{
			$sort = 'asc';
		}
		
		if(!pixel_sitemap_cache($ps_posts,'get')){
			$ps_posts = array();
			$my_query = new WP_Query('&post_status=publish&orderby=date&order='.$sort.'&posts_per_page='.($ps_count==0?10000:$ps_count));
			$k=0;
			while ($my_query->have_posts()) : $my_query->the_post();
				$ps_posts[$k]['permalink'] = get_permalink($post->ID);
				$ps_posts[$k]['title'] = get_the_title($post->ID);
				$k++;
			endwhile;
			pixel_sitemap_cache($ps_posts,'put');
		}
	
		if($ps_sort=='rand')
			shuffle($ps_posts);
		?>
				
		<div class="pixel-sitemap">
		<?
		foreach($ps_posts as $v){
			?><a class="pixel-sitemap-link" href="<?php echo $v['permalink']?>" rel="bookmark" title="<?php echo addslashes($v['title']); ?>" ><img class="pixel-sitemap-px" src="<?php echo get_option('siteurl'); ?>/wp-content/plugins/pixel-sitemap/px.gif" alt="<?php echo addslashes($v['title']); ?>" /></a><?
		}
		?>
		</div>
		<br style="clear:both;">
		<?
		unset($ps_posts);

		echo $after_widget;
	}
	
	// This is the function that outputs the form to let the users edit
	// the widget's settings. It's an optional feature that users cry for.
	function pixel_sitemap_control() {
		// Get our options and see if we're handling a form submission.

		$options = get_option('pixel_sitemap');
		if ( !is_array($options) )
			$options = array('title'=>'Pixel Sitemap',
					'ps_count' => '0',
					'ps_sort' => 'rand');

		if ( $_POST['pixel_sitemap-submit'] ) {
			// Remember to sanitize and format use input appropriately.
			$options['title'] = strip_tags(stripslashes($_POST['title']));			
			$options['ps_count'] = strip_tags(stripslashes($_POST['ps_count']));
			$options['ps_count'] = intval($options['ps_count']);
			$options['ps_count'] = !empty($options['ps_count'])?$options['ps_count']:0;
			$options['ps_sort'] = strip_tags(stripslashes($_POST['ps_sort']));		
			update_option('pixel_sitemap', $options);
			
			pixel_sitemap_cache_clear();
		}
		// Be sure you format your options to be valid HTML attributes.
		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		$ps_count = htmlspecialchars($options['ps_count'], ENT_QUOTES);
		$ps_sort = htmlspecialchars($options['ps_sort'], ENT_QUOTES);		
		// Here is our little form segment. Notice that we don't need a
		// complete form. This will be embedded into the existing form.
?>
<div>
 
    <p><label for="title"><?php echo __('Title text (optional)','pixel-sitemap'); ?></label><br />
    <input type="text" name="title" id="title" value="<?php echo $title; ?>" class="widefat" /></p>
	
    <p><label for="ps_count"><?php echo __('Count posts in map','pixel-sitemap'); ?></label><br />
    <input type="text" name="ps_count" id="ps_count" value="<?php echo $ps_count; ?>" class="widefat" />
	<small><?php echo __('Show all posts if 0','pixel-sitemap'); ?></small>
	</p>
	
    <p><label for="ps_sort"><?php echo __('Sorting','pixel-sitemap');?></label><br />
    <select name="ps_sort" id="ps_sort" class="widefat">
		<option value="rand" <?php if($ps_sort=='rand') echo 'selected="selected"'?>><?php echo __('Random','pixel-sitemap'); ?></option>
		<option value="old" <?php if($ps_sort=='old') echo 'selected="selected"'?>><?php echo __('First old posts','pixel-sitemap'); ?></option>
		<option value="new" <?php if($ps_sort=='new') echo 'selected="selected"'?>><?php echo __('First new posts','pixel-sitemap'); ?></option>
	</select>
	</p>

    <input type="hidden" id="pixel_sitemap-submit" name="pixel_sitemap-submit" value="1" />
  
</div>

<?php
}

	// This registers our widget so it appears with the other available
	// widgets and can be dragged and dropped into any active sidebars.
	register_sidebar_widget('Pixel Sitemap', 'pixel_sitemap');
	// This registers our optional widget control form. Because of this
	// our widget will have a button that reveals a 300x100 pixel form.
	register_widget_control('Pixel Sitemap', 'pixel_sitemap_control', 250, 470);
}
load_plugin_textdomain('pixel-sitemap','wp-content/plugins/pixel-sitemap','pixel-sitemap');
// Run our code later in case this loads prior to any required plugins.
add_action('plugins_loaded', 'pixel_sitemap_init');
//Clear cach if new post published
add_action('publish_post', 'pixel_sitemap_cache_clear');
//Add css file to head section
add_action('wp_head', 'pixel_sitemap_css');

?>