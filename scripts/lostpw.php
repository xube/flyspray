<?php

  /*********************************************************\
  | Deal with lost passwords                                |
  | ~~~~~~~~~~~~~~~~~~~~~~~~                                |
  \*********************************************************/

if(!defined('IN_FS')) {
    die('Do not access this file directly.');
}

$page->setTitle('Flyspray:: ' . L('lostpw'));

if (!Get::has('magic') && $user->isAnon()) {
    // Step One: user requests magic url
    $page->pushTpl('lostpw.step1.tpl');
}
elseif (Get::has('magic') && $user->isAnon()) {
    // Step Two: user enters new password

    $check_magic = $db->Query("SELECT * FROM {users} WHERE magic_url = ?",
            array(Get::val('magic')));

    if (!$db->CountRows($check_magic)) {
        $_SESSION['ERROR'] = L('badmagic');
        $fs->redirect(CreateURL('error'));
    }
    $page->pushTpl('lostpw.step2.tpl');
} else {
    $fs->redirect($baseurl);
}
?>
