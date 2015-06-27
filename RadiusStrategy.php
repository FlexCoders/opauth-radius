<?php
/**
 * Radius strategy for Opauth
 *
 * More information on Opauth: http://opauth.org
 *
 * @copyright    Copyright © 2015 FlexCoders Ltd (http://flexcoders.co.uk)
 * @link         http://flexcoders.co.uk
 * @package      Opauth.RadiusStrategy
 * @license      MIT License
 */

/**
 * Radius strategy for Opauth
 *
 * @package			Opauth.Radius
 */
class RadiusStrategy extends OpauthStrategy{

	/**
	 * Compulsory config keys, listed as unassociative arrays
	 */
	public $expects = array(
		'server',
		'secret',
		'port',
		'acctport',
	);

	/**
	 * Optional config keys, without predefining any default values.
	 */
	public $optionals = array(
		'nasip',
		'level',
		'lower',
		'higher',
	);

	/**
	 * Optional config keys with respective default values, listed as associative arrays
	 * eg. array('scope' => 'email');
	 */
	public $defaults = array(
		'expiry' => 86400,
	);

	/**
	 * radius connection object
	 */
	protected $radius;

	/**
	 * Auth request
	 */
	public function request()
	{
		// bail out if we didn't get a username and password passed
		if (empty($this->env['username']) or empty($this->env['password']))
		{
			$error = array(
				'code' => 'credentials_error',
				'message' => 'Radius user credentials not passed in the request',
				'raw' => array(),
			);

			$this->errorCallback($error);
		}

		// create a radius client instance
		$this->radius = new RadiusClient(
			$this->strategy['server'],
			$this->strategy['secret'],
			'', 10,
			$this->strategy['port'],
			$this->strategy['acctport']
		);

		// some RADIUS servers need this
		if ( ! empty($this->strategy['nasip']))
		{
			$this->radius->SetNasPort(0);
			$this->radius->SetNasIpAddress($this->strategy['nasip']);
		}

		// attempt to login
		if ( ! $this->radius->AccessRequest($this->env['username'], $this->env['password']))
		{
			$error = array(
				'code' => 'Access-Rejected',
				'message' => $this->radius->GetLastError(),
				'raw' => array(),
			);

			$this->errorCallback($error);
		}

		// get the returned attributes
		$attrs = $this->radius->GetParsedReceivedAttributes();

		// construct the response array
		$this->auth = array(
			'uid' => $this->env['username'],
			'info' => array(
				'name' => $this->env['username'],
				'email' => $this->env['username'].'@example.org',
				'nickname' => $this->env['username'],
			),
			'credentials' => array(
				'token' => 0,
				'expires' => date('c', time() + isset($this->strategy['expiry']) ? $this->strategy['expiry'] : 86400)
			),
			'raw' => $attrs,
		);

		// support for Cisco's shell privilege AVpair
		if ( ! empty($this->strategy['level']) and  ! empty($this->strategy['lower']) and  ! empty($this->strategy['higher']))
		{
			foreach ($attrs as $attr)
			{
				if ($attr['Attribute'] == 'Vendor-Specific' and strpos($attr['Attribute-Specific'], 'shell:priv-lvl') === 0)
				{
					$level = explode('=', $attr['Attribute-Specific']);
					if (isset($level[1]))
					{
						if ($level[1] >= $this->strategy['level'])
						{
							$this->auth['info']['group_id'] = $this->strategy['higher'];
						}
						else
						{
							$this->auth['info']['group_id'] = $this->strategy['lower'];
						}
						break;
					}
				}
			}
		}

		// and process the callback
		$this->callback();
	}
}
