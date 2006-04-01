<?php

define('BASEDIR', dirname(dirname(__FILE__)));

// Change this line if you move flyspray.conf.php elsewhere
$conf    = @parse_ini_file(BASEDIR . '/flyspray.conf.php', true);

// $baseurl
// htmlspecialchars because PHP_SELF is user submitted data, and can be used as an XSS vector.
$webdir = dirname(htmlspecialchars($_SERVER['PHP_SELF']));

$baseurl = rtrim(Flyspray::absoluteURI($webdir),'/\\') . '/' ;


define('DOKU_PLUGIN',        BASEDIR . '/includes/dokuwiki/lib/plugins/');
define('DOKU_CONF',          BASEDIR . '/includes/dokuwiki/conf/');
define('DOKU_INTERNAL_LINK', $conf['general']['doku_url']);
define('DOKU_BASE',          $baseurl .'includes/dokuwiki/');
define('DOKU_URL',           BASEDIR .'includes/dokuwiki/');

?>
