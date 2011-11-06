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

/**
 * Loads a document, applies the modifications and output it.
 * @param string $name The name of the document to load.
 * @todo render a cached version of the modified document.
 */
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
 * @param string $name The name of the document to load.
 * @todo loads existent mods and init javascript to keep track of the original doc.
 * @todo add feature to make the selector used by anyText flexible.
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

/**
 * Saves the posted modifications of a document.
 * @param string $name The name of the document being modified.
 * @todo Send an explicit response (so messages can be displayed client side).
 * @todo generate a plain version of the document integrating the modifications.
 */
$app->post('/transform/:name', function($name) use ($app) {
	$domdoc = Model::factory('DomDoc')->find_one($name);
	if (!$domdoc->loaded())
		$app->notFound();
	$postedMods = $app->request()->post('mods');
	
	$mods = array();
	foreach ($postedMods as $postedMod) {
		$mod = Model::factory('Mod');
		$mod->xpath = $postedMod['selector'];
		$mod->value = $postedMod['value'];
		$mod->mod_type_id = 1;
		$mod->dom_doc_name = $name;
		$mods[] = $mod;
	}
	
	Model::start_transaction();
	$person = ORM::for_table('mod')
		->where_equal('dom_doc_name', $name)
		->delete_many();
	foreach ($mods as $mod)
		$mod->save();
	Model::commit();
});

$app->get('/upload', function() use ($app) {
	$app->render('upload.php', array(
		'action' => $app->request()->getRootUri() . $app->request()->getResourceUri(),
	));
});

$app->post('/upload', function() use ($app) {
	require('./lib/FileUpload.php');
	$fu = new FileUpload('userfile');
	$location = $fu->move(DomDoc::$repo);
	// $zip = new ZipArchive;
	// if ($zip->open($location) === TRUE) {
	// 	$zip->extractTo('/my/destination/dir/');
	// 	$zip->close();
	// 	echo 'ok';
	// } else {
	// 	echo 'failed';
	// }
});

$app->run();
