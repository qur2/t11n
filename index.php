<?php
require 'Slim/Slim.php';
require 'Views/MustacheView.php';
MustacheView::$mustacheDirectory = 'vendor/mustache/';
require_once 'vendor/idiorm/idiorm.php';
require_once 'vendor/dakota/dakota.php';
ORM::configure('sqlite:data/t11n.sqlite');
require_once 'lib/Repo.php';
require_once 'lib/DomDoc.php';
require_once 'lib/ModSet.php';
require_once 'lib/Mod.php';
Repo::$root = 'data/';

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
 * Loads a document, applies a set of modifications and outputs it.
 * @param string $repo The name of the repo where the document lies.
 * @param string $domDoc The name of the document to load.
 * @param string $modSet The id of the modification set to apply to the document.
 * @todo render a cached version of the modified document.
 */
$app->get('/page/:repo/:domDoc(/:modSet)', function($repo, $domDoc, $modSet = null) use ($app) {
	$domDoc = Model::factory('DomDoc')->where('repo_name', $repo)->where('name', $domDoc)->find_one();
	if (!$domDoc->loaded())
		$app->notFound();
	print $domDoc->alter($modSet, $app->request()->getRootUri() . '/')->getContent();
});

/**
 * Loads the original page and inject anyText plugin + its dependencies.
 * @param string $name The name of the document to load.
 * @todo loads existent mods and init javascript to keep track of the original doc.
 * @todo add feature to make the selector used by anyText flexible.
 */
$app->get('/transform/:repo/:domDoc(/:modSet)', function($repo, $domDoc, $modSet = null) use ($app) {
	$domDoc = Model::factory('DomDoc')->where('repo_name', $repo)->find_one($domDoc);
	if (!$domDoc->loaded())
		$app->notFound();
	require_once 'lib/DomInjector.php';
	$basePath = $app->request()->getRootUri();
	$doc = $domDoc->alter(null, $app->request()->getRootUri() . '/')->getDom();
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
$app->post('/transform/:repo/:domDoc(/:modSet)', function($repo, $domDoc, $modSet = null) use ($app) {
	$domdoc = Model::factory('DomDoc')->where('repo_name', $repo)->find_one($domDoc);
	if (!$domdoc->loaded())
		$app->notFound();
	$postedMods = $app->request()->post('mods');
	$data = array();
	foreach ($postedMods as $postedMod) {
		$mod = Model::factory('Mod');
		$mod->xpath = $postedMod['selector'];
		$mod->value = $postedMod['value'];
		$mod->mod_type_id = 1;
		$data[] = $mod;
	}
	if (is_null($modSet))
		$data['dom_doc_id'] = $domDoc;
	else
		$data['mod_set_id'] = $modSet;
	ModSet::buildFromMods($data);
});

/**
 * Loads the file upload page.
 */
$app->get('/repo', function() use ($app) {
	$basePath = $app->request()->getRootUri();
	$app->render('upload.php', array(
		'basePath' => $basePath,
		'action' => $app->request()->getRootUri() . $app->request()->getResourceUri(),
	));
});

/**
 * Handles the file post query.
 * @todo check for dir name collisions (in progress).
 * @todo check what file type is in the uploaded zip.
 */
$app->post('/repo', function() use ($app) {
	require('./lib/FileUpload.php');
	$fu = new FileUpload('userfile');
	try {
		$location = $fu->move(Repo::$root);
		$destination = substr($location, 0, strrpos($location, '.')) . DIRECTORY_SEPARATOR;
		$repo = new Repo;
		$files = $repo->dir->buildFromZip($location, $destination);
		unlink($location);
		$data = array();
		foreach ($files as $file) {
			$domDoc = Model::factory('DomDoc');
			$domDoc->name = $file;
			$data[] = $domDoc;
		}
		$data['repo_name'] = $repo->dir->name;
		DomDoc::buildFromRepoFiles($data);
		$app->flash('succeed', sprintf('Repo successfully  created : %s', $destination));
		$app->redirect(join('/', array($app->request()->getRootUri(), 'page', $domDoc->repo_name, $domDoc->name)));
	} catch (RuntimeException $e) {
		$app->flash('fail', $e->getMessage());
		$app->redirect($app->request()->getRootUri() . '/repo');
	}
});

$app->run();
