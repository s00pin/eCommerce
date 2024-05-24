<?php
declare(strict_types=1);
// Import the Composer Autoloader to make the SDK classes accessible:
require 'vendor/autoload.php';

// Load our environment variables from the .env file:
(Dotenv\Dotenv::createImmutable(__DIR__))->load();

// Now instantiate the Auth0 class with our configuration:

use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;

$configuration = new SdkConfiguration(
domain: $_ENV['AUTH0_DOMAIN'],
clientId:  $_ENV['AUTH0_CLIENT_ID'],
clientSecret: $_ENV['AUTH0_CLIENT_SECRET'],
redirectUri: 'http://' . $_SERVER['HTTP_HOST'] . '/callback',
cookieSecret: '4f60eb5de6b5904ad4b8e31d9193e7ea4a3013b476ddb5c259ee9077c05e1457'
);

$sdk = new Auth0($configuration);

require('router.php');