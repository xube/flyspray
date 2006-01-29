<?php

  /*********************************************************\
  | Register a new user (when confirmation codes is used)   |
  | ~~~~~~~~~~~~~~~~~~~                                     |
  \*********************************************************/

if (!$user->can_register()) {
    $fs->Redirect( CreateURL('error', null) );
}

$page->setTitle('Flyspray:: ' . $language['registernewuser']);

if (Get::has('magic')) {
    // If the user came here from their notification link
    $sql = $db->Query("SELECT * FROM {registrations} WHERE magic_url = ?",
            array(Get::val('magic')));

    if (!$db->CountRows($sql)) {
        $fs->Redirect( CreateURL('error', null) );
    }

    $page->pushTpl('register.magic.tpl');
} else {
    $page->pushTpl('register.no-magic.tpl');
}
?>
