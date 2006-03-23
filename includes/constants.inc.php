<?php

define('BASEDIR',            realpath(dirname(__FILE__) . '/..')); 

// Change this line if you move flyspray.conf.php elsewhere
$conf    = @parse_ini_file(BASEDIR . '/flyspray.conf.php', true);

// $baseurl
if (substr($baseurl = $conf['general']['baseurl'], -1) != '/') {
    $baseurl .= '/';
}
if (substr($baseurl, 0, 7) != 'http://') {
    $baseurl = 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $baseurl;
}

define('DOKU_PLUGIN',        BASEDIR . '/includes/dokuwiki/lib/plugins/');
define('DOKU_CONF',          BASEDIR . '/includes/dokuwiki/conf/');
define('DOKU_INTERNAL_LINK', $conf['general']['doku_url']);
define('DOKU_BASE',          $baseurl .'includes/dokuwiki/');
define('DOKU_URL',           BASEDIR .'includes/dokuwiki/');

?>
