<?php

class Amazon_SES extends AWS_Plugin_Base {

	/**
	 * @var Amazon_Web_Services
	 */
	private $aws;

	/**
	 * @var Aws\Ses\SesClient
	 */
	private $sesclient;

    const DEFAULT_REGION = 'us-west-2';

	/**
	 * @param string              $plugin_file_path
	 * @param Amazon_Web_Services $aws
	 */
	function __construct( $plugin_file_path, $aws ) {
		$this->aws = $aws;
	}

	/**
	 * Get the SES client
	 *
	 * @param bool|string $region specify region to client for signature
	 * @param bool        $force  force return of new S3 client when swapping regions
	 *
	 * @return Aws\Ses\SesClient
	 */
	function get_sesclient( $region = false, $force = false ) {
		if ( is_null( $this->sesclient ) || $force ) {
			if ( $region ) {
				$args = array(
					'region' => $region,
					'signature' => 'v4',
				);
			} else {
				$args = array();
			}
			$client = $this->aws->get_client()->get( 'ses', $args );
			$this->set_client( $client );
		}
		return $this->sesclient;
	}

	/**
	 * Setter for S3 client
	 *
	 * @param Aws\Ses\SesClient $client
	 */
	public function set_client( $client ) {
		$this->sesclient = $client;
	}

	/**
	 * Header value filter
	 *
	 * @param array() $headers
	 * @param string  $filter_string
	 *
	 * @return array  matching strings
	 */
	private function filter_headers( $headers, $filter_string ) {
		return array_filter( $headers, function( $value ) use ( $filter_string ) {
			return substr( $value, 0, strlen( $filter_string ) ) === $filter_string;
		} );
	}

	/**
	 * Get From address from header or return WordPress default
	 *
	 * @param array() $headers
	 *
	 * @return string from
	 */
	private function from( $headers ) {
		$addresses = $this->filter_headers( $headers, 'From: ' );
		return empty( $addresses ) ? get_option( 'admin_email' ) : substr( array_shift( $addresses ), 5 );
	}

	/**
	 *
	 * Get content type for message body
	 *
	 * @param array() $headers
	 *
	 * @return string Content-Type
	 */
	private function content_type( $headers ) {
		$content_types = $this->filter_headers( $headers, 'Content-Type: ' );
		return empty( $content_types ) ? 'Content-Type: text/plain' : array_shift( $content_types );
	}

	/**
	 * Get charset for message body
	 *
	 * @param array() $headers
	 *
	 * @return string charset
	 */
	private function charset( $headers ) {
		$charsets = $this->filter_headers( $headers, 'charset' );
		return empty( $charsets ) ? 'charset=UTF-8' : array_shift( $charsets );
	}

	/**
	 * Send an email using Amazon SES
	 *
	 * @param string $to       the recipient for email message
	 * @param string $subject  the subject of the email message
	 * @param string $message  the body of the email message
	 * @param string $headers  custom headers for the email message
	 *
	 * @return bool  success
	 */
	public function mail( $to, $subject, $message, $headers = '', $attachments ) {
		if ( is_string( $to ) ) :
			$to = array( $to );
		endif;

		try {
			if ( empty( $attachments ) ) :
				$this->get_sesclient( self::DEFAULT_REGION )->sendEmail( array(
					'Source' => from( $headers ),
					'Destination' => array(
						'ToAddresses' => $to
					),
					'Message' => array(
						'Subject' => array(
							'Data' => $subject,
							'Charset' => 'UTF-8'
						),
						'Body' => array(
						       'Text' => array(
								'Data' => $message,
								'Charset' => $this->charset( $headers )
							)
						)
					)
				) );
			else :
				$boundary = uniqid( rand(), true );

				$raw_message = 'To: ' . join( ', ', $to ) . "\n";
				$raw_message .= 'From: ' . $this->from( $headers ) . "\n";
				$raw_message .= 'Subject: ' . $subject . "\n";
				$raw_message .= 'MIME-Version: 1.0' . "\n";
				$raw_message .= 'Content-type: Multipart/Mixed; boundary="' . $boundary . '"' . "\n";
				$raw_message .= "\n--{$boundary}\n";
				$raw_message .= 'Content-type: Multipart/Alternative; boundary="alt-' . $boundary . '"' . "\n";

				if ( $message != null && strlen( $message ) > 0) :
					$raw_message .= "\n--alt-{$boundary}\n";
					$raw_message .= $this->content_type( $headers ) . '; ' . $this->charset( $headers ) . "\n\n";
					$raw_message .= $message . "\n";
				endif;

				$raw_message .= "\n--{$boundary}\n";

				foreach( $attachments as $attachment ) :
					 $raw_message .= "\n--{$boundary}\n";
					 $raw_message .= 'Content-Type: application/octet-stream; name="' . basename( $attachment ) . '"' . "\n";
					 $raw_message .= 'Content-Disposition: attachment' . "\n";
					 $raw_message .= 'Content-Transfer-Encoding: base64' . "\n";
					 $raw_message .= "\n" . chunk_split( base64_encode( file_get_contents( $attachment ) ), 76, "\n" ) . "\n";
				endforeach;

				$raw_message .= "\n--{$boundary}--\n";

				$this->get_sesclient( self::DEFAULT_REGION )->sendRawEmail( array(
					'Source' => get_option( 'admin_email' ),
					'Destinations' => $to,
					'RawMessage' => array(
						'Data' => base64_encode( $raw_message )
					)
				) );
			endif;

			return true;
		} catch( Exception $e ) {
			return false;
		}
	}
}
