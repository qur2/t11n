<?php
if (!isset($_GET['path']))
	throw new RuntimeException(sprintf('Please specify a base path (relative to %s) in the get parameters.', basename($_SERVER["SCRIPT_NAME"])));
$file = isset($_GET['file']) ? $_GET['file'] : 'index.html';
$path = $_GET['path'];
if (substr($path, -1) != '/')
	$path .= '/';

// load base html file
$doc = new DOMDocument('1.0', 'UTF-8');
$html = file_get_contents($path . $file);
// @see http://be2.php.net/manual/en/domdocument.loadhtml.php#95251
@$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
foreach ($doc->childNodes as $item)
	if ($item->nodeType == XML_PI_NODE)
		$doc->removeChild($item);
$doc->encoding = 'UTF-8';

// build xpath selector for element having a url to correct
$urlAttributes = array('src', 'href');
$selector = array();
foreach ($urlAttributes as $attr)
	$selector[] = "//*[@{$attr}]";
$xpath = new DOMXpath($doc);
$elements = $xpath->query(join(' | ', $selector));

// correct the urls on the fly
if (!is_null($elements)) {
	foreach ($elements as $el) {
		foreach ($urlAttributes as $attr) {
			if ($el->hasAttribute($attr)) {
				$attrVal = $el->getAttribute($attr);
				if ('#' != $attrVal && 'http' != substr($attrVal, 0, 4))
					$el->setAttribute($attr, $path . $attrVal);
			}
		}
	}
}

// include jQuery, anyText plugin and lesscss
$body = $doc->getElementsByTagName('body')->item(0);
$el = new DOMElement('script');
$body->appendChild($el);
$el->setAttribute('type', 'text/javascript');
$el->setAttribute('src', 'https://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js');
$el = new DOMElement('script');
$body->appendChild($el);
$el->setAttribute('type', 'text/javascript');
$el->setAttribute('src', 'jquery.anytext.js');
$el = new DOMElement('script');
$body->appendChild($el);
$el->setAttribute('type', 'text/javascript');
$el->setAttribute('src', 'http://lesscss.googlecode.com/files/less-1.1.3.min.js');

// include anyText CSS
$head = $doc->getElementsByTagName('head')->item(0);
$el = new DOMElement('link');
$head->appendChild($el);
$el->setAttribute('rel', 'stylesheet/less');
$el->setAttribute('href', 'jquery.anytext.less');
$el->setAttribute('type', 'text/css');

$el = new DOMElement('script');
$body->appendChild($el);
$text = new DOMText("
$(document).ready(function() {  
	  $('#page-wrapper').anyText();
});");
$el->appendChild($text);

print $doc->saveHtml();