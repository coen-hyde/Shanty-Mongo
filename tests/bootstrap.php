<?php

error_reporting(E_ALL | E_STRICT);

$root = dirname(__DIR__);
require_once "$root/vendor/autoload.php";
$path = array(
	"$root/tests",
	get_include_path()
);
set_include_path(implode(PATH_SEPARATOR, $path));

