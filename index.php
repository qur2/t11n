<?php
require 'Slim/Slim.php';
require 'Views/MustacheView.php';
MustacheView::$mustacheDirectory = 'vendor/mustache/';
require_once 'vendor/idiorm/idiorm.php';
require_once 'vendor/dakota/dakota.php';
ORM::configure('sqlite:data/t11n.sqlite');
require_once 'lib/models.php';
DomDoc::$repo = 'data/';

$app = new Slim(array(
	'mode' => 'dev',
	'view' => 'MustacheView',
	'templates.path' => './templates',
));

// prod mode definition
$app->configureMode('prod', function() use ($app) {
	$app->config(array(
		'log.enable' => true,
		'log.path' => './logs',
		'log.level' => 4,
		'debug' => false
	));
});

// dev mode definition
$app->configureMode('dev', function() use ($app) {
	$app->config(array(
		'log.enable' => false,
		'debug' => true
	));
});

$app->get('/', function() use ($app) {
	$domdoc = Model::factory('DomDoc')->find_one('cv.html');
	$basePath = dirname($app->request()->getRootUri());
	$app->render('home.php', array(
		'basePath' => $basePath,
		'domdoc' => $domdoc,
	));
});

$app->get('/page/:name', function($name) use ($app) {
	$domdoc = Model::factory('DomDoc')->find_one($name);
	if (!$domdoc->loaded())
		$app->notFound();
	$mods = Model::factory('Mod')->where('dom_doc_name', $name)->find_many();
	print $domdoc
		->alter($mods)
		->sanitizeAssets($app->request()->getRootUri() . '/')
		->getAlteredContent();
});

/**
 * Loads the original page and inject anyText plugin + its dependencies.
 * TODO loads existent mods and init javascript to keep track of the original doc.
 * TODO add feature to make the selector used by anyText flexible.
 */
$app->get('/transform/:name', function($name) use ($app) {
	$domdoc = Model::factory('DomDoc')->find_one($name);
	if (!$domdoc->loaded())
		$app->notFound();
	// $mods = Model::factory('Mod')->where('dom_doc_name', $name)->find_many();
	require_once 'lib/DomInjector.php';
	$basePath = $app->request()->getRootUri();
	$doc = $domdoc->sanitizeAssets($app->request()->getRootUri() . '/')->newDom;
	$di = new DomInjector($doc);
	$di->append(array(
		'tag' => 'script',
		'type' => 'text/javascript',
		'src' => 'https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js',
	));
	$di->append(array(
		'tag' => 'script',
		'type' => 'text/javascript',
		'src' => $basePath . '/www/jquery.anytext/jquery.anytext.js',
	));
	$di->append(array(
		'tag' => 'link',
		'type' => 'text/css',
		'rel' => 'stylesheet/less',
		'href' => $basePath . '/www/jquery.anytext/jquery.anytext.less',
	));
	$di->append(array(
		'tag' => 'script',
		'type' => 'text/javascript',
		'src' => 'http://lesscss.googlecode.com/files/less-1.1.3.min.js',
	));
	$di->append(array(
		'tag' => 'script',
		'type' => 'text/javascript',
		'value' => "$(document).ready(function() { $('#container').anyText(); });",
	), 'body');
	echo $doc->saveHTML();
});

$app->run();