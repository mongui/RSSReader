<?php

define('MVCious', true);
require '../libraries/minifier.php';

if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip'))
	ob_start("ob_gzhandler");
else
	ob_start();

$minifier = new Minifier();

$type = filter_var($_GET['type'], FILTER_SANITIZE_STRING);
$files = explode(',', filter_var($_GET['files'], FILTER_SANITIZE_STRING));

if ( isset($type) && is_array($files) )
{
	foreach ( $files as $file )
		$minifier->add_file($type, $file.'.'.$type );
}

if ( $type == 'css' )
{
	header("Content-type: text/css", true);
	echo $minifier->minify_css();
}
elseif ( $type == 'js' )
{
	header("Content-type: text/javascript", true);
	echo $minifier->minify_js();
}
