<?php

  /*********************************************************\
  | Deal with lost passwords                                |
  | ~~~~~~~~~~~~~~~~~~~~~~~~                                |
  \*********************************************************/

$fs->get_language_pack('admin');
$page->uses('admin_text');

if (!Get::has('magic') && $user->isAnon()) {
    // Step One: user requests magic url
    $page->display('lostpw.step1.tpl');
}
elseif (!Get::has('magic') && $user->isAnon()) {
    // Step Two: user enters new password

    $check_magic = $db->Query("SELECT * FROM {users} WHERE magic_url = ?",
            array(Get::val('magic')));

    if (!$db->CountRows($check_magic)) {
        $_SESSION['ERROR'] = $admin_text['badmagic'];
        $fs->redirect($fs->createUrl('error'));
    }
    $page->display('lostpw.step2.tpl');
}
?>
