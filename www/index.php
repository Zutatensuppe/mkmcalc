<?php

require_once __DIR__.'/../include/bootstrap.php';

Header('Content-Type: text/html; charset=utf-8');


use system\Session as Session;
use system\Auth as Auth;
use system\Dispatcher as Dispatcher;

Session::start();
Auth::checkSession();

$dispatcher = new Dispatcher();
$dispatcher->dispatch();

die();