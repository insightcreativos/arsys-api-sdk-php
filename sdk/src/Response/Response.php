<?php

/**
 * Arsys API response object.
 * @package ArsysPHP
 * @subpackage Response
 */

namespace Arsys\API\Response;

class Response {
	/**
	 * Response in array format.
	 * @var array
	 */
	protected $response = [
		'success'      => null,
		'errorCode'    => null,
		'errorCodeMsg' => null,
		'action'       => null,
		'version'      => null,
		'responseData' => null
	];

	/**
	 * Response in RAW format (JSON string).
	 * @var string
	 */
	protected $rawResponse;

	/**
	 * Options.
	 * @var array
	 */
	protected $options = [
		'throwExceptions' => false
	];

	/**
	 * Exception mapping.
	 * @var array
	 */
	protected static $errorMap = [
		'-1' => \Arsys\API\Exceptions\ValidationError::class,

		'1' => \Arsys\API\Exceptions\UndefinedError::class,

		'100' => \Arsys\API\Exceptions\Syntax\Error::class,
		'101' => \Arsys\API\Exceptions\Syntax\ParameterFault::class,
		'102' => \Arsys\API\Exceptions\ObjectOrAction_NotValid::class,
		'103' => \Arsys\API\Exceptions\ObjectOrAction_NotAllowed::class,
		'104' => \Arsys\API\Exceptions\ObjectOrAction_NotImplemented::class,
		'105' => \Arsys\API\Exceptions\Syntax\InvalidParameter::class,

		'200' => \Arsys\API\Exceptions\Authentication\Login_Required::class,
		'201' => \Arsys\API\Exceptions\Authentication\Login_Invalid::class,
		'210' => \Arsys\API\Exceptions\Authentication\Session_Invalid::class,

		'300' => \Arsys\API\Exceptions\Action_NotAllowed::class,

		'1000' => \Arsys\API\Exceptions\Account\Blocked::class,
		'1001' => \Arsys\API\Exceptions\Account\Deleted::class,
		'1002' => \Arsys\API\Exceptions\Account\Inactive::class,
		'1003' => \Arsys\API\Exceptions\Account\NotExists::class,
		'1004' => \Arsys\API\Exceptions\Account\InvalidPass::class,
		'1005' => \Arsys\API\Exceptions\Account\InvalidPass::class,
		'1006' => \Arsys\API\Exceptions\Account\Blocked::class,
		'1007' => \Arsys\API\Exceptions\Account\Filtered::class,
		'1009' => \Arsys\API\Exceptions\Account\InvalidPass::class,
		'1010' => \Arsys\API\Exceptions\Account\Blocked::class,
		'1011' => \Arsys\API\Exceptions\Account\Blocked::class,
		'1012' => \Arsys\API\Exceptions\Account\Blocked::class,
		'1013' => \Arsys\API\Exceptions\Account\Blocked::class,
		'1014' => \Arsys\API\Exceptions\Account\Filtered::class,
		'1030' => \Arsys\API\Exceptions\Account\Banned::class,

		'1100' => \Arsys\API\Exceptions\Account\InsufficientBalance::class,

		'2001' => \Arsys\API\Exceptions\Domain\InvalidDomainName::class,
		'2002' => \Arsys\API\Exceptions\Domain\TLD_NotSupported::class,
		'2003' => \Arsys\API\Exceptions\Domain\TLD_UnderMaintenance::class,
		'2004' => \Arsys\API\Exceptions\Domain\CheckError::class,
		'2005' => \Arsys\API\Exceptions\Domain\TransferNotAllowed::class,
		'2006' => \Arsys\API\Exceptions\Domain\WhoisNotAllowed::class,
		'2007' => \Arsys\API\Exceptions\Domain\WhoisError::class,
		'2008' => \Arsys\API\Exceptions\Domain\NotFound::class,
		'2009' => \Arsys\API\Exceptions\Domain\Create\Error::class,
		'2010' => \Arsys\API\Exceptions\Domain\Create\Taken::class,
		'2011' => \Arsys\API\Exceptions\Domain\Create\PremiumDomain::class,
		'2012' => \Arsys\API\Exceptions\Domain\TransferError::class,

		'2100' => \Arsys\API\Exceptions\Domain\RenewError::class,
		'2101' => \Arsys\API\Exceptions\Domain\RenewNotAllowed::class,
		'2102' => \Arsys\API\Exceptions\Domain\RenewBlocked::class,

		'2200' => \Arsys\API\Exceptions\Domain\UpdateError::class,
		'2201' => \Arsys\API\Exceptions\Domain\UpdateNotAllowed::class,
		'2202' => \Arsys\API\Exceptions\Domain\UpdateBlocked::class,

		'2210' => \Arsys\API\Exceptions\Domain\VerificationStatus::class,

		'3001' => \Arsys\API\Exceptions\Contact\NotExists::class,
		'3002' => \Arsys\API\Exceptions\Contact\DataError::class,
		'3003' => \Arsys\API\Exceptions\Contact\VerificationStatus::class,

		'3500' => \Arsys\API\Exceptions\User\NotExists::class,
		'3501' => \Arsys\API\Exceptions\User\CreateError::class,
		'3502' => \Arsys\API\Exceptions\User\UpdateError::class,

		'4001' => \Arsys\API\Exceptions\Service\NotFound::class,
		'4002' => \Arsys\API\Exceptions\Service\EntityNotFound::class,
		'4003' => \Arsys\API\Exceptions\Service\EntityLimitReached::class,
		'4004' => \Arsys\API\Exceptions\Service\EntityCreateError::class,
		'4005' => \Arsys\API\Exceptions\Service\EntityUpdateError::class,
		'4006' => \Arsys\API\Exceptions\Service\EntityDeleteError::class,
		'4007' => \Arsys\API\Exceptions\Service\CreateError::class,
		'4008' => \Arsys\API\Exceptions\Service\UpgradeError::class,
		'4009' => \Arsys\API\Exceptions\Service\RenewError::class,
		'4010' => \Arsys\API\Exceptions\Service\ParkingUpdateError::class,

		'5000' => \Arsys\API\Exceptions\SSL\Error::class,
		'5001' => \Arsys\API\Exceptions\SSL\NotFound::class,

		'10001' => \Arsys\API\Exceptions\Webconstructor_Error::class,
	];

	/**
	 * Take a response string in JSON format and an array of options to initialize the class.
	 *
	 * @param string $response Response in JSON format
	 * @param array $options Options passed to the class
	 */
	public function __construct( $response = null, array $options = [] ) {
		$this->rawResponse = $response;

		$responseJson = @json_decode( $this->rawResponse, true );

		if ( is_array( $responseJson ) ) {
//			$this->response = array_merge( $this->response, $responseJson );
			$this->response['responseData'] = $responseJson;
			$this->response['success']      = true;
			$this->response['errorCode']    = null;
			$this->response['errorCodeMsg'] = null;
			$this->response['action']       = 'GET';
			$this->response['version']      = '2.0.0';
		}

		$this->options = array_merge( $this->options, $options );

		$this->readResponse();
	}

	/**
	 * Check for errors in the response, and throw appropriate exceptions.
	 * @throws \Arsys\API\Exceptions\Error on invalid responses and errors
	 */
	protected function readResponse() {
		if ( $this->options['throwExceptions'] ) {
			if ( is_null( $this->getSuccess() ) ) {
				throw new \Arsys\API\Exceptions\Error( 'Invalid response: ' . $this->rawResponse );
			}

			if ( ! $this->getSuccess() ) {
				throw $this->castError( $this->response );
			}
		}
	}

	/**
	 * Get the "success" parameter from the response.
	 * @return boolean
	 */
	public function getSuccess() {
		return $this->response['success'];
	}

	/**
	 * Get the "errorCode" parameter from the response.
	 * @return string
	 */
	public function getErrorCode() {
		return $this->response['errorCode'];
	}

	/**
	 * Get the "errorCodeMsg" parameter from the response.
	 * @return string
	 */
	public function getErrorCodeMsg() {
		return $this->response['errorCodeMsg'];
	}

	/**
	 * Get the "action" parameter from the response.
	 * @return string
	 */
	public function getAction() {
		return $this->response['action'];
	}

	/**
	 * Get the "version" parameter from the response.
	 * @return string
	 */
	public function getVersion() {
		return $this->response['version'];
	}

	/**
	 * Get all of the values in the "responseData" field in array format.
	 * @return array
	 */
	public function getResponseData() {
		return $this->response['responseData'];
	}

	/**
	 * Get a parameter from the "responseData" array.
	 *
	 * @param string $key Key of the parameter
	 *
	 * @return mixed or false if $key not found
	 */
	public function get( $key ) {
		if ( array_key_exists( $key, $this->response['responseData'] ) ) {
			return $this->response['responseData'][ $key ];
		} else {
			return false;
		}
	}

	/**
	 * Set a parameter from the "responseData" array.
	 *
	 * @param string $key Key of the parameter
	 * @param mixed $data Value of the parameter
	 *
	 * @return mixed or false if $key not found
	 */
	public function set( string $key, $data ) {
		$this->response['responseData'][ $key ] = $data;
		return $this->get( $key );
	}

	/**
	 * Get the response in raw JSON string format.
	 * @return string
	 */
	public function getRawResponse() {
		return $this->rawResponse;
	}

	/**
	 * Get the response in array format.
	 * @return array
	 */
	public function getArray() {
		return $this->response;
	}

	/**
	 * Output the response in the specified format.
	 *
	 * @param string $method Output filter
	 * @param array $args Optional arguments for the call
	 */
	public function output( $format, array $args = [] ) {
		$filters = \Arsys\API\OutputFilters\OutputFilter::getOutputFilters();

		if ( ! array_key_exists( $format, $filters ) ) {
			trigger_error( 'Not a valid Output Format: ' . $format, E_USER_ERROR );
		}

		$class = $filters[ $format ];

		if ( ! class_exists( $class ) ) {
			trigger_error( 'Not a valid Output Class: ' . $class, E_USER_ERROR );
		}

		$outputFilter = new $class( $args );

		if ( ! method_exists( $outputFilter, 'render' ) ) {
			trigger_error( 'Method render not found in output filter ' . $class, E_USER_ERROR );
		}

		return $outputFilter->render( $this->response['responseData'] );
	}

	/**
	 * Throw the appropriate exception for the error received.
	 *
	 * @param array $result Response from the API; an error response is expected
	 *
	 * @throws \Arsys\API\Exceptions\Error or the appropriate exception
	 */
	public function castError( array $result ) {
		if ( ! $result['errorCode'] ) {
			throw new \Arsys\API\Exceptions\Error( 'Got an unexpected error: ' . @json_encode( $result ) );
		}

		$class = ( isset( self::$errorMap[ $result['errorCode'] ] ) ) ? self::$errorMap[ $result['errorCode'] ] : '\Arsys\API\Exceptions\Error';

		return new $class( $result['messages'], $result['errorCode'] );
	}
}
