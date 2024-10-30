<?php

/**
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 */
class Hemmy_Core_Widget {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function widget_enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Hemmy_Core_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Hemmy_Core_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, HEMMY_CORE_URL . 'assets/js/hemmy-core.js', array( 'jquery' ), $this->version, true );
		wp_localize_script( 'jquery', 'hemmy_ajax_var', array(
			'admin_ajax_url' => esc_url( admin_url('admin-ajax.php') ),
			'mc_nounce' => wp_create_nonce('hemmy-mailchimp'),
			'must_fill' => esc_html__('Enter the email address', 'hemmy-core'),
		));

	}
	
	public function hemmy_widget_register(){
		
		register_widget( 'Hemmy_Twitter_Widget' );
		register_widget( 'Hemmy_Mailchimp_Widget' );
		register_widget( 'Hemmy_Instagram_Widget' );
	}
	
}

if( ! function_exists('hemmy_scrape_instagram') ):

	function hemmy_scrape_instagram( $username ) {
		$username = trim( strtolower( $username ) );

		switch ( substr( $username, 0, 1 ) ) {
			case '#':
				$url              = 'https://instagram.com/explore/tags/' . str_replace( '#', '', $username );
				$transient_prefix = 'h';
				break;

			default:
				$url              = 'https://instagram.com/' . str_replace( '@', '', $username );
				$transient_prefix = 'u';
				break;
		}

		if ( false === ( $instagram = get_transient( 'insta-a10-' . $transient_prefix . '-' . sanitize_title_with_dashes( $username ) ) ) ) {

			$remote = wp_remote_get( $url );

			if ( is_wp_error( $remote ) ) {
				return new WP_Error( 'site_down', esc_html__( 'Unable to communicate with Instagram.', 'hemmy-core' ) );
			}

			if ( 200 !== wp_remote_retrieve_response_code( $remote ) ) {
				return new WP_Error( 'invalid_response', esc_html__( 'Instagram did not return a 200.', 'hemmy-core' ) );
			}

			$shards      = explode( 'window._sharedData = ', $remote['body'] );
			$insta_json  = explode( ';</script>', $shards[1] );
			$insta_array = json_decode( $insta_json[0], true );

			if ( ! $insta_array ) {
				return new WP_Error( 'bad_json', esc_html__( 'Instagram has returned invalid data.', 'hemmy-core' ) );
			}

			if ( isset( $insta_array['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'] ) ) {
				$images = $insta_array['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'];
			} elseif ( isset( $insta_array['entry_data']['TagPage'][0]['graphql']['hashtag']['edge_hashtag_to_media']['edges'] ) ) {
				$images = $insta_array['entry_data']['TagPage'][0]['graphql']['hashtag']['edge_hashtag_to_media']['edges'];
			} else {
				return new WP_Error( 'bad_json_2', esc_html__( 'Instagram has returned invalid data.', 'hemmy-core' ) );
			}

			if ( ! is_array( $images ) ) {
				return new WP_Error( 'bad_array', esc_html__( 'Instagram has returned invalid data.', 'hemmy-core' ) );
			}

			$instagram = array();

			foreach ( $images as $image ) {
				if ( true === $image['node']['is_video'] ) {
					$type = 'video';
				} else {
					$type = 'image';
				}

				$caption = __( 'Instagram Image', 'hemmy' );
				if ( ! empty( $image['node']['edge_media_to_caption']['edges'][0]['node']['text'] ) ) {
					$caption = wp_kses( $image['node']['edge_media_to_caption']['edges'][0]['node']['text'], array() );
				}

				$instagram[] = array(
					'description' => $caption,
					'link'        => trailingslashit( '//instagram.com/p/' . $image['node']['shortcode'] ),
					'time'        => $image['node']['taken_at_timestamp'],
					'comments'    => $image['node']['edge_media_to_comment']['count'],
					'likes'       => $image['node']['edge_liked_by']['count'],
					'thumbnail'   => preg_replace( '/^https?\:/i', '', $image['node']['thumbnail_resources'][0]['src'] ),
					'small'       => preg_replace( '/^https?\:/i', '', $image['node']['thumbnail_resources'][2]['src'] ),
					'large'       => preg_replace( '/^https?\:/i', '', $image['node']['thumbnail_resources'][4]['src'] ),
					'original'    => preg_replace( '/^https?\:/i', '', $image['node']['display_url'] ),
					'type'        => $type,
				);
			} // End foreach().

			// do not set an empty transient - should help catch private or empty accounts.
			if ( ! empty( $instagram ) ) {
				$instagram = base64_encode( serialize( $instagram ) );
				set_transient( 'insta-a10-' . $transient_prefix . '-' . sanitize_title_with_dashes( $username ), $instagram, apply_filters( 'null_instagram_cache_time', HOUR_IN_SECONDS * 2 ) );
			}
		}

		if ( ! empty( $instagram ) ) {

			return unserialize( base64_decode( $instagram ) );

		} else {

			return new WP_Error( 'no_images', esc_html__( 'Instagram did not return any images.', 'hemmy-core' ) );

		}
	}
endif;

if( ! function_exists('hemmy_images_only') ):
	
	function hemmy_images_only( $media_item ) {
		if ( $media_item['type'] == 'image' )
			return true;
		return false;
	}

endif;

if( !function_exists( 'hemmy_get_tweets' ) ) {
	
	function hemmy_get_tweets( $consumer_key, $consumer_secret, $access_token, $access_token_secret, $tweet_count, $twitter_id ){
		
		if( class_exists('TwitterOAuth') ){
			require_once HEMMY_CORE_DIR . 'twitter/twitteroauth.php';
		}
		//set transient name
		$transient_name = 'hemmy_list_tweets_' . strtolower($twitter_id);
		$tweets = '';
		// Get stored transients
		$cached_twitter_feeds = get_transient( $transient_name );
		if( ( false === $cached_twitter_feeds || empty( $cached_twitter_feeds ) ) || $tweet_count > count( $cached_twitter_feeds ) ) {
		
			// Get Access Token
			$connection = Hemmy_GetConnectionWithAccessToken($consumer_key, $consumer_secret, $access_token, $access_token_secret);				
			$params = array(
			  'count' 		=> $tweet_count,
			  'screen_name' => $twitter_id
			);
			$url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
			// Get Response data
			$tweets = $connection->get($url, $params);
			// Set it to transient
			set_transient( $transient_name, $tweets, HOUR_IN_SECONDS * 8 ); //
			
			} else {
				$tweets = $cached_twitter_feeds;
			}
		
		return $tweets;
	}
}

function Hemmy_GetConnectionWithAccessToken($cons_key, $cons_secret, $oauth_token, $oauth_token_secret) 
{
	$connection = new TwitterOAuth($cons_key, $cons_secret, $oauth_token, $oauth_token_secret);
	return $connection;
}

function hemmy_tweet_time_ago( $time ) {
	$periods = array( esc_html__( 'second', 'hemmy-core' ), esc_html__( 'minute', 'hemmy-core' ), esc_html__( 'hour', 'hemmy-core' ), esc_html__( 'day', 'hemmy-core' ), esc_html__( 'week', 'hemmy-core' ), esc_html__( 'month', 'hemmy-core' ), esc_html__( 'year', 'hemmy-core' ), esc_html__( 'decade', 'hemmy-core' ) );
	
	$lengths = array( '60', '60', '24', '7', '4.35', '12', '10' );
	$now = time();
	$difference = $now - $time;
	
	$tense = esc_html__( 'ago', 'hemmy-core' );

	for( $j = 0; $difference >= $lengths[$j] && $j < count( $lengths )-1; $j++ ) {
		$difference /= $lengths[$j];
	}

	$difference = round( $difference );

	if( $difference != 1 ) {
		$periods[$j] .= esc_html__( 's', 'hemmy-core' );
	}

   return sprintf('%s %s %s', $difference, $periods[$j], $tense );
}
	
if( !class_exists( 'Hemmy_Twitter_Widget' ) ):

	class Hemmy_Twitter_Widget extends WP_Widget{
	
		/**
		 * Sets up the widgets name etc
		 */
		public function __construct() {
			parent::__construct(
				'hemmy_twitter_widget', // Base ID
				esc_html__( 'Twitter', 'hemmy-core' ) // Name
			);
		}
		
		/**
		  * Outputs the options form on admin
		  *
		  * @param array $instance The widget options
		  */
		public function form( $instance ) {
		 // outputs the options form on admin
			$twitter_title 				= ! empty( $instance['hemmy_twitter_title'] ) ? $instance['hemmy_twitter_title'] : '';
			$twitter_id 				= ! empty( $instance['hemmy_twitter_id'] ) ? $instance['hemmy_twitter_id'] : '';
			$twitter_consumer_key 		= ! empty( $instance['hemmy_twitter_consumer_key'] ) ? $instance['hemmy_twitter_consumer_key'] : '';
			$twitter_consumer_secret 	= ! empty( $instance['hemmy_twitter_consumer_secret'] ) ? $instance['hemmy_twitter_consumer_secret'] : '';
			$twitter_access_token 		= ! empty( $instance['hemmy_twitter_access_token'] ) ? $instance['hemmy_twitter_access_token'] : '';
			$twitter_access_secret 		= ! empty( $instance['hemmy_twitter_access_secret'] ) ? $instance['hemmy_twitter_access_secret'] : '';
			$twitter_tweets 			= ! empty( $instance['hemmy_twitter_tweets'] ) ? absint( $instance['hemmy_twitter_tweets'] ) : 1;
			$twitter_show_tweets		= ! empty( $instance['hemmy_twitter_show_tweets'] ) ? absint( $instance['hemmy_twitter_show_tweets'] ) : 1;
		 
		?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'hemmy_twitter_title' ) ); ?>"><?php esc_attr_e( 'Title:', 'hemmy-core' ); ?></label> 
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'hemmy_twitter_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hemmy_twitter_title' ) ); ?>" type="text" value="<?php echo esc_attr( $twitter_title ); ?>">
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'hemmy_twitter_id' ) ); ?>"><?php esc_attr_e( 'Twitter ID:', 'hemmy-core' ); ?></label> 
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'hemmy_twitter_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hemmy_twitter_id' ) ); ?>" type="text" value="<?php echo esc_attr( $twitter_id ); ?>">
			</p>
			
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'hemmy_twitter_consumer_key' ) ); ?>"><?php esc_attr_e( 'Consumer Key:', 'hemmy-core' ); ?></label> 
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'hemmy_twitter_consumer_key' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hemmy_twitter_consumer_key' ) ); ?>" type="text" value="<?php echo esc_attr( $twitter_consumer_key ); ?>">
			</p>
			
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'hemmy_twitter_consumer_secret' ) ); ?>"><?php esc_attr_e( 'Consumer Secret:', 'hemmy-core' ); ?></label> 
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'hemmy_twitter_consumer_secret' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hemmy_twitter_consumer_secret' ) ); ?>" type="text" value="<?php echo esc_attr( $twitter_consumer_secret ); ?>">
			</p>
			
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'hemmy_twitter_access_token' ) ); ?>"><?php esc_attr_e( 'Access Token:', 'hemmy-core' ); ?></label> 
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'hemmy_twitter_access_token' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hemmy_twitter_access_token' ) ); ?>" type="text" value="<?php echo esc_attr( $twitter_access_token ); ?>">
			</p>
			
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'hemmy_twitter_access_secret' ) ); ?>"><?php esc_attr_e( 'Access Token Secret:', 'hemmy-core' ); ?></label> 
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'hemmy_twitter_access_secret' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hemmy_twitter_access_secret' ) ); ?>" type="text" value="<?php echo esc_attr( $twitter_access_secret ); ?>">
			</p>
			
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'hemmy_twitter_tweets' ) )?>"><?php esc_attr_e( 'Number Of Tweets: ', 'hemmy-core' )?></label>
				<input type="number" id="<?php echo esc_attr( $this->get_field_id( 'hemmy_twitter_tweets' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hemmy_twitter_tweets' ) ); ?>" value="<?php echo esc_attr( $twitter_tweets ); ?>" min="1" max="10" class="widefat">
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'hemmy_twitter_show_tweets' ) )?>"><?php esc_attr_e( 'Number Of Shown Tweets: ', 'hemmy-core' )?></label>
				<input type="number" id="<?php echo esc_attr( $this->get_field_id( 'hemmy_twitter_show_tweets' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hemmy_twitter_show_tweets' ) ); ?>" value="<?php echo esc_attr( $twitter_show_tweets ); ?>" min="1" max="5" class="widefat">
			</p>
		<?php 
		}
		
		/**
		 * Processing widget options on save
		 *
		 * @param array $new_instance The new options
		 * @param array $old_instance The previous options
		 *
		 * @return array
		 */
		public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
			$instance = array();
			$instance['hemmy_twitter_title'] 			= ! empty( $new_instance['hemmy_twitter_title'] ) ? sanitize_text_field( $new_instance['hemmy_twitter_title'] ) : '';
			$instance['hemmy_twitter_id'] 				= ! empty( $new_instance['hemmy_twitter_id'] ) ? sanitize_text_field( $new_instance['hemmy_twitter_id'] ) : '';
			$instance['hemmy_twitter_consumer_key'] 	= ! empty( $new_instance['hemmy_twitter_consumer_key'] ) ? sanitize_text_field( $new_instance['hemmy_twitter_consumer_key'] ) : '';
			$instance['hemmy_twitter_consumer_secret'] 	= ! empty( $new_instance['hemmy_twitter_consumer_secret'] ) ? sanitize_text_field( $new_instance['hemmy_twitter_consumer_secret'] ) : '';
			$instance['hemmy_twitter_access_token'] 	= ! empty( $new_instance['hemmy_twitter_access_token'] ) ? sanitize_text_field( $new_instance['hemmy_twitter_access_token'] ) : '';
			$instance['hemmy_twitter_access_secret'] 	= ! empty( $new_instance['hemmy_twitter_access_secret'] ) ? sanitize_text_field( $new_instance['hemmy_twitter_access_secret'] ) : '';
			$instance[ 'hemmy_twitter_tweets' ] 		= absint( $new_instance[ 'hemmy_twitter_tweets' ] );
			$instance[ 'hemmy_twitter_show_tweets' ] 	= absint( $new_instance[ 'hemmy_twitter_show_tweets' ] );
		
			return $instance;
		}
	
		/**
		* Outputs the content of the widget
		*
		* @param array $args
		* @param array $instance
		*/
		public function widget( $args, $instance ) {
		 
			 $twitter_title 		= ! empty( $instance['hemmy_twitter_title'] ) ? $instance['hemmy_twitter_title'] : '';
			 $twitter_id 			= ! empty( $instance['hemmy_twitter_id'] ) ? $instance['hemmy_twitter_id'] : '';
			 $consumer_key 			= ! empty( $instance['hemmy_twitter_consumer_key'] ) ? $instance['hemmy_twitter_consumer_key'] : '';
			 $consumer_secret 		= ! empty( $instance['hemmy_twitter_consumer_secret'] ) ? $instance['hemmy_twitter_consumer_secret'] : '';
			 $access_token 			= ! empty( $instance['hemmy_twitter_access_token'] ) ? $instance['hemmy_twitter_access_token'] : '';
			 $access_token_secret 	= ! empty( $instance['hemmy_twitter_access_secret'] ) ? $instance['hemmy_twitter_access_secret'] : '';
			 $tweet_count 			= ! empty( $instance['hemmy_twitter_tweets'] ) ? absint( $instance['hemmy_twitter_tweets'] ) : 1;
			 $twitter_show_tweets	= ! empty( $instance['hemmy_twitter_show_tweets'] ) ? absint( $instance['hemmy_twitter_show_tweets'] ) : 1;
			
			echo wp_kses_post( $args['before_widget'] );
			
			if ( ! empty( $instance['hemmy_twitter_title'] ) ) {
					$widget_title = apply_filters( 'widget_title', $instance['hemmy_twitter_title'] );
					echo wp_kses_post( $args['before_title'] . $widget_title . $args['after_title'] );
	
			}
			$tweets = hemmy_get_tweets( $consumer_key, $consumer_secret, $access_token, $access_token_secret, $tweet_count, $twitter_id );

			if( $tweets && is_array( $tweets ) ) { ?>
				<div class="hemmy-twitter-widget">
					<div class="twitter-slider" data-item="<?php echo esc_attr( $twitter_show_tweets ); ?>">
						<?php foreach( $tweets as $tweet ) {
							
								$tweet_time = strtotime( $tweet['created_at'] ); 
								$time_ago = hemmy_tweet_time_ago( $tweet_time ); ?>
						
							<div class="item">
								<div class="tweet-wrap media">
									<div class="tweet-thumb">
										<a href="http://twitter.com/<?php echo esc_attr( $tweet["user"]["screen_name"] );?>/statuses/<?php echo esc_attr( $tweet['id_str'] ); ?>">
											<img class="rounded-circle align-self-center tweet-img mr-4" src="<?php echo esc_url( $tweet['user']['profile_image_url'] ); ?>" alt="<?php echo esc_attr( $tweet['user']['screen_name'] ); ?>" />			
												
										</a>
									</div>
									<div class="tweet-info-wrap media-body">
										<h5 class="mt-0 tweet-title">
											<a href="http://twitter.com/<?php echo esc_attr( $tweet['user']['screen_name'] ); ?>/statuses/<?php echo esc_attr( $tweet['id_str'] ); ?>"><?php echo esc_html( $tweet['user']['screen_name'] ); ?></a>
										</h5>
										<p class="tweet-text">
											<?php $tweet_text = $tweet['text'];
												$tweet_text = preg_replace( "~[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]~", "<a href=\"\\0\">\\0</a>", $tweet_text ); 
												echo wp_kses_post( $tweet_text );
											?>
										</p>
									</div>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>
			<?php } 
			
			echo wp_kses_post( $args['after_widget'] );
		}
	
	}
	
endif;

if( !class_exists( 'Hemmy_Mailchimp_Widget' ) ):

	class Hemmy_Mailchimp_Widget extends WP_Widget{
		
		private $default_failure_message;
		private $default_signup_text;
		private $default_success_message;
		private $default_title;
		private $successful_signup = false;
		private $subscribe_errors;
		private $api_key;
		/**
		 * Sets up the widgets name etc
		 */
		public function __construct() {
			$this->default_failure_message = esc_html__('There was a problem processing your submission.', 'hemmy-core');
			$this->default_signup_text = esc_html__('Join now!', 'hemmy-core');
			$this->default_success_message = esc_html__('Thank you for joining our mailing list. Please check your email for a confirmation link.', 'hemmy-core');
			$this->default_title = esc_html__('Sign up for our mailing list.', 'hemmy-core');
			$hemmy_option = hemmy_get_option( 'hemmy_mailchimp_apikey' );
			$this->api_key = isset( $hemmy_option ) ? $hemmy_option : '';
			parent::__construct(
				'hemmy_mailchimp_widget', // Base ID
				esc_html__( 'Mailchimp', 'hemmy-core' ) // Name
			);
		}
		
		/**
	      * Outputs the options form on admin
	      *
	      * @param array $instance The widget options
	      */
		public function form( $instance ) {
			$defaults = array(	'title' => '', 
								'current_mailing_list' => '', 
								'signup_text' => '', 
								'collect_first' => '', 
								'collect_last' => '', 
								'subtitle' => '', 
								'success_message' => esc_html__('Success.', 'hemmy-core'), 
								'failure_message' => esc_html__('Failure.', 'hemmy-core')
			);
			$instance = wp_parse_args( (array) $instance, $defaults );
			$api_key = $this->api_key;
			if( $api_key ){
				$dc = substr( $api_key, strpos( $api_key, '-' ) + 1 );
				$args = array(
					'headers' => array(
						'Authorization' => 'Basic ' . base64_encode( 'user:'. $api_key )
					)
				);
				$response = wp_remote_get( 'https://'.$dc.'.api.mailchimp.com/3.0/lists/?fields=lists', $args );
				$result = json_decode( $response['body'] );
			}
			?>
			<h3><?php echo esc_html__('General Settings', 'hemmy-core');?></h3>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'hemmy-mc-title' ) ); ?>"><?php esc_html_e( 'Title:', 'hemmy-core' ); ?></label> 
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>">
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'current_mailing_list' ) ); ?>"><?php esc_attr_e( 'Select A Mailing List:', 'hemmy-core' ); ?></label> 
				<select class="widefat" id="<?php echo esc_attr( $this->get_field_id('current_mailing_list') );?>" name="<?php echo esc_attr( $this->get_field_name('current_mailing_list') ); ?>">
				<?php	
					if( $api_key ){
						$selected = $instance['current_mailing_list'];
			
						if( !empty( $result->lists) ) {
							foreach( $result->lists as $list ){	?>	
							<option <?php echo ( $selected == $list->id ? ' selected="selected" ' : '' ); ?>value="<?php echo esc_attr( $list->id ); ?>"><?php echo esc_attr( $list->name ); ?></option>
							<?php }
						} 
					} ?>
				</select>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id('signup_text') ); ?>"><?php echo esc_html__('Sign Up Button Text :', 'hemmy-core'); ?></label>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id('signup_text') ); ?>" name="<?php echo esc_attr( $this->get_field_name('signup_text') ); ?>" value="<?php echo esc_attr( $instance['signup_text'] ); ?>" type="text"  />
			</p>
			<h3><?php echo esc_html__('Personal Information', 'hemmy-core');?></h3>
			<p>
				<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id('collect_first') ); ?>" name="<?php echo esc_attr( $this->get_field_name('collect_first') ); ?>" <?php echo checked($instance['collect_first'], true, false); ?> />
				<label for="<?php echo esc_attr( $this->get_field_id( 'collect_first' ) ); ?>"><?php esc_attr_e( 'Collect first name:', 'hemmy-core' ); ?></label>
				<br />
				<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $this->get_field_id('collect_last') ); ?>" name="<?php echo esc_attr( $this->get_field_name('collect_last') ); ?>" <?php echo checked($instance['collect_last'], true, false); ?> />
			<label><?php echo esc_html__('Collect last name.', 'hemmy-core'); ?></label>
			</p>
			<h3><?php echo esc_html__('Notification', 'hemmy-core');?></h3>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'subtitle' ) ); ?>"><?php esc_attr_e( 'Sub Title:', 'hemmy-core' ); ?></label> 
				<textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'subtitle' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name('subtitle') ); ?>" rows="3" cols="10"><?php echo esc_attr( $instance['subtitle'] ); ?></textarea>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'success_message' ) ); ?>"><?php esc_attr_e( 'Success Message:', 'hemmy-core' ); ?></label> 
				<textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'success_message' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name('success_message') ); ?>" rows="3" cols="10"><?php echo esc_attr( $instance['success_message'] ); ?></textarea>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'failure_message' ) ); ?>"><?php esc_attr_e( 'Failed Message:', 'hemmy-core' ); ?></label> 
				<textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'failure_message' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name('failure_message') ); ?>" rows="3" cols="10"><?php echo esc_attr( $instance['failure_message'] ); ?></textarea>
			</p>
		<?php }
		
		/**
	     * Processing widget options on save
	     *
	     * @param array $new_instance The new options
	     * @param array $old_instance The previous options
	     *
	     * @return array
	     */
		public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
			$instance = array();
			$instance['title']					= ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
			$instance['subtitle'] 				= ! empty( $new_instance['subtitle'] ) ? sanitize_textarea_field( $new_instance['subtitle'] ) : '';
			$instance['current_mailing_list'] 	= ! empty( $new_instance['current_mailing_list'] ) ? sanitize_text_field( $new_instance['current_mailing_list'] ) : '';
			$instance['failure_message']		= ! empty( $new_instance['failure_message'] ) ? sanitize_textarea_field( $new_instance['failure_message'] ) : '';
			$instance['signup_text'] 			= ! empty( $new_instance['signup_text'] ) ? sanitize_text_field( $new_instance['signup_text'] ) : '';
			$instance['collect_first'] 			= ! empty( $new_instance['collect_first'] );
			$instance['collect_last'] 			= ! empty( $new_instance['collect_last'] );
			$instance['success_message']		= ! empty( $new_instance['success_message'] ) ? sanitize_textarea_field( $new_instance['success_message'] ) : '';
			
			return $instance;
			
		}
		
		/**
	      * Outputs the content of the widget
	      *
	      * @param array $args
	      * @param array $instance
	      */
		 public function widget( $args, $instance ) {
		 	extract($args);

			echo wp_kses_post( $before_widget );
			echo ( $instance['title'] != '' ? wp_kses_post( $before_title . $instance['title'] . $after_title ) : '' );
			?>	
			<div class="mailchimp-wrapper">
				<form class="hemmy-mc-form" id="<?php echo 'hemmy-mc-form'; ?>" method="post">
					<?php	
						if( $instance['subtitle'] ) {
					?>	
					<p class="zozo-mc-subtitle"><?php echo stripslashes( $instance['subtitle'] ); ?></p>
					<p class="mc-aknowlegement" id="<?php echo 'zozo-mc-err'; ?>"></p>
					<?php	
						}
						if( $instance['collect_first'] ) {
					?>
					<div class="form-group">
						<input type="text" placeholder="<?php esc_html_e('First Name', 'hemmy-core'); ?>" class="form-control first-name" name="hemmy_mc_first_name" />
					</div>
					<?php
						}
						if( $instance['collect_last'] ) {
					?>	
					<div class="form-group">
						<input type="text" placeholder="<?php esc_html_e('Last Name', 'hemmy-core'); ?>" class="form-control last-name" name="hemmy_mc_last_name" />
					</div>
					<?php	
						}
					?>
					<input type="hidden" name="hemmy_mc_listid" value="<?php echo stripslashes( $instance['current_mailing_list'] ); ?>" />
					
					<div class="input-group" data-toggle="tooltip" data-placement="top">
						<input type="text" class="form-control hemmy-mc-email" id="zozo-mc-email" placeholder="<?php esc_html_e('Email Address', 'hemmy-core'); ?>" name="hemmy_mc_email">
						<input class="input-group-addon hemmy-mc mc-submit-btn btn btn-default"type="button" name="<?php echo stripslashes($instance['signup_text']); ?>" value="<?php echo stripslashes($instance['signup_text']); ?>" />
					</div>
						
					</form>
					<!--Mailchimp Custom Script-->
				
				<?php
					$success = $instance['success_message'] && $instance['success_message'] != '' ? $instance['success_message'] : esc_html__( 'Success', 'hemmy-core' );
					$fail = $instance['failure_message'] && $instance['failure_message'] != '' ? $instance['failure_message'] : esc_html__( 'Failed', 'hemmy-core' );
				?>
					<div class="mc-notice-group" data-success="<?php echo esc_html( $success ); ?>" data-fail="<?php echo esc_html( $fail ); ?>">
						<span class="mc-notice-msg"></span>
					</div><!-- .mc-notice-group -->
				</div><!-- .mailchimp-wrapper -->
			
			<?php
			echo wp_kses_post( $after_widget );
		 }
	
	}
endif;

if( !class_exists( 'Hemmy_Instagram_Widget' ) ):

	class Hemmy_Instagram_Widget extends WP_Widget{
		
		/**
		 * Sets up the widgets name etc
		 */
		public function __construct() {
			parent::__construct(
				'hemmy_instagram_widget', // Base ID
				esc_html__( 'Instagram', 'hemmy-core' ) // Name
			);
		}
		
		/**
	      * Outputs the options form on admin
	      *
	      * @param array $instance The widget options
	      */
		public function form( $instance ) {
		 // outputs the options form on admin
			$instagram_title 			= ! empty( $instance['hemmy_instagram_title'] ) ? $instance['hemmy_instagram_title'] : '';
			$instagram_username 		= ! empty( $instance['hemmy_instagram_username'] ) ? $instance['hemmy_instagram_username'] : '';
			$instagram_post				= ! empty( $instance['hemmy_instagram_post'] ) ? absint( $instance['hemmy_instagram_post'] ) : 1;
			$instagram_post_size 		= ! empty( $instance['hemmy_instagram_post_size'] ) ? $instance['hemmy_instagram_post_size'] : 'large';
			$instagram_post_target 		= ! empty( $instance['hemmy_instagram_post_target'] ) ? $instance['hemmy_instagram_post_target'] : '_self';
			$instagram_link 			= ! empty( $instance['hemmy_instagram_link'] ) ? $instance['hemmy_instagram_link'] : '';
		 ?>
		 <p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'hemmy_instagram_title' ) ); ?>"><?php esc_attr_e( 'Title:', 'hemmy-core' ); ?></label> 
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'hemmy_instagram_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hemmy_instagram_title' ) ); ?>" type="text" value="<?php echo esc_attr( $instagram_title ); ?>">
		</p>
		
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'hemmy_instagram_username' ) ); ?>"><?php esc_attr_e( 'Username:', 'hemmy-core' ); ?></label> 
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'hemmy_instagram_username' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hemmy_instagram_username' ) ); ?>" type="text" value="<?php echo esc_attr( $instagram_username ); ?>">
		</p>
		
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'hemmy_instagram_post' ) )?>"><?php esc_attr_e( 'Number Of Photos: ', 'hemmy-core' )?></label>
			<input type="number" id="<?php echo esc_attr( $this->get_field_id( 'hemmy_instagram_post' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hemmy_instagram_post' ) ); ?>" value="<?php echo esc_attr( $instagram_post ); ?>" min="1" max="20" class="widefat">
		</p>
		
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'hemmy_instagram_post_size' ) ); ?>"><?php esc_attr_e( 'Photo Size:', 'hemmy-core' ); ?></label> 
			<select id="<?php echo esc_attr( $this->get_field_id( 'hemmy_instagram_post_size' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hemmy_instagram_post_size' ) ); ?>" class="widefat">
				<option value="thumbnail" <?php selected( "thumbnail", $instagram_post_size ); ?>><?php esc_html_e( 'Thumbnail','hemmy-core' ); ?></option>
				<option value="small" <?php selected( "small", $instagram_post_size ); ?>><?php esc_html_e( 'Small','hemmy-core' ); ?></option>
				<option value="large" <?php selected( "large", $instagram_post_size ); ?>><?php esc_html_e( 'Large','hemmy-core' ); ?></option>
				<option value="original" <?php selected( "original", $instagram_post_size ); ?>><?php esc_html_e( 'Original','hemmy-core' ); ?></option>
			</select>
		</p>
		
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'hemmy_instagram_post_target' ) ); ?>"><?php esc_attr_e( 'Target:', 'hemmy-core' ); ?></label> 
			<select id="<?php echo esc_attr( $this->get_field_id( 'hemmy_instagram_post_target' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hemmy_instagram_post_target' ) ); ?>" class="widefat">
				<option value="_self" <?php selected( "_self", $instagram_post_target ); ?>><?php esc_html_e( 'Current Window(_self)','hemmy-core' ); ?></option>
				<option value="_blank" <?php selected( "_blank", $instagram_post_target ); ?>><?php esc_html_e( 'New Window(_blank)','hemmy-core' ); ?></option>
			</select>
		</p>
		
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'hemmy_instagram_link' ) ); ?>"><?php esc_attr_e( 'Follow Link:', 'hemmy-core' ); ?></label> 
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'hemmy_instagram_link' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hemmy_instagram_link' ) ); ?>" type="text" value="<?php echo esc_attr( $instagram_link ); ?>">
		</p>
		<?php }
		
		/**
	     * Processing widget options on save
	     *
	     * @param array $new_instance The new options
	     * @param array $old_instance The previous options
	     *
	     * @return array
	     */
		public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
			$instance = array();
			$instance = $old_instance;
			$instance['hemmy_instagram_title'] 		= ! empty( $new_instance['hemmy_instagram_title'] ) ? sanitize_text_field( $new_instance['hemmy_instagram_title'] ) : '';
			$instance['hemmy_instagram_username'] 	= ! empty( $new_instance['hemmy_instagram_username'] ) ? sanitize_text_field( $new_instance['hemmy_instagram_username'] ) : '';
			$instance['hemmy_instagram_post'] 		= ! empty( $new_instance['hemmy_instagram_post'] ) ? absint( $new_instance['hemmy_instagram_post'] ) : 1;
			$instance['hemmy_instagram_post_size'] 	= ! empty( $new_instance['hemmy_instagram_post_size'] ) ? sanitize_text_field( $new_instance['hemmy_instagram_post_size'] ) : '';
			$instance['hemmy_instagram_post_target']= ! empty( $new_instance['hemmy_instagram_post_target'] ) ? sanitize_text_field( $new_instance['hemmy_instagram_post_target'] ) : '';
			$instance['hemmy_instagram_link']	 	= ! empty( $new_instance['hemmy_instagram_link'] ) ? sanitize_text_field( $new_instance['hemmy_instagram_link'] ) : '';
			return $instance;
			
		}
		
		/**
	      * Outputs the content of the widget
	      *
	      * @param array $args
	      * @param array $instance
	      */
		 public function widget( $args, $instance ) {
		 	$instagram_title 			= ! empty( $instance['hemmy_instagram_title'] ) ? $instance['hemmy_instagram_title'] : '';
			$instagram_username 		= ! empty( $instance['hemmy_instagram_username'] ) ? $instance['hemmy_instagram_username'] : '';
			$instagram_post				= ! empty( $instance['hemmy_instagram_post'] ) ? absint( $instance['hemmy_instagram_post'] ) : 1;
			$instagram_post_size 		= ! empty( $instance['hemmy_instagram_post_size'] ) ? $instance['hemmy_instagram_post_size'] : 'large';
			$instagram_post_target 		= ! empty( $instance['hemmy_instagram_post_target'] ) ? $instance['hemmy_instagram_post_target'] : '_self';
			$instagram_link 			= ! empty( $instance['hemmy_instagram_link'] ) ? $instance['hemmy_instagram_link'] : '';
			
			echo wp_kses_post( $args['before_widget'] );
			
			if ( ! empty( $instance['hemmy_instagram_title'] ) ) {
					$widget_title = apply_filters( 'widget_title', $instance['hemmy_instagram_title'] );
					echo wp_kses_post( $args['before_title'] . $widget_title . $args['after_title'] );
	
			}
			do_action( 'wpiw_before_widget', $instance );
			
			if ( $instagram_username != '' ) {
				$media_array = hemmy_scrape_instagram( $instagram_username, $instagram_post );
				if ( is_wp_error( $media_array ) ) {
					echo wp_kses_post( $media_array->get_error_message() );
				}else{
					// filter for images only?
					if ( $images_only = apply_filters( 'wpiw_images_only', FALSE ) )
						$media_array = array_filter( $media_array, 'hemmy_images_only' );
					// filters for custom classes
					
					$media_array = array_slice( $media_array, 0, $instagram_post );
					
					$ulclass = apply_filters( 'wpiw_list_class', 'instagram-pics instagram-size-' . $instagram_post_size );
					$liclass = apply_filters( 'wpiw_item_class', '' );
					$aclass = apply_filters( 'wpiw_a_class', '' );
					$imgclass = apply_filters( 'wpiw_img_class', '' );
					$template_part = apply_filters( 'wpiw_template_part', 'parts/wp-instagram-widget.php' );
					?><ul class="nav <?php echo esc_attr( $ulclass ); ?>"><?php
					foreach ( $media_array as $item ) {
						// copy the else line into a new file (parts/wp-instagram-widget.php) within your theme and customise accordingly
						if ( locate_template( $template_part ) != '' ) {
							include locate_template( $template_part );
						} else {
							
							if( isset( $item[$instagram_post_size] ) && !empty( $item[$instagram_post_size] ) ){
								echo '<li class="'. esc_attr( $liclass ) .'" ><a href="'. esc_url( $item['link'] ) .'" target="'. esc_attr( $instagram_post_target ) .'"  class="'. esc_attr( $aclass ) .'"><div class="insta-footer-img" style="background-image:url('. esc_url( $item[$instagram_post_size] ) .');"></div></a></li>';
							}
						}
					}
					?></ul><?php
				}
			}
			$linkclass = apply_filters( 'wpiw_link_class', 'clear' );
			if ( $instagram_link != '' ) { ?>
				<p class="<?php echo esc_attr( $linkclass ); ?>"><a href="//instagram.com/<?php echo esc_attr( trim( $instagram_username ) ); ?>" rel="me" target="<?php echo esc_attr( $instagram_post_target ); ?>"><?php echo wp_kses_post( $instagram_link ); ?></a></p>
			<?php }
			do_action( 'wpiw_after_widget', $instance );
			echo wp_kses_post( $args['after_widget'] );
		}
	}
endif;


if( ! function_exists('hemmy_mailchimp') ) {
	function hemmy_mailchimp(){
		$nonce = $_POST['nonce'];
		if ( ! wp_verify_nonce( $nonce, 'hemmy-mailchimp' ) )
			die ( esc_html__( 'Busted', 'hemmy' ) );
			
		$first_name	= isset( $_POST['hemmy_mc_first_name'] ) ? sanitize_text_field( $_POST['hemmy_mc_first_name'] ) : '';
		$last_name 	= isset( $_POST['hemmy_mc_last_name' ] ) ? sanitize_text_field( $_POST['hemmy_mc_last_name'] ) : '';
		$list_id 	= isset( $_POST['hemmy_mc_listid'] ) ? sanitize_text_field( $_POST['hemmy_mc_listid'] ) : '';
		$email 		= isset( $_POST['hemmy_mc_email'] ) ? sanitize_email($_POST['hemmy_mc_email']) : '';
		$success 	= isset( $_POST['hemmy_mc_success'] ) ? sanitize_text_field( $_POST['hemmy_mc_success'] ) : '';
		
		if( $email == '' || $list_id == '' ){
			die ( 'failed' );
		}
		
		$memberID = md5( strtolower( $email ) );
		
		$api_key = hemmy_get_option( 'hemmy_mailchimp_apikey' );
		
		$dc = substr( $api_key, strpos( $api_key, '-' ) + 1 );
		
		$extra_args = array(
			'email_address' => esc_attr( $email ),
			'status' 		=> 'subscribed',
			'merge_fields'  => [
				'FNAME'     => esc_attr( $first_name ),
				'LNAME'     => esc_attr( $last_name )
			]		
		);
		
		$args = array(
			'method' 	=> 'PUT',
			'headers' 	=> array(
				'Authorization' => 'Basic ' . base64_encode( 'user:'. $api_key )
			),
			'body' 		=> json_encode( $extra_args )
		);
			
		$response = wp_remote_get( 'https://'.$dc.'.api.mailchimp.com/3.0/lists/'. esc_attr( $list_id ) .'/members/'. esc_attr( $memberID ), $args );
		$body = json_decode( $response['body'] );
		if ( $response['response']['code'] == 200 ) {
			echo "success";
		}elseif( $response['response']['code'] == 214 ){
			echo "already";
		}else {
			echo "failure";
		}
			
		die();
	}
	add_action('wp_ajax_nopriv_hemmy-mc', 'hemmy_mailchimp');
	add_action('wp_ajax_hemmy-mc', 'hemmy_mailchimp');
}