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

define('NOTIFY_TASK_OPENED',      1);
define('NOTIFY_TASK_CHANGED',     2);
define('NOTIFY_TASK_CLOSED',      3);
define('NOTIFY_TASK_REOPENED',    4);
define('NOTIFY_DEP_ADDED',        5);
define('NOTIFY_DEP_REMOVED',      6);
define('NOTIFY_COMMENT_ADDED',    7);
define('NOTIFY_ATT_ADDED',        8);
define('NOTIFY_REL_ADDED',        9);
define('NOTIFY_OWNERSHIP',       10);
define('NOTIFY_CONFIRMATION',    11);
define('NOTIFY_PM_REQUEST',      12);
define('NOTIFY_PM_DENY_REQUEST', 13);
define('NOTIFY_NEW_ASSIGNEE',    14);
define('NOTIFY_REV_DEP',         15);
define('NOTIFY_REV_DEP_REMOVED', 16);
define('NOTIFY_ADDED_ASSIGNEES', 17);
define('NOTIFY_ANON_TASK',       18);
define('NOTIFY_PW_CHANGE',       19);
  
?>
