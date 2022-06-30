<?php

/**
 * Interface for Arsys API clients.
 * @package ArsysPHP
 * @subpackage Client
 */

namespace Arsys\API\Client;

interface Client_Interface {
	/**
	 * Call an API endpoint.
	 *
	 * @param string $url URL to be requested
	 * @param array $args Arguments to be passed along the request
	 *
	 * @return \Arsys\API\Response\Response
	 */
	public function execute( $url, array $args = [] );
}
