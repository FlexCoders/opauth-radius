Opauth-Radius
=============
[Opauth][1] strategy for Radius authentication.

Opauth is a multi-provider authentication framework for PHP.

Getting started
----------------
1. Install Opauth-Radius:
   ```bash
   cd path_to_opauth/Strategy
   git clone git://github.com/flexcoders/opauth-radius.git radius
   ```

2. Configure Opauth-Radius strategy.

3. Call it.

Since this is not an HTTP based protocol, some of the standard Opauth config does not apply.
There is no redirection involved, and a username and password needs to be passed.

You call it like so:
````
// some input vars
$providerName = "Radius";

// prep a config
$config = [
	'provider' => $providerName,
	'username' => $_POST['username'],
	'password' => $_POST['password'],
	'request_uri' => '/current/uri/'.strtolower($providerName),
	'callback_url' => '/your/uri/for/callback/'.strtolower($providerName),
];

// construct the Opauth object
$this->opauth = new \Opauth($config, true);
````

It will attempt a Radius login, and then redirect to the callback url, just like with all other Opauth
strategies, and with a similar response.

Strategy configuration
----------------------

Required parameters:

```php
<?php
'Radius' => array(
    'server'        => '127.0.0.1',
    'secret'        => 'testing123',
    'port'          => 1812,
    'acctport'      => 1813,
    'nasip'         => '1.2.3.4',
    'level'         => 10,
    'higher'        => 6,
    'lower'         => 2,
    'expiry'        => 86400,
)
```

Optional parameters:
`level`, `higher`, `lower`, `expiry`

Support for Cisco's AVPair "shell:priv-lvl":

Altough not strictly part of the authentication process, this library has support for
Cisco's shell privilege level, as used in a lot of large organisations to allow different
levels of administrator access.

It allows you to define a threshold `level`. If the privilege level is greater or equal then
the value defined, the `higher` value will be returned in $this->auth['info']['group_id'],
and if the level is lower, the `lower` value will be returned.

This will allow an Opauth implementation to create user accounts that immediately be used
when the user logs in for the first time because the required set of permissions within the
application is already known.

License
---------
Opauth-Radius is MIT Licensed
Copyright © 2015 FlexCoders Ltd (http://flexcoders.co.uk)

[1]: https://github.com/opauth/opauth
