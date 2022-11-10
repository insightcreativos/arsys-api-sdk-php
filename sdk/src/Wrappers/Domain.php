<?php

/**
 * Wrapper for the Arsys Domain API module.
 * Please read the online documentation for more information before using the module.
 *
 * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
 * @                                                                                        @
 * @  Certain calls in this module can use credit from your Arsys/MrDomain account.    @
 * @  Caution is advised when using calls in this module.                                    @
 * @                                                                                        @
 * @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
 *
 * @package ArsysPHP
 * @subpackage Wrappers
 */

namespace Arsys\API\Wrappers;

use Arsys\API\Response\Response;
use function PHPUnit\Framework\throwException;

class Domain extends AbstractWrapper {

	/** @var array $domain_info Domain Info */
	public array $domain_info = [];

	/** @var array $name_servers Name Servers Info */
	public array $name_servers = [];

	/** @var array $contacts Contacts Info */
	public array $contacts = [];

	/**
	 * Rewriting the proxy method for specific needs.
	 *
	 * @param string $method Method name
	 * @param array $args Array of arguments passed to the method
	 *
	 * @return Response
	 */
	public function proxy( $method, array $args = [] ) {
		if ( $method == 'list' ) {
			$method = 'getList';
		}

		if ( ! method_exists( $this, $method ) ) {
			trigger_error( 'Method ' . $method . ' not found', E_USER_ERROR );
		}

		return call_user_func_array( [ $this, $method ], $args );
	}

	/**
	 * HACER DOMAIN check
	 *
	 * Check the availibility of a domain name.
	 *
	 * @link
	 *
	 * @param string $domain Domain name to check
	 *
	 * @return Response
	 */
	protected function check( $domain ) {
		$_params = [ 'domain' => $domain ];

		return $this->execute( 'domains/' . $_params['domain'] . '/domain_protection', [], [] );
	}

	/**
	 * HACER DOMAIN checkForTransfer
	 *
	 * Check if a domain can be transfered.
	 *
	 * @link
	 *
	 * @param string $domain Domain name to check
	 *
	 * @return Response
	 */
	protected function checkForTransfer( $domain ) {
		$_params = [ 'domain' => $domain ];

		$map = [
			[ 'name' => 'domain', 'type' => 'domain', 'required' => true ]
		];

		return $this->execute( 'domain/checkfortransfer/', $_params, $map );
	}

	/**
	 * https://domain.apitool.info/v2/domains
	 *
	 * Create a new Domain.
	 * Accepts an associative array with the following parameters:
	 *
	 * ! = required
	 * - period        integer        Number of years to register the domain.
	 * - premium        boolean    Must be true to register premium domains.
	 * - nameservers    string        Comma-separated list of DNS servers (min 2, max 7).
	 *                                Use "parking" for redirection & parking service.
	 * ! owner            array        Associative array of owner contact information.
	 * - admin            array        Associative array of administrator contact infromation.
	 * - tech            array        Associative array of technical contact information.
	 * - billing        array        Associative array of billing contact information.
	 *
	 * @link
	 *
	 * @param string $domain Domain name to register
	 * @param array $args Associative array of parameters
	 *
	 * @return Response
	 * @throws \Exception
	 */
	protected function create( $domain, array $args = [] ) {
		$params = $this->getParamsForTransferOrCreate( $domain, $args );
		$params = [
			"domain"          => $domain,
			"duration"        => "1",
			"registrant_code" => $params['registrant_code'],
			"admin_code"      => $params['admin_code'],
			"tech_code"       => $params['tech_code'],
			"servers_code"    => $params['servers_code'],
//			"intended_use"    => "commercial purpose"
		];
		$resp = $this->execute( 'domains_v2', $params, [], 'POST' );

		if ( $this->isResponseError( $resp ) ) {
			throw new \Exception('Ha habido algún error al registrar el dominio.');
		}

		return $resp;
	}

	/**
	 *
	 *
	 * Transfer a domain.
	 * Accepts an associative array with the following parameters:
	 *
	 * ! = required
	 * - nameservers    string        Comma-separated list of DNS servers (min 2, max 7).
	 *                                Use "parking" for redirection & parking service.
	 *                                Use "keepns" to leave the DNS servers unmodified.
	 * - authcode        string        Authcode for the Domain, if necessary.
	 * ! owner            array        Associative array of owner contact information.
	 * - admin            array        Associative array of administrator contact infromation.
	 * - tech            array        Associative array of technical contact information.
	 * - billing        array        Associative array of billing contact information.
	 *
	 * @link
	 *
	 * @param string $domain Domain name to transfer
	 * @param array $args Associative array of parameters
	 *
	 * @return Response
	 * @throws \Exception
	 */
	protected function transfer( $domain, array $args = [] ) {
		$params = $this->getParamsForTransferOrCreate( $domain, $args );

		return $this->execute( str_replace( '//', '/', 'domains/' . $domain . '/transfer_v2' ), $params, [],
			'POST' );

	}

	/**
	 * HACER DOMAIN transferRestart
	 *
	 * Restart a transfer already initiated.
	 * Accepts an associative array with the following parameters:
	 *
	 * ! = required
	 * - authcode        string        A new authcode to replace the old one
	 *
	 * @link
	 *
	 * @param string $domain Domain name to update
	 * @param string $updateType Type of information to modify (contact, nameservers, transferBlock, block, whoisPrivacy)
	 * @param array $args Associative array of parameters
	 *
	 * @return Response
	 */
	protected function transferRestart( $domain, array $args = [] ) {
		$_params = array_merge( $this->getDomainOrDomainID( $domain ), $args );

		$map = [
			[ 'name' => 'domain', 'type' => 'domain', 'required' => true, 'bypass' => 'domainID' ],
			[ 'name' => 'domainID', 'type' => 'string', 'required' => true, 'bypass' => 'domain' ],
			[ 'name' => 'authcode', 'type' => 'string', 'required' => false ],
			[ 'name' => 'foacontact', 'type' => 'string', 'required' => false, 'list' => [ 'owner', 'admin' ] ],
		];

		return $this->execute( 'domain/transferrestart/', $_params, $map );
	}

	/**
	 * HACER DOMAIN update domain
	 *
	 * Update domain parameters, such as contacts, nameservers, and more.
	 * Accepts an associative array with the following parameters:
	 *
	 * ! = required
	 * [updateType = contact]
	 * - owner            array        Associative array of owner contact information.
	 * - admin            array        Associative array of administrator contact infromation.
	 * - tech            array        Associative array of technical contact information.
	 * - billing        array        Associative array of billing contact information.
	 *
	 * [updateType = nameservers]
	 * ! nameservers    string        Comma-separated list of DNS servers (min 2, max 7).
	 *                                Use "default" to assign Arsys/MrDomain hosting values.
	 *
	 * [updateType = transferBlock]
	 * ! transferBlock    boolean        Enables or disables the transfer block for the domain.
	 *
	 * [updateType = block]
	 * ! block            boolean        Enables or disables the domain block.
	 *
	 * [updateType = whoisPrivacy]
	 * ! whoisPrivacy    boolean        Enables or disables the whoisPrivacy service for the domain.
	 *
	 * @link
	 *
	 * @param string $domain Domain name to update
	 * @param string $updateType Type of information to modify (contact, nameservers, transferBlock, block, whoisPrivacy)
	 * @param array $args Associative array of parameters
	 *
	 * @return Response
	 */
	protected function update( $domain, array $args = [] ) {
//		throw new \Exception(__CLASS__ . ' ' . __FUNCTION__ . ' Not implemented');
//		$_params = array_merge( $this->getDomainOrDomainID( $domain ), $this->flattenContacts( $args ) );

//		$map = [
//			[ 'name' => 'domain', 'type' => 'domain', 'required' => true, 'bypass' => 'domainID' ],
//			[ 'name' => 'domainID', 'type' => 'string', 'required' => true, 'bypass' => 'domain' ],
//			[
//				'name'     => 'updateType',
//				'type'     => 'list',
//				'required' => true,
//				'list'     => [
//					'contact',
//					'nameservers',
//					'transferBlock',
//					'block',
//					'whoisPrivacy',
//					'renewalMode',
//					'tag',
//					'viewWhois'
//				]
//			],
//
//			[ 'name' => 'ownerContactID', 'type' => 'contactID', 'required' => false, 'bypass' => 'ownerContactType' ],
//			[
//				'name'     => 'ownerContactType',
//				'type'     => 'list',
//				'required' => false,
//				'bypass'   => 'ownerContactID',
//				'list'     => [ 'individual', 'organization' ]
//			],
//			[
//				'name'     => 'ownerContactFirstName',
//				'type'     => 'string',
//				'required' => false,
//				'bypass'   => 'ownerContactID'
//			],
//			[ 'name' => 'ownerContactLastName', 'type' => 'string', 'required' => false, 'bypass' => 'ownerContactID' ],
//			[ 'name' => 'ownerContactOrgName', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'ownerContactOrgType', 'type' => 'string', 'required' => false ],
//			[
//				'name'     => 'ownerContactIdentNumber',
//				'type'     => 'string',
//				'required' => false,
//				'bypass'   => 'ownerContactID'
//			],
//			[ 'name' => 'ownerContactEmail', 'type' => 'email', 'required' => false, 'bypass' => 'ownerContactID' ],
//			[ 'name' => 'ownerContactPhone', 'type' => 'phone', 'required' => false, 'bypass' => 'ownerContactID' ],
//			[ 'name' => 'ownerContactFax', 'type' => 'phone', 'required' => false ],
//			[ 'name' => 'ownerContactAddress', 'type' => 'string', 'required' => false, 'bypass' => 'ownerContactID' ],
//			[
//				'name'     => 'ownerContactPostalCode',
//				'type'     => 'string',
//				'required' => false,
//				'bypass'   => 'ownerContactID'
//			],
//			[ 'name' => 'ownerContactCity', 'type' => 'string', 'required' => false, 'bypass' => 'ownerContactID' ],
//			[ 'name' => 'ownerContactState', 'type' => 'string', 'required' => false, 'bypass' => 'ownerContactID' ],
//			[ 'name' => 'ownerContactCountry', 'type' => 'string', 'required' => false, 'bypass' => 'ownerContactID' ],
//
//			[ 'name' => 'adminContactID', 'type' => 'contactID', 'required' => false ],
//			[
//				'name'     => 'adminContactType',
//				'type'     => 'list',
//				'required' => false,
//				'list'     => [ 'individual', 'organization' ]
//			],
//			[ 'name' => 'adminContactFirstName', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'adminContactLastName', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'adminContactOrgName', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'adminContactOrgType', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'adminContactIdentNumber', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'adminContactEmail', 'type' => 'email', 'required' => false ],
//			[ 'name' => 'adminContactPhone', 'type' => 'phone', 'required' => false ],
//			[ 'name' => 'adminContactFax', 'type' => 'phone', 'required' => false ],
//			[ 'name' => 'adminContactAddress', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'adminContactPostalCode', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'adminContactCity', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'adminContactState', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'adminContactCountry', 'type' => 'string', 'required' => false ],
//
//			[ 'name' => 'techContactID', 'type' => 'contactID', 'required' => false ],
//			[
//				'name'     => 'techContactType',
//				'type'     => 'list',
//				'required' => false,
//				'list'     => [ 'individual', 'organization' ]
//			],
//			[ 'name' => 'techContactFirstName', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'techContactLastName', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'techContactOrgName', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'techContactOrgType', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'techContactIdentNumber', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'techContactEmail', 'type' => 'email', 'required' => false ],
//			[ 'name' => 'techContactPhone', 'type' => 'phone', 'required' => false ],
//			[ 'name' => 'techContactFax', 'type' => 'phone', 'required' => false ],
//			[ 'name' => 'techContactAddress', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'techContactPostalCode', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'techContactCity', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'techContactState', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'techContactCountry', 'type' => 'string', 'required' => false ],
//
//			[ 'name' => 'billingContactID', 'type' => 'contactID', 'required' => false ],
//			[
//				'name'     => 'billingContactType',
//				'type'     => 'list',
//				'required' => false,
//				'list'     => [ 'individual', 'organization' ]
//			],
//			[ 'name' => 'billingContactFirstName', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'billingContactLastName', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'billingContactOrgName', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'billingContactOrgType', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'billingContactIdentNumber', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'billingContactEmail', 'type' => 'email', 'required' => false ],
//			[ 'name' => 'billingContactPhone', 'type' => 'phone', 'required' => false ],
//			[ 'name' => 'billingContactFax', 'type' => 'phone', 'required' => false ],
//			[ 'name' => 'billingContactAddress', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'billingContactPostalCode', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'billingContactCity', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'billingContactState', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'billingContactCountry', 'type' => 'string', 'required' => false ],
//
//			[ 'name' => 'nameservers', 'type' => 'string', 'required' => false ],
//			[ 'name' => 'transferBlock', 'type' => 'boolean', 'required' => false ],
//			[ 'name' => 'block', 'type' => 'boolean', 'required' => false ],
//			[ 'name' => 'whoisPrivacy', 'type' => 'boolean', 'required' => false ],
//			[ 'name' => 'viewWhois', 'type' => 'boolean', 'required' => false ],
//			[
//				'name'     => 'renewalMode',
//				'type'     => 'list',
//				'required' => false,
//				'list'     => [ 'autorenew', 'manual', 'letexpire' ]
//			],
//			[ 'name' => 'tag', 'type' => 'string', 'required' => false ]
//		];

//		return $this->execute( 'domain/update/', $_params, $map );
	}

	/**
	 *
	 * Modify nameservers for a domain.
	 * This method expects an array of nameservers that should look like this:
	 *
	 * $nameservers = array('ns1.dns.com', 'ns2.dns.com)
	 *
	 * @link
	 *
	 * @param string $domain Domain name or Domain ID to be modified
	 * @param array $nameservers Array containing the nameservers
	 *
	 * @return Response
	 */
	protected function updateNameServers( $domain, array $nameservers = [] ) {
		$nameservers = array_map( function ( $nameserver ) {
			// Crear DNS server https://domain.apitool.info/help/index.html#api-DNS-CreateDnsServer
			// Obtener códigos
			if ( empty( $nameserver ) ) {
				return null;
			}

			return $this->create_new_dns( $nameserver )->get( 'code' );
		}, $nameservers );
		// Actualizar dominio con los nuevos dns servers: https://domain.apitool.info/help/index.html#api-Domains-ChangeDnsServers
		$_params = [
			'primary_server_code'    => $nameservers[0] ?? '',
			'secondary_server_code'  => $nameservers[1] ?? '',
			'additional_server_code' => array_filter( [
				$nameservers[2] ?? null,
				$nameservers[3] ?? null,
				$nameservers[4] ?? null,
			] ),
		];

		return $this->execute( 'domains/' . $domain . '/dns_servers', $_params, [], 'PUT' );
	}

	/**
	 *
	 * Modify contact information for a domain.
	 * Accepts an associative array with the following parameters:
	 *
	 * ! = required
	 * - owner            array        Associative array of owner contact information.
	 * - admin            array        Associative array of administrator contact infromation.
	 * - tech            array        Associative array of technical contact information.
	 * - billing        array        Associative array of billing contact information.
	 *
	 * @link
	 *
	 * @param string $domain Domain name or Domain ID to be modified
	 * @param array $args Associative array of parameters
	 *
	 * @return Response
	 */
	protected function updateContacts( $domain, array $args = [] ) {
		// throw new \Exception( __CLASS__ . ' ' . __FUNCTION__ . ' Not implemented' );

		$_params = array_merge( $this->getDomainOrDomainID( $domain ), $this->flattenContacts( $args ) );

		$map = [
			[ 'name' => 'domain', 'type' => 'domain', 'required' => true, 'bypass' => 'domainID' ],
			[ 'name' => 'domainID', 'type' => 'string', 'required' => true, 'bypass' => 'domain' ],

			[ 'name' => 'ownerContactID', 'type' => 'contactID', 'required' => false, 'bypass' => 'ownerContactType' ],
			[
				'name'     => 'ownerContactType',
				'type'     => 'list',
				'required' => false,
				'bypass'   => 'ownerContactID',
				'list'     => [ 'individual', 'organization' ]
			],
			[
				'name'     => 'ownerContactFirstName',
				'type'     => 'string',
				'required' => false,
				'bypass'   => 'ownerContactID'
			],
			[ 'name' => 'ownerContactLastName', 'type' => 'string', 'required' => false, 'bypass' => 'ownerContactID' ],
			[ 'name' => 'ownerContactOrgName', 'type' => 'string', 'required' => false ],
			[ 'name' => 'ownerContactOrgType', 'type' => 'string', 'required' => false ],
			[
				'name'     => 'ownerContactIdentNumber',
				'type'     => 'string',
				'required' => false,
				'bypass'   => 'ownerContactID'
			],
			[ 'name' => 'ownerContactEmail', 'type' => 'email', 'required' => false, 'bypass' => 'ownerContactID' ],
			[ 'name' => 'ownerContactPhone', 'type' => 'phone', 'required' => false, 'bypass' => 'ownerContactID' ],
			[ 'name' => 'ownerContactFax', 'type' => 'phone', 'required' => false ],
			[ 'name' => 'ownerContactAddress', 'type' => 'string', 'required' => false, 'bypass' => 'ownerContactID' ],
			[
				'name'     => 'ownerContactPostalCode',
				'type'     => 'string',
				'required' => false,
				'bypass'   => 'ownerContactID'
			],
			[ 'name' => 'ownerContactCity', 'type' => 'string', 'required' => false, 'bypass' => 'ownerContactID' ],
			[ 'name' => 'ownerContactState', 'type' => 'string', 'required' => false, 'bypass' => 'ownerContactID' ],
			[ 'name' => 'ownerContactCountry', 'type' => 'string', 'required' => false, 'bypass' => 'ownerContactID' ],

			[ 'name' => 'adminContactID', 'type' => 'contactID', 'required' => false ],
			[
				'name'     => 'adminContactType',
				'type'     => 'list',
				'required' => false,
				'list'     => [ 'individual', 'organization' ]
			],
			[ 'name' => 'adminContactFirstName', 'type' => 'string', 'required' => false ],
			[ 'name' => 'adminContactLastName', 'type' => 'string', 'required' => false ],
			[ 'name' => 'adminContactOrgName', 'type' => 'string', 'required' => false ],
			[ 'name' => 'adminContactOrgType', 'type' => 'string', 'required' => false ],
			[ 'name' => 'adminContactIdentNumber', 'type' => 'string', 'required' => false ],
			[ 'name' => 'adminContactEmail', 'type' => 'email', 'required' => false ],
			[ 'name' => 'adminContactPhone', 'type' => 'phone', 'required' => false ],
			[ 'name' => 'adminContactFax', 'type' => 'phone', 'required' => false ],
			[ 'name' => 'adminContactAddress', 'type' => 'string', 'required' => false ],
			[ 'name' => 'adminContactPostalCode', 'type' => 'string', 'required' => false ],
			[ 'name' => 'adminContactCity', 'type' => 'string', 'required' => false ],
			[ 'name' => 'adminContactState', 'type' => 'string', 'required' => false ],
			[ 'name' => 'adminContactCountry', 'type' => 'string', 'required' => false ],

			[ 'name' => 'techContactID', 'type' => 'contactID', 'required' => false ],
			[
				'name'     => 'techContactType',
				'type'     => 'list',
				'required' => false,
				'list'     => [ 'individual', 'organization' ]
			],
			[ 'name' => 'techContactFirstName', 'type' => 'string', 'required' => false ],
			[ 'name' => 'techContactLastName', 'type' => 'string', 'required' => false ],
			[ 'name' => 'techContactOrgName', 'type' => 'string', 'required' => false ],
			[ 'name' => 'techContactOrgType', 'type' => 'string', 'required' => false ],
			[ 'name' => 'techContactIdentNumber', 'type' => 'string', 'required' => false ],
			[ 'name' => 'techContactEmail', 'type' => 'email', 'required' => false ],
			[ 'name' => 'techContactPhone', 'type' => 'phone', 'required' => false ],
			[ 'name' => 'techContactFax', 'type' => 'phone', 'required' => false ],
			[ 'name' => 'techContactAddress', 'type' => 'string', 'required' => false ],
			[ 'name' => 'techContactPostalCode', 'type' => 'string', 'required' => false ],
			[ 'name' => 'techContactCity', 'type' => 'string', 'required' => false ],
			[ 'name' => 'techContactState', 'type' => 'string', 'required' => false ],
			[ 'name' => 'techContactCountry', 'type' => 'string', 'required' => false ],

			[ 'name' => 'billingContactID', 'type' => 'contactID', 'required' => false ],
			[
				'name'     => 'billingContactType',
				'type'     => 'list',
				'required' => false,
				'list'     => [ 'individual', 'organization' ]
			],
			[ 'name' => 'billingContactFirstName', 'type' => 'string', 'required' => false ],
			[ 'name' => 'billingContactLastName', 'type' => 'string', 'required' => false ],
			[ 'name' => 'billingContactOrgName', 'type' => 'string', 'required' => false ],
			[ 'name' => 'billingContactOrgType', 'type' => 'string', 'required' => false ],
			[ 'name' => 'billingContactIdentNumber', 'type' => 'string', 'required' => false ],
			[ 'name' => 'billingContactEmail', 'type' => 'email', 'required' => false ],
			[ 'name' => 'billingContactPhone', 'type' => 'phone', 'required' => false ],
			[ 'name' => 'billingContactFax', 'type' => 'phone', 'required' => false ],
			[ 'name' => 'billingContactAddress', 'type' => 'string', 'required' => false ],
			[ 'name' => 'billingContactPostalCode', 'type' => 'string', 'required' => false ],
			[ 'name' => 'billingContactCity', 'type' => 'string', 'required' => false ],
			[ 'name' => 'billingContactState', 'type' => 'string', 'required' => false ],
			[ 'name' => 'billingContactCountry', 'type' => 'string', 'required' => false ]
		];
		// Crear 3 contactos: owner, admin, tech
		$owner_code = $this->crear_contacto( $this->get_array_contact( $_params, 'owner', 1 ) );
		$admin_code = $this->crear_contacto( $this->get_array_contact( $_params, 'admin', 2 ) );
		$tech_code  = $this->crear_contacto( $this->get_array_contact( $_params, 'tech', 2 ) );

		$data = [
			'domain'          => $_params['domain'],
			'registrant_code' => $owner_code->get( 'code' ),
			'admin_code'      => $admin_code->get( 'code' ),
			'tech_code'       => $tech_code->get( 'code' ),
		];

		return $this->execute( 'domains/' . $_params['domain'] . '/data', $data, [], 'PUT' );
	}

	/**
	 * HACER DOMAIN glueRecordCreate
	 *
	 * Creates a DNS record associated to a domain (gluerecord).
	 * Accepts an associative array with the following parameters:
	 *
	 * ! = required
	 * ! ipv4            IPv4        IPv4 address for the DNS server
	 * - ipv6            IPv6        IPv6 address for the DNS server
	 *
	 * @link
	 *
	 * @param string $domain Domain name or Domain ID to be modified
	 * @param string $name Name of the gluerecord to be created
	 * @param array $args Associative array of parameters
	 *
	 * @return Response
	 */
	protected function glueRecordCreate( $domain, array $args = [] ) {
		$_params = array_merge( $this->getDomainOrDomainID( $domain ), $args );

		$map = [
			[ 'name' => 'domain', 'type' => 'domain', 'required' => true, 'bypass' => 'domainID' ],
			[ 'name' => 'domainID', 'type' => 'string', 'required' => true, 'bypass' => 'domain' ],
			[ 'name' => 'name', 'type' => 'string', 'required' => true ],
			[ 'name' => 'ipv4', 'type' => 'ipv4', 'required' => true ],
			[ 'name' => 'ipv6', 'type' => 'ipv6', 'required' => false ]
		];

		return $this->execute( 'domain/gluerecordcreate/', $_params, $map );
	}

	/**
	 * HACER DOMAIN glueRecordUpdate
	 *
	 * Modifies an existing gluerecord for a domain.
	 * Accepts an associative array with the following parameters:
	 *
	 * ! = required
	 * ! ipv4            IPv4        IPv4 address for the DNS server
	 * - ipv6            IPv6        IPv6 address for the DNS server
	 *
	 * @link
	 *
	 * @param string $domain Domain name or Domain ID to be modified
	 * @param string $name Name of the gluerecord to be updated
	 * @param array $args Associative array of parameters
	 *
	 * @return Response
	 */
	protected function glueRecordUpdate( $domain, array $args = [] ) {
		$_params = array_merge( $this->getDomainOrDomainID( $domain ), $args );

		$map = [
			[ 'name' => 'domain', 'type' => 'domain', 'required' => true, 'bypass' => 'domainID' ],
			[ 'name' => 'domainID', 'type' => 'string', 'required' => true, 'bypass' => 'domain' ],
			[ 'name' => 'name', 'type' => 'string', 'required' => true ],
			[ 'name' => 'ipv4', 'type' => 'ipv4', 'required' => true ],
			[ 'name' => 'ipv6', 'type' => 'ipv6', 'required' => false ]
		];

		return $this->execute( 'domain/gluerecordupdate/', $_params, $map );
	}

	/**
	 * HACER DOMAIN glueRecordDelete
	 *
	 * Deletes an existing gluerecord for a domain.
	 *
	 * @link
	 *
	 * @param string $domain Domain name or Domain ID to be modified
	 * @param string $name Name of the gluerecord to be deleted
	 *
	 * @return Response
	 */
	protected function glueRecordDelete( $domain, array $args = [] ) {
		$_params = array_merge( $this->getDomainOrDomainID( $domain ), $args );

		$map = [
			[ 'name' => 'domain', 'type' => 'domain', 'required' => true, 'bypass' => 'domainID' ],
			[ 'name' => 'domainID', 'type' => 'string', 'required' => true, 'bypass' => 'domain' ],
			[ 'name' => 'name', 'type' => 'string', 'required' => true ]
		];

		return $this->execute( 'domain/gluerecorddelete/', $_params, $map );
	}

	/**
	 * HACER DOMAIN getList
	 *
	 * List the domains in the account, filtered by various parameters.
	 * Accepts an associative array with the following parameters:
	 *
	 * ! = required
	 * - pageLength        integer        Max results (defaults to 1000)
	 * - page            integer        Number of the page to get (defaults to 1)
	 * - domain            string        Exact domain name to find
	 * - word            string        Filter the list by this string
	 * - tld            string        Filter list by this TLD
	 * - renewable        boolean        Set to true to get only renewable domains
	 * - infoType        string        Type of information to get. Accepted values:
	 *                                status, contact, nameservers, authcode, service, gluerecords.
	 *
	 * @link
	 *
	 * @param array $args Associative array of parameters
	 *
	 * @return Response
	 */
	protected function getList( array $args = [] ) {
		$_params = $args;

		$map = [
			[ 'name' => 'pageLength', 'type' => 'integer', 'required' => false ],
			[ 'name' => 'page', 'type' => 'integer', 'required' => false ],
			[ 'name' => 'domain', 'type' => 'domain', 'required' => false ],
			[ 'name' => 'word', 'type' => 'string', 'required' => false ],
			[ 'name' => 'tld', 'type' => 'string', 'required' => false ],
			[ 'name' => 'renewable', 'type' => 'boolean', 'required' => false ],
			[
				'name'     => 'infoType',
				'type'     => 'list',
				'required' => false,
				'list'     => [ 'status', 'contact', 'nameservers', 'service', 'gluerecords' ]
			],
			[ 'name' => 'owner', 'type' => 'string', 'required' => false ],
			[ 'name' => 'tag', 'type' => 'string', 'required' => false ],
			[ 'name' => 'status', 'type' => 'string', 'required' => false ],
			[
				'name'     => 'ownerverification',
				'type'     => 'list',
				'required' => false,
				'list'     => [ 'verified', 'notapplicable', 'inprocess', 'failed' ]
			]
		];

		return $this->execute( 'domain/list/', $_params, $map );
	}

	/**
	 * Get information from a domain in the account.
	 * Accepts an associative array with the following parameters:
	 *
	 * ! = required
	 * - infoType        string        Type of information to get. Accepted values:
	 *                                status, contact, nameservers, authcode, service, gluerecords, dnssec.
	 *
	 * @link
	 *
	 * @param string $domain Domain name or Domain ID to get the information from
	 * @param array $args Associative array of parameters
	 *
	 * @return Response
	 */
	protected function getInfo( $domain, array $args = [] ) {
		$_params = array_merge( $this->getDomainOrDomainID( $domain ), $args );

		$map = [
			[ 'name' => 'domain', 'type' => 'domain', 'required' => true, 'bypass' => 'domainID' ],
			[ 'name' => 'domainID', 'type' => 'string', 'required' => true, 'bypass' => 'domain' ],
			[
				'name'     => 'infoType',
				'type'     => 'list',
				'required' => true,
				'list'     => [ 'status', 'contact', 'nameservers', 'authcode', 'service', 'gluerecords', 'dnssec' ]
			]
		];

		$this->domain_info[ $_params['domain'] ] =
			$this->domain_info[ $_params['domain'] ] ?? $this->execute( 'domains/' . $_params['domain'], $_params, $map,
				'GET' );

		if ( isset( $_params['infoType'] ) && 'contact' === $_params['infoType'] ) {
			// Extraer datos de contactos.
			// "registrant_code":"SROW-4538215","admin_code":"SRCO-6286374","tech_code":"SRCO-6286469"
			// Type of contact to be associated as. 1 - registrant, 2 - administrative, 3 - technical.
			$this->domain_info[ $_params['domain'] ]->set(
				'contactOwner',
				$this->getContact( $this->domain_info[ $_params['domain'] ]->get( 'registrant_code' ) )->getResponseData()
			);
			$this->domain_info[ $_params['domain'] ]->set(
				'contactAdmin',
				$this->getContact( $this->domain_info[ $_params['domain'] ]->get( 'admin_code' ) )->getResponseData()
			);
			$this->domain_info[ $_params['domain'] ]->set(
				'contactTech',
				$this->getContact( $this->domain_info[ $_params['domain'] ]->get( 'tech_code' ) )->getResponseData()
			);
		}

		return $this->domain_info[ $_params['domain'] ];
	}

	/**
	 * Buscar dominio.
	 *
	 * Search for suggestion of domain
	 *
	 * @link
	 *
	 * @param string $domain Domain name or Domain ID to get the authcode for
	 *
	 * @return Response
	 * @throws \Exception
	 */
	protected function domainSuggests( $domain ) {

		/**
			[query] => ticihdodi
			[language] => es
			[tlds] => com,net,org,biz,info,eu
		 */
		$data = [
			'sld' => $domain['query'],
		];

		return $this->execute( 'domains/check_availability?' . http_build_query( $data ) );

//		return $this->output( $servers );
	}

	/**
	 * HACER DOMAIN getAuthCode
	 *
	 * Get the authcode for a domain in the account.
	 *
	 * @link
	 *
	 * @param string $domain Domain name or Domain ID to get the authcode for
	 *
	 * @return Response
	 * @throws \Exception
	 */
	protected function getAuthCode( $domain ) {
        return new \Arsys\API\Response\Response(
            '',
            $this->master->getOption( 'response' )
        );
		throw new \Exception( __CLASS__ . ' ' . __FUNCTION__ . ' Not supported' );
		$_params = $this->getDomainOrDomainID( $domain );

		$map = [
			[ 'name' => 'domain', 'type' => 'domain', 'required' => true, 'bypass' => 'domainID' ],
			[ 'name' => 'domainID', 'type' => 'string', 'required' => true, 'bypass' => 'domain' ],
		];

		return $this->execute( 'domain/getauthcode/', $_params, $map );
	}

	/**
	 * Get the nameservers for a domain in the account.
	 *
	 * @link
	 *
	 * @param string $domain Domain name or Domain ID to get the nameservers for
	 *
	 * @return Response
	 */
	protected function getNameServers( $domain ) {
		$_params = $this->getDomainOrDomainID( $domain );

		$domain  = $this->domain_info[ $_params['domain'] ] ?? $this->getInfo( $_params['domain'] );
		$servers = $domain->get( 'dns_server_codes' ) ?? [];
		if ( empty( $servers ) ) {
			return null;
		}
		$servers = array_reduce( $servers, function ( $carry, $server ) {
			if ( empty( $carry ) ) {
				$carry = [];
			}
			$this->name_servers[ $server ] =
				$this->name_servers[ $server ] ?? $this->execute( 'dns/' . $server );
			$carry[]                       = $this->name_servers[ $server ]->getResponseData();

			return $carry;
		} );

		return $this->output( json_encode( $servers ) );
	}

	/**
	 * HACER DOMAIN getGlueRecords
	 *
	 * Get the gluerecords for a domain in the account.
	 *
	 * @link
	 *
	 * @param string $domain Domain name or Domain ID to get the gluerecords for
	 *
	 * @return Response
	 */
	protected function getGlueRecords( $domain ) {
		$_params = $this->getDomainOrDomainID( $domain );

		$map = [
			[ 'name' => 'domain', 'type' => 'domain', 'required' => true, 'bypass' => 'domainID' ],
			[ 'name' => 'domainID', 'type' => 'string', 'required' => true, 'bypass' => 'domain' ],
		];

		return $this->execute( 'domain/getgluerecords/', $_params, $map );
	}

	/**
	 * HACER DOMAIN getDnsSec
	 *
	 * Retrieve the DNSSEC entries associated with a domain
	 *
	 * @link
	 *
	 * @param string $domain Domain name or Domain ID to get the dnssec for
	 *
	 * @return Response
	 */
	protected function getDnsSec( $domain ) {
		$_params = $this->getDomainOrDomainID( $domain );

		$map = [
			[ 'name' => 'domain', 'type' => 'domain', 'required' => true, 'bypass' => 'domainID' ],
			[ 'name' => 'domainID', 'type' => 'string', 'required' => true, 'bypass' => 'domain' ],
		];

		return $this->execute( 'domain/getdnssec/', $_params, $map );
	}

	/**
	 * HACER DOMAIN dnsSecCreate
	 *
	 * Creates a DNSSEC entry for the specified domain.
	 * Accepts an associative array with the following parameters:
	 *
	 * ! = required
	 * ! keytag        integer        Keytag for the DNSSEC entry
	 * ! algorithm    integer        Algorithm to use for the DNSSEC entry
	 * ! digesttype    integer        Type of digest to use for the DNSSEC entry
	 * ! digest        string        Digest for the DNSSEC entry
	 *
	 * @link
	 *
	 * @param string $domain Domain name or Domain ID to which attach the DNSSEC entry
	 * @param array $args Associative array of parameters
	 *
	 * @return Response
	 */
	protected function dnsSecCreate( $domain, array $args = [] ) {
		$_params = array_merge( $this->getDomainOrDomainID( $domain ), $args );

		$map = [
			[ 'name' => 'domain', 'type' => 'domain', 'required' => true, 'bypass' => 'domainID' ],
			[ 'name' => 'domainID', 'type' => 'string', 'required' => true, 'bypass' => 'domain' ],
			[ 'name' => 'keytag', 'type' => 'integer', 'required' => true ],
			[ 'name' => 'algorithm', 'type' => 'integer', 'required' => true ],
			[ 'name' => 'digesttype', 'type' => 'integer', 'required' => true ],
			[ 'name' => 'digest', 'type' => 'string', 'required' => true ],
		];

		return $this->execute( 'domain/dnsseccreate/', $_params, $map );
	}

	/**
	 * HACER DOMAIN dnsSecDelete
	 *
	 * Deletes an existing DNSSEC entry in the specified domain.
	 * Accepts an associative array with the following parameters:
	 *
	 * ! = required
	 * ! name        string        Name of the DNS server associated with the entry
	 * ! keytag        integer        Keytag for the DNSSEC entry
	 * ! algorithm    integer        Algorithm to use for the DNSSEC entry
	 * ! digesttype    integer        Type of digest to use for the DNSSEC entry
	 * ! digest        string        Digest for the DNSSEC entry
	 *
	 * @link
	 *
	 * @param string $domain Domain name or Domain ID containing the DNSSEC entry
	 * @param array $args Associative array of parameters
	 *
	 * @return Response
	 */
	protected function dnsSecDelete( $domain, array $args = [] ) {
		$_params = array_merge( $this->getDomainOrDomainID( $domain ), $args );

		$map = [
			[ 'name' => 'domain', 'type' => 'domain', 'required' => true, 'bypass' => 'domainID' ],
			[ 'name' => 'domainID', 'type' => 'string', 'required' => true, 'bypass' => 'domain' ],
			[ 'name' => 'name', 'type' => 'string', 'required' => true ],
			[ 'name' => 'keytag', 'type' => 'integer', 'required' => true ],
			[ 'name' => 'algorithm', 'type' => 'integer', 'required' => true ],
			[ 'name' => 'digesttype', 'type' => 'integer', 'required' => true ],
			[ 'name' => 'digest', 'type' => 'string', 'required' => true ],
		];

		return $this->execute( 'domain/dnssecdelete/', $_params, $map );
	}

	/**
	 *
	 * Attempts to renew a domain in the account.
	 * Accepts an associative array with the following parameters:
	 *
	 * ! = required
	 * - period        integer        Number of years to renew the domain for
	 *
	 * @link
	 *
	 * @param string $domain Domain name or Domain ID to renew
	 * @param string $curExpDate Current expiration date for this domain
	 * @param array $args Associative array of parameters
	 *
	 * @return Response
	 */
	protected function renew( $domain, array $args = [] ) {
		$_params = [
			'duration' => ( $args['period'] > 0 && $args['period'] < 6 ) ? $args['period'] : 1,
		];

		return $this->execute( 'domains/' . $domain . '/renew', $_params, [], 'PUT' );
	}

	/**
	 * HACER DOMAIN whois
	 *
	 * Performs a whois lookup for a domain name.
	 * Returns whois data for a domain in a single string field. By default,
	 * only domains on the user account can be queried.
	 *
	 * @link
	 *
	 * @param string $domain Domain name to be queried
	 *
	 * @return Response
	 */
	protected function whois( $domain ) {
		$_params = [ 'domain' => $domain ];

		$map = [
			[ 'name' => 'domain', 'type' => 'domain', 'required' => true ]
		];

		return $this->execute( 'domain/whois/', $_params, $map );
	}

	/**
	 * HACER DOMAIN resendVerificationMail
	 *
	 * Resends the contact data verification email.
	 *
	 * @link
	 *
	 * @param string $domain Domain or Domain ID to send the verification email for
	 *
	 * @return Response
	 */
	protected function resendVerificationMail( $domain ) {
		$_params = $this->getDomainOrDomainID( $domain );

		$map = [
			[ 'name' => 'domain', 'type' => 'domain', 'required' => true, 'bypass' => 'domainID' ],
			[ 'name' => 'domainID', 'type' => 'string', 'required' => true, 'bypass' => 'domain' ],
		];

		return $this->execute( 'domain/resendverificationmail/', $_params, $map );
	}

	/**
	 * HACER DOMAIN resendFOAMail
	 *
	 * Resends the FOA authorization email to the owner contact of a domain.
	 *
	 * @link
	 *
	 * @param string $domain Domain or Domain ID to send the verification mail for
	 *
	 * @return Response
	 */
	protected function resendFOAMail( $domain ) {
		$_params = $this->getDomainOrDomainID( $domain );

		$map = [
			[ 'name' => 'domain', 'type' => 'domain', 'required' => true, 'bypass' => 'domainID' ],
			[ 'name' => 'domainID', 'type' => 'string', 'required' => true, 'bypass' => 'domain' ],
		];

		return $this->execute( 'domain/resendfoamail/', $_params, $map );
	}

	/**
	 * HACER DOMAIN resetFOA
	 *
	 * Resets the domain authorization process (only for domains with transfer in process)
	 *
	 * @link
	 *
	 * @param string $domain Domain or Domain ID to send the verification mail for
	 *
	 * @return Response
	 */
	protected function resetFOA( $domain ) {
		$_params = $this->getDomainOrDomainID( $domain );

		$map = [
			[ 'name' => 'domain', 'type' => 'domain', 'required' => true, 'bypass' => 'domainID' ],
			[ 'name' => 'domainID', 'type' => 'string', 'required' => true, 'bypass' => 'domain' ],
		];

		return $this->execute( 'domain/resetfoa/', $_params, $map );
	}

	/**
	 * HACER DOMAIN getHistory
	 *
	 * Gets the history for a specific domain
	 *
	 * ! = required
	 * - pageLength        integer        Max results (defaults to 1000)
	 * - page            integer        Number of the page to get (defaults to 1)
	 *
	 * @link
	 *
	 * @param string $domain Domain name or Domain ID
	 * @param array $args Associative array of parameters
	 *
	 * @return Response
	 */
	protected function getHistory( $domain, array $args = [] ) {
		$_params = array_merge( $this->getDomainOrDomainID( $domain ), $args );

		$map = [
			[ 'name' => 'domain', 'type' => 'domain', 'required' => true, 'bypass' => 'domainID' ],
			[ 'name' => 'domainID', 'type' => 'string', 'required' => true, 'bypass' => 'domain' ],
			[ 'name' => 'pageLength', 'type' => 'integer', 'required' => false ],
			[ 'name' => 'page', 'type' => 'integer', 'required' => false ]
		];

		return $this->execute( 'domain/gethistory/', $_params, $map );
	}

	/**
	 * HACER DOMAIN listDeleted
	 *
	 * Gets deleted domains list
	 *
	 * ! = required
	 * - pageLength        integer        Max results (defaults to 1000)
	 * - page            integer        Number of the page to get (defaults to 1)
	 *
	 * @link
	 *
	 * @param array $args Associative array of parameters
	 *
	 * @return Response
	 */
	protected function listDeleted( array $args = [] ) {
		$_params = $args;

		$map = [
			[ 'name' => 'pageLength', 'type' => 'integer', 'required' => false ],
			[ 'name' => 'page', 'type' => 'integer', 'required' => false ]
		];

		return $this->execute( 'domain/listdeleted/', $_params, $map );
	}

	/**
	 * Check whether the domain is a domain name or a domain ID.
	 *
	 * Some calls can be provided with a domain name or a domain ID. To simplify
	 * methods, we only ask for one parameter, and we try to identify if it's a
	 * Domain Name or a Domain ID.
	 *
	 * If we can find a dot (.) inside $domain, then it's a Domain Name. If not,
	 * we assume it's a Domain ID.
	 *
	 * This method returns an array ready to be passed to any API call or to be
	 * merged with more parameters.
	 *
	 * @param string $domain Domain name or Domain ID
	 *
	 * @return array
	 */
	protected function getDomainOrDomainID( $domain ) {
		if ( strpos( $domain, '.' ) ) {
			return [ 'domain' => $domain ];
		}

		return [ 'domainID' => $domain ];
	}

	/**
	 * @param string $contactId
	 *
	 * @return Response
	 */
	private function getContact( string $contactId ): Response {
		// Obtener un contacto
		$this->contacts[ $contactId ] = $this->contacts[ $contactId ] ?? $this->execute( 'contacts/' . $contactId );

		return $this->contacts[ $contactId ];
	}

	/**
	 * @param array $data
	 *
	 * @return Response
	 */
	private function crear_contacto( array $data ): Response {
		// Obtener un contacto
		return $this->execute( 'contacts', $data, [], 'POST' );
	}

	/**
	 * @param string $domain Dominio
	 * @param bool $lock Si true -> bloquear
	 *
	 */
	protected function updateLockedDomain( string $domain, bool $lock ) {
		$_params = $this->getDomainOrDomainID( $domain );

		return $this->execute(
			'domains/' . $_params['domain'] . '/state',
			[ 'action' => $lock ? 'LOCK' : 'UNLOCK' ],
			[],
			'PUT'
		);
	}

	/**
	 * Crear DNS Server
	 *
	 * @param string $nameserver Name server
	 * @param string $serverip Server IP
	 *
	 * @return array|Response|Arsys\API\Response\Response|string
	 */
	protected function create_new_dns( string $nameserver, string $serverip = '' ) {
		if ( '' == $serverip ) {
			$serverip = gethostbyname( $nameserver );
		}

		return $this->execute(
			'dns',
			[
				'server_name' => $nameserver,
				'server_ip'   => $serverip,
			],
			[],
			'POST'
		);
	}

	/**
	 * Obtener array de contacto para arsys
	 *
	 * @param array $_params Params.
	 * @param string $type Type.
	 * @param int $registrant Registrant Contact type (1 - registrant, 2 - common contact).
	 *
	 * @return array
	 */
	protected function get_array_contact( array $_params, string $type, int $registrant = 2 ): array {
		return array_filter( [
			"type"          => $registrant,
			"name"          => ( $_params[ $type . 'ContactFirstName' ] . ' ' . $_params[ $type . 'ContactLastName' ] ) ?? '',
			"address"       => $_params[ $type . 'ContactAddress' ] ?? '',
			"city"          => $_params[ $type . 'ContactCity' ] ?? '',
			"postal_code"   => $_params[ $type . 'ContactPostalCode' ] ?? '',
			"province"      => $_params[ $type . 'ContactState' ] ?? '',
			"country_code"  => $_params[ $type . 'ContactCountry' ] ?? '',
			"email"         => $_params[ $type . 'ContactEmail' ] ?? '',
			"fiscal_number" => $_params[ $type . 'ContactIdentNumber' ] ?? null,
			"phone"         => str_replace( '.', ' ', $_params[ $type . 'ContactPhone' ] ) ?? '',
		] );
	}

	/**
	 * @param array $_params
	 * @param string $type
	 *
	 * @return Response
	 */
	private function crear_contacto_y_asignar( array $_params, string $type ): ?Response {
		$contact_code = $this->crear_contacto( $this->get_array_contact( $_params, $type, 'owner' === $type ? 1 : 2 ) );
		if ( $contact_code->get( 'code' ) ) {
			$resp = $this->execute(
				'domains/' . $_params['domain'] . '/contacts',
				[
					'contact_code' => $contact_code->get( 'code' ),
					'contact_type' => 'owner' === $type ? 1 : 2
				],
				[],
				'PUT'
			);

			return $resp;
		}

		return null;
	}

	/**
	 * Crear contactos y servernames para el dominio.
	 *
	 * @param string $domain
	 * @param array $args
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function getParamsForTransferOrCreate( string $domain, array $args ): array {
		$_params = array_merge( [
			'domain' => $domain
		], $this->flattenContacts( $args ) );

		// Crear contactos.
		$owner_code = $admin_code = $tech_code = null;
		if ( isset( $_params['ownerContactFirstName'] ) ) {
			$owner_code = $this->crear_contacto( $this->get_array_contact( $_params, 'owner', 1 ) );
			if ( $this->isResponseError( $owner_code ) ) {
				throw new \Exception( 'Error al crear contacto, faltan datos: actualice el formulario de su cuenta' );
			}
		}
		if ( isset( $_params['adminContactFirstName'] ) ) {
			$admin_code = $this->crear_contacto( $this->get_array_contact( $_params, 'admin', 2 ) );
			if ( $this->isResponseError( $admin_code ) ) {
				throw new \Exception( 'Error al crear contacto, faltan datos: actualice el formulario de su cuenta' );
			}
		}
		if ( isset( $_params['techContactFirstName'] ) ) {
			$tech_code = $this->crear_contacto( $this->get_array_contact( $_params, 'tech', 2 ) );
			if ( $this->isResponseError( $tech_code ) ) {
				throw new \Exception( 'Error al crear contacto, faltan datos: actualice el formulario de su cuenta' );
			}
		} else {
			$tech_code = $admin_code;
		}
		// Crear nameservers.
		$nameservers = array_map( function ( $nameserver ) {
			// Crear DNS server https://domain.apitool.info/help/index.html#api-DNS-CreateDnsServer
			// Obtener códigos
			if ( empty( $nameserver ) ) {
				return null;
			}

			return $this->create_new_dns( $nameserver )->get( 'code' );
		}, explode( ',', $_params['nameservers'] ) );

		$nameservers = array_filter( $nameservers );
		if ( empty( $nameservers ) ) {
			throw new \Exception( ' Error al crear los servidores de nombres' );
		}

		return [
			'registrant_code' => $owner_code->get( 'code' ),
			'admin_code'      => $admin_code->get( 'code' ),
			'tech_code'       => $tech_code->get( 'code' ),
			'servers_code'    => $nameservers
		];
	}

	/**
	 * Verificar si es error.
	 *
	 * @param Response $owner_code
	 *
	 * @return bool
	 */
	private function isResponseError( Response $owner_code ): bool {
		return ( $owner_code->get( 'type' ) && 'INTERNAL_ERROR' == $owner_code->get( 'type' ) );
	}
}
