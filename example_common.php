<?php

define("BASE_DIR", getcwd());

function __autoload($class) {
	$class = BASE_DIR."/".str_replace("\\", '/', $class).'.php';
	include_once($class);
}

function delay($obj, $parent = null) {
	return new delayable\DelayedIteratorIterator($obj, $parent);
}
