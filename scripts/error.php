<?php

  /*********************************************************\
  | Generic error script                                    |
  | ~~~~~~~~~~~~~~~~~~~~                                    |
  | You are usually redirected here if you requested a page |
  | or task that doesn't exist, or if you entered           |
  | an illegal URL and it was caught by a regexp.           |
  \*********************************************************/


$page->setTitle($fs->prefs['page_title'] . ' Error');
$page->pushTpl('error.tpl');
?>
