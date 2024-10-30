<?php

/**
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 */
class Hemmy_Core_MetaBox {

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
	public function metabox_enqueue_scripts() {

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
		wp_enqueue_media();
        wp_enqueue_script( 'media-upload' );
		wp_enqueue_style( $this->plugin_name, HEMMY_CORE_URL . '/assets/css/metabox.css' );

	}
	
	public function hemmy_post_options_meta_box(){
	
		add_meta_box(
			'hemmy-post-options',
			esc_html__( 'Hemmy Post Options', 'hemmy-core' ),
			array( $this, 'hemmy_post_options_form' ),
			'post',
			'normal',
			'high'
		);
	}
	
	public function hemmy_post_options_form( $post) {
	
		wp_nonce_field( '_hemmy_post_opt_nonce', 'hemmy_post_opt_nonce' ); 
	
		$hemmy_feature_post 			= get_post_meta( $post->ID, 'hemmy_feature_post', true );
		$hemmy_related_post 			= get_post_meta( $post->ID, 'hemmy_related_post', true );
		$hemmy_post_author_info 		= get_post_meta( $post->ID, 'hemmy_post_author_info', true );
		$hemmy_post_page_feature_img 	= get_post_meta( $post->ID, 'hemmy_post_page_feature_img', true );
		$hemmy_post_page_sidebar 		= get_post_meta( $post->ID, 'hemmy_post_page_sidebar', true );
	?>
		
	<div class="hemmy-post-options-wrap">
		<table class="form-table">
			<tbody>
				<tr class="option-item feature_post">
					<th><label for="<?php esc_attr_e( 'Feature Post', 'hemmy-core' ); ?>"><?php echo esc_html_e( 'Feature Post','hemmy-core' );?></label></th>
					<td>
						<select name="hemmy_feature_post" id="hemmy_feature_post">
							<option value="disable" name="disable" <?php selected( $hemmy_feature_post, 'disable' ); ?>><?php esc_html_e( 'Disable', 'hemmy-core' ); ?></option>
							<option value="enable" name="enable" <?php selected( $hemmy_feature_post, 'enable' ); ?>><?php esc_html_e( 'Enable','hemmy-core' ); ?></option>
						</select>
					</td>
				</tr>
				<tr class="option-item related_post">
					<th><label for="<?php esc_attr_e( 'Related Post', 'hemmy-core' ); ?>"><?php echo esc_html_e( 'Related Post','hemmy-core' );?></label></th>
					<td>
						<select name="hemmy_related_post" id="hemmy_related_post">
							<option value="disable" name="disable" <?php selected( $hemmy_related_post, 'disable' ); ?>><?php esc_html_e( 'Disable', 'hemmy-core' ); ?></option>
							<option value="enable" name="enable" <?php selected( $hemmy_related_post, 'enable' ); ?>><?php esc_html_e( 'Enable','hemmy-core' ); ?></option>
						</select>
					</td>
				</tr>
				<tr class="option-item show_author_info">
					<th><label for="<?php esc_attr_e( 'Post Author Info', 'hemmy-core' ); ?>"><?php echo esc_html_e( 'Post Author Info','hemmy-core' );?></label></th>
					<td>
						<select name="hemmy_post_author_info" id="hemmy_post_author_info">
							<option value="disable" name="disable" <?php selected( $hemmy_post_author_info, 'disable' ); ?>><?php esc_html_e( 'Disable', 'hemmy-core' ); ?></option>
							<option value="enable" name="enable" <?php selected( $hemmy_post_author_info, 'enable' ); ?>><?php esc_html_e( 'Enable','hemmy-core' ); ?></option>
						</select>
					</td>
				</tr>
				<tr class="option-item show_feature_img_info">
					<th><label for="<?php esc_attr_e( 'Post Feature Image', 'hemmy-core' ); ?>"><?php echo esc_html_e( 'Post Page - Feature Image ','hemmy-core' );?></label></th>
					<td>
						<select name="hemmy_post_page_feature_img" id="hemmy_post_page_feature_img">
							<option value="enable" name="enable" <?php selected( $hemmy_post_page_feature_img, 'enable' ); ?>><?php esc_html_e( 'Enable','hemmy' ); ?></option>
							<option value="disable" name="disable" <?php selected( $hemmy_post_page_feature_img, 'disable' ); ?>><?php esc_html_e( 'Disable', 'hemmy-core' ); ?></option>							
						</select>
					</td>
				</tr>
				<tr class="option-item show_post_sidebar">
					<th><label for="<?php esc_attr_e( 'Post Sidebar', 'hemmy-core' ); ?>"><?php echo esc_html_e( 'Post Page - Sidebar','hemmy-core' );?></label></th>
					<td>
						<select name="hemmy_post_page_sidebar" id="hemmy_post_page_sidebar">
							<option value="disable" name="disable" <?php selected( $hemmy_post_page_sidebar, 'disable' ); ?>><?php esc_html_e( 'Disable', 'hemmy-core' ); ?></option>
							<option value="enable" name="enable" <?php selected( $hemmy_post_page_sidebar, 'enable' ); ?>><?php esc_html_e( 'Enable','hemmy-core' ); ?></option>
						</select>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
		
	<?php }
	
	public function hemmy_post_options_save( $post_id ){
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! isset( $_POST['hemmy_post_opt_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['hemmy_post_opt_nonce'] ), '_hemmy_post_opt_nonce' ) ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		if ( isset( $_POST['hemmy_feature_post'] ) ) {
	
			update_post_meta( $post_id, 'hemmy_feature_post', sanitize_text_field( wp_unslash( $_POST['hemmy_feature_post'] ) ) );
		}
		else{
			// delete data
			delete_post_meta( $post_id, 'hemmy_feature_post' );
		}
	
		//related post
		if ( isset( $_POST['hemmy_related_post'] ) ) {
	
			update_post_meta( $post_id, 'hemmy_related_post', sanitize_text_field( wp_unslash( $_POST['hemmy_related_post'] ) ) );
		}
		else{
			// delete data
			delete_post_meta( $post_id, 'hemmy_related_post' );
		}
	
		//post author info
		if ( isset( $_POST['hemmy_post_author_info'] ) ) {
	
			update_post_meta( $post_id, 'hemmy_post_author_info', sanitize_text_field( wp_unslash( $_POST['hemmy_post_author_info'] ) ) );
		}
		else{
			// delete data
			delete_post_meta( $post_id, 'hemmy_post_author_info' );
		}
	
		//post page feature img
		if( isset( $_POST['hemmy_post_page_feature_img'] ) ){
			update_post_meta( $post_id, 'hemmy_post_page_feature_img', sanitize_text_field( wp_unslash( $_POST['hemmy_post_page_feature_img'] ) ) );
		}
		else{
			// delete data
			delete_post_meta( $post_id, 'hemmy_post_page_feature_img' );
		}
	
		//post page -sidebar
		if( isset( $_POST['hemmy_post_page_sidebar'] ) ){
			update_post_meta( $post_id, 'hemmy_post_page_sidebar', sanitize_text_field( wp_unslash( $_POST['hemmy_post_page_sidebar'] ) ) );
		}
		else{
			// delete data
			delete_post_meta( $post_id, 'hemmy_post_page_sidebar' );
		}
	}
	
}