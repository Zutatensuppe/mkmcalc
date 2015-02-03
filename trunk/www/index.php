<?php

require_once __DIR__.'/../include/bootstrap.php';

Header('Content-Type: text/html; charset=utf-8');



Session::start();
Auth::checkSession();

$dispatcher = new Dispatcher();
$dispatcher->dispatch();

die();