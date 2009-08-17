<?php
/**
 * Load some XMPP reference documents and create JavaScript objects from them
 */
$remoteUrl = 'http://xmpp.org/registrar/';
$cachePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
$remote = array('disco-features.xml' => 'features', 'disco-categories.xml' => 'categories');

@header('Content-Type: application/javascript');

/**
 * Move features into JavaScript object
 *
 * @param string $filename file containing features description
 *
 * @return string
 */
function features($fileName) {
    $xml = simplexml_load_string(str_replace("\n", " ", file_get_contents($fileName)));
    $res = '';
    foreach ($xml->var as $feature) {
        $res .= sprintf('"%s": {"desc": "%s", "doc": "%s"},', addslashes($feature->name), addslashes($feature->desc), addslashes($feature->doc)) . "\n";
    }
    $res = "features: {\n$res},\n";
    return $res;
}

/**
 * Move categories into JavaScript object
 *
 * @param string $filename file containing categories description
 *
 * @return string
 */
function categories($fileName) {
    $xml = simplexml_load_string(str_replace("\n", " ", file_get_contents($fileName)));
    $res = '';
    foreach ($xml->category as $category) {
        $res .= sprintf('"%s": {"desc": "%s","type": {', addslashes($category->name), addslashes($category->desc));
        foreach ($category->type as $type) {
            $res .= sprintf('"%s": {"desc": "%s", "doc": "%s"},', addslashes($type->name), addslashes($type->desc), addslashes($type->doc));
        }
        $res .= "}},\n";
    }
    $res = "categories: {\n$res},\n";
    return $res;
}

$res = '';
foreach ($remote as $fileName => $functionName) {
    $filePath = $cachePath . $fileName;
    // save remote files into local cache
    if (!file_exists($filePath) || (time() - filemtime($filePath) > 86400)){
        file_put_contents($filePath, file_get_contents($remoteUrl . $fileName));
    }
    // transform files
    if (function_exists($functionName)) {
        $res .= call_user_func_array($functionName, array($filePath));
    }
}
// Render result
$res = "var gRegistars = {\n$res };";
echo $res;