<?php
namespace APIGame;

use APIGame\Core;

// \mb_internal_encoding("UTF-8");
error_reporting(-1);

require_once "core/loader.php";
Core\Loader::getInstance();

$api = new API();
$api->addService(new XO\XOGame());
$api->addService(new XO\XOGame(new XO\StupidAI(), 'XOGame/stupid'));
$api->addService(new XO\XOGame(new XO\RandomAI(), 'XOGame/random'));
$api->run();
