<?php
// If the application preferences require the use of
// confirmation codes, use this script
if ($fs->prefs['spam_proof'] != '1'
    || $fs->prefs['anon_reg'] != '1'
    || Cookie::has('flyspray_userid'))
{
    $fs->Redirect( $fs->CreateURL('error', null) );
}

$fs->get_language_pack('register');
$page->uses('register_text');

// If the user came here from their notification link
if (Get::has('magic')) {
    // Check that the magic url is valid
    $check_magic = $db->Query("SELECT * FROM {registrations}
                               WHERE magic_url = ?",
                               array(Get::val('magic')));

    if (!$db->CountRows($check_magic)) {
        $fs->Redirect( $fs->CreateURL('error', null) );
    }

    $page->display('register.magic.tpl');
} else {
    $page->display('register.no-magic.tpl');
}
?>
