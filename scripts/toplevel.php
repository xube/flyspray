<?php

  /********************************************************\
  | Task Creation                                          |
  | ~~~~~~~~~~~~~                                          |
  \********************************************************/

if(!defined('IN_FS')) {
    die('Do not access this file directly.');
}



$page->setTitle('Flyspray:: ' . $proj->prefs['project_title'] . ': ' . L('toplevel'));
$page->pushTpl('toplevel.tpl');

?>