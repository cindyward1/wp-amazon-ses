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
	 * Send an email using Amazon SES
	 *
	 * @param string $to       the recipient for email message
	 * @param string $subject  the subject of the email message
	 * @param string $message  the body of the email message
	 * @param string $headers  custom headers for the email message
	 *
	 * @return bool  success
	 */
	public function mail( $to, $subject, $message, $headers = '' ) {
		try {
			$this->get_sesclient( self::DEFAULT_REGION )->sendEmail( array(
				'Source' => get_option( 'admin_email' ),
				'Destination' => array(
					'ToAddresses' => array( $to )
				),
				'Message' => array(
					'Subject' => array(
						'Data' => $subject,
						'Charset' => 'UTF-8'
					),
					'Body' => array(
						'Text' => array(
							'Data' => $message,
							'Charset' => 'UTF-8'
						)
					)
				)
			) );

			return true;
		} catch( Exception $e ) {
			return false;
		}
	}
}
