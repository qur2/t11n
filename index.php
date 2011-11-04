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
	'log.enable' => true,
	'log.path' => './logs',
	'log.level' => 4,
	'view' => 'MustacheView',
	'templates.path' => './templates',
));

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

$app->run();