<?php

  declare(strict_types=1);

  use Steampixel\Route;

  if ($_SERVER['HTTP_HOST'] !== 'localhost:3000') {
    die('<p>This quickstart is configured to be run from <a href="http://localhost:3000">http://localhost:3000</a>.</p>');
  }

  Route::add('/', function() use ($sdk) {
    require('dashboard.php');
  });

  Route::add('/login', function() use ($sdk) {
    require('login.php');
  });

  Route::add('/callback', function() use ($sdk) {
    require('callback.php');
  });

  Route::add('/logout', function() use ($sdk) {
    require('logout.php');
  });

  Route::add('/apis', function() use ($sdk) {
    require('apis.php');
  });

  Route::add('/webhooks', function() use ($sdk) {
    require('webhooks.php');
  });

  Route::run();