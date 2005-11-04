<?php

  /*********************************************************\
  | User Profile Edition                                    |
  | ~~~~~~~~~~~~~~~~~~~~                                    |
  \*********************************************************/

if ($user->isAnon()) {
    $fs->redirect($fs->createUrl('error'));
}

$fs->get_language_pack('admin');
$page->uses('admin_text');
$page->assign('theuser', $user);
$page->display('myprofile.tpl');

?>
