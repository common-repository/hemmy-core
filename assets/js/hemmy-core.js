(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	/*Mailchimp Code*/
	if( $('.hemmy-mc').length ){
		$('.hemmy-mc').live( "click", function () {
			
			var c_btn = $(this);
			var mc_wrap = $( this ).parents('.mailchimp-wrapper');
			var mc_form = $( this ).parents('.hemmy-mc-form');
			
			if( mc_form.find('input[name="hemmy_mc_email"]').val() == '' ){
				mc_wrap.find('.mc-notice-msg').text( hemmy_ajax_var.must_fill );
			}else{
				c_btn.attr( "disabled", "disabled" );
				$.ajax({
					type: "POST",
					url: hemmy_ajax_var.admin_ajax_url,
					data: 'action=hemmy-mc&nonce='+hemmy_ajax_var.mc_nounce+'&'+mc_form.serialize(),
					success: function (data) {
						//Success
						c_btn.removeAttr( "disabled" );
						if( data == 'success' || data == 'already' ){
							mc_wrap.find('.mc-notice-msg').text( mc_wrap.find('.mc-notice-group').attr('data-success') );
						}else{
							mc_wrap.find('.mc-notice-msg').text( mc_wrap.find('.mc-notice-group').attr('data-fail') );
						}
					},error: function(xhr, status, error) {
						c_btn.removeAttr( "disabled" );
						mc_wrap.find('.mc-notice-msg').text( mc_wrap.find('.mc-notice-group').attr('data-fail') );
					}
				});
			}
		});
	} // if mailchimp exists
	
})( jQuery );
