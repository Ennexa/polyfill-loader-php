<?php
include __DIR__ . '/../../lib/PolyfillLoader/PolyfillLoader.php';
include __DIR__ . '/../../lib/PolyfillLoader/FilenameFilterIterator.php';

$minify = (bool)(empty($_GET['minify']) ? false : $_GET['minify']);
$flags = explode(',', empty($_GET['flags']) ? '' : $_GET['flags']);
$callback = empty($_GET['callback']) ? null : $_GET['callback'];
$features = preg_split('/\s*,\s*/', empty($_GET['features']) ? 'default-3.5' : $_GET['features']);

header('Content-Type: application/javascript');

$loader = new PolyfillLoader\PolyfillLoader(__DIR__ . '/__dist', $minify, $flags);
$loader->load($features);

if ($callback) {
	$loader->setCallback($callback);
}

$loader->generate();
