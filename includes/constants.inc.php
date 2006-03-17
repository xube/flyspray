<?php

define('BASEDIR',            realpath(dirname(__FILE__) . '/..')); 

// Change this line if you move flyspray.conf.php elsewhere
$conf    = @parse_ini_file(BASEDIR . '/flyspray.conf.php', true);

define('DOKU_PLUGIN',        BASEDIR . '/includes/dokuwiki/lib/plugins/');
define('DOKU_CONF',          BASEDIR . '/includes/dokuwiki/conf/');
define('DOKU_INTERNAL_LINK', $conf['general']['doku_url']);
define('DOKU_BASE',          BASEDIR .'includes/dokuwiki/');
define('DOKU_URL',           BASEDIR .'includes/dokuwiki/');

?>