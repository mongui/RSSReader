<?php

define('MVCious', true);
error_reporting(0);

$type = filter_var($_GET['type'], FILTER_SANITIZE_STRING);
$files = explode(',', filter_var($_GET['files'], FILTER_SANITIZE_STRING));

if (strpos($files[0], '/') !== false) {
	$path = $files[0];
	$files[0] = substr(strrchr($path, '/'), 1);
	$path = chop($path, $files[0]);
}

$modified = 0;

foreach( $files as $file ) {        
	$age = filemtime($path . $file . '.' . $type);

	if ( !$age ) {
		header(':', TRUE, 404);
		die();
	}
	elseif ( $age > $modified ) {
		$modified = $age;
	}
}

$offset = 60 * 60 * 24 * 30 * 6; // Cache for 6 months
header ('Expires: ' . gmdate ('D, d M Y H:i:s', time() + $offset) . ' GMT');

if ( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $modified )
{
	header('HTTP/1.0 304 Not Modified');
	header ('Cache-Control:');
}
else
{
	header ('Cache-Control: max-age=' . $offset);
	header ('Pragma:');
	header ('Last-Modified: ' . gmdate('D, d M Y H:i:s', $modified) . ' GMT');
  
	require '../libraries/minifier.php';

	if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip'))
		ob_start('ob_gzhandler');
	else
		ob_start();

	$minifier = new Minifier();

	if ( isset($type) && is_array($files) )
	{
		foreach ( $files as $file )
			$minifier->add_file($type, $path . $file . '.' . $type);
	}

	if ( $type == 'css' )
	{
		header('Content-type: text/css', true);
		echo $minifier->minify_css();
	}
	elseif ( $type == 'js' )
	{
		header('Content-type: text/javascript', true);
		echo $minifier->minify_js();
	}

}