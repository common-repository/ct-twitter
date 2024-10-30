<?php
/*
Plugin Name: CT Twitter Widget
Plugin URI: http://contemporealestatethemes.com
Description: A simple twitter widget
Version: 1.0.0
Author: Chris Robinson
Author URI: http://contemporealestatethemes.com
*/

/*-----------------------------------------------------------------------------------*/
/* Include CSS */
/*-----------------------------------------------------------------------------------*/
 
function ct_twitter_css() {		
	wp_register_style( 'ct_twitter_css', plugins_url( 'assets/style.css', __FILE__ ), false, '1.0' );
	wp_enqueue_style( 'ct_twitter_css' );
}
add_action( 'wp_print_styles', 'ct_twitter_css' );

/*-----------------------------------------------------------------------------------*/
/* Register Widget */
/*-----------------------------------------------------------------------------------*/

class Twitter {
  public $tweets = array();
  public function __construct($user, $limit = 5) {
	$user = str_replace(' OR ', '%20OR%20', $user);
	$feed = curl_init('http://search.twitter.com/search.atom?q=from:'. $user .'&rpp='. $limit);
	curl_setopt($feed, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($feed, CURLOPT_HEADER, 0);
	$xml = curl_exec($feed);
	curl_close($feed);
	$result = new SimpleXMLElement($xml);
	
	foreach($result->entry as $entry) {
		$tweet = new stdClass();
		$tweet->id = (string) $entry->id;
		$user = explode(' ', $entry->author->name);
		$tweet->user = (string) $user[0];
		$tweet->author = (string) substr($entry->author->name, strlen($user[0])+2, -1);
		$tweet->title = (string) $entry->title;
		$tweet->content = (string) $entry->content;
		$tweet->updated = (int) strtotime($entry->updated);
		$tweet->permalink = (string) $entry->link[0]->attributes()->href;
		$tweet->avatar = (string) $entry->link[1]->attributes()->href;
		array_push($this->tweets, $tweet);
	}
	unset($feed, $xml, $result, $tweet);
  }
  public function getTweets() { return $this->tweets; }
}

class ct_Twitter extends WP_Widget {

	function ct_Twitter() {
	   $widget_ops = array('description' => 'Add your Twitter feed to your sidebar with this widget.' );
	   parent::WP_Widget(false, __('CT Twitter', 'contempo'),$widget_ops);      
	}
	
	function ct_TwitterFeed($user, $limit) {
		
		$feed = new Twitter($user, $limit);
		$tweets = $feed->getTweets();
		
		echo '<ul>';
		foreach($tweets as $tweet) {
		  echo "<li>". $tweet->content . __('by', 'contempo') . " <a href='http://twitter.com/". $tweet->user ."'>". $tweet->author ."</a></li>";
		}
		echo "</ul>";   
	}
   
	function widget($args, $instance) {  
		
		extract( $args );
		
		$title = $instance['title'];
		$limit = $instance['limit']; if (!$limit) $limit = 5;
		$user = $instance['user'];
		$unique_id = $args['widget_id'];
		
		echo $before_widget;
		echo $before_title;
			if (!$title) {
				_e('Twitter','contempo');
			} else {
				echo $title;
			}
			echo $after_title;
			echo $this->ct_TwitterFeed($user, $limit);	
		echo '<p class="right"><em><a class="read-more" href="http://twitter.com/' . $user . '">' . __('Follow', 'contempo') . '@' . $user . ' <em>&rarr;</em></a></em></p>';
		echo $args['after_widget'];
	}

	function update($new_instance, $old_instance) {                
	   return $new_instance;
	}
	
	function form($instance) {        
	
	   $title = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
	   $limit = isset( $instance['title'] ) ? esc_attr( $instance['limit'] ) : '';
	   $user = isset( $instance['user'] ) ? esc_attr( $instance['user'] ) : '';
	   
	   ?>
	   <p>
		   <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title (optional):','contempo'); ?></label>
		   <input type="text" name="<?php echo $this->get_field_name('title'); ?>"  value="<?php echo $title; ?>" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" />
	   </p>
	   <p>
		   <label for="<?php echo $this->get_field_id('user'); ?>"><?php _e('Username:','contempo'); ?></label>
		   <input type="text" name="<?php echo $this->get_field_name('user'); ?>"  value="<?php echo $user; ?>" class="widefat" id="<?php echo $this->get_field_id('user'); ?>" />
	   </p>
	   <p>
		   <label for="<?php echo $this->get_field_id('limit'); ?>"><?php _e('Limit:','contempo'); ?></label>
		   <input type="text" name="<?php echo $this->get_field_name('limit'); ?>"  value="<?php echo $limit; ?>" class="" size="3" id="<?php echo $this->get_field_id('limit'); ?>" />
	
	   </p>
	  <?php
	}
   
} 

add_action( 'widgets_init', create_function( '', 'register_widget("ct_Twitter");' ) );

?>