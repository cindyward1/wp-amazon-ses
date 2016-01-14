<?php
/**
 * Custom wp_mail function
 */

if ( ! function_exists( 'wp_mail' ) ) :

	function wp_mail( $to, $subject, $message, $headers = '' ) {
		global $amazon_ses;

		if ( isset( $amazon_ses ) ) {
			return $amazon_ses->mail( $to, $subject, $message, $headers );
		} else {
			return false;
		}
	}

endif;
