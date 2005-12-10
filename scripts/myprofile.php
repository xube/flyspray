<?php

  /*********************************************************\
  | User Profile Edition                                    |
  | ~~~~~~~~~~~~~~~~~~~~                                    |
  \*********************************************************/

if ($user->isAnon()) {
    $fs->redirect($fs->createUrl('error'));
}

$fs->get_language_pack('admin');
$fs->get_language_pack('newuser');

$sql = $db->Query("SELECT  group_id, group_name
                             FROM  {groups}
                            WHERE  belongs_to_project = '0'
                         ORDER BY  group_id ASC");
$page->assign('groups', $db->fetchAllArray($sql));
        
$page->uses('admin_text','newuser_text');
$page->assign('theuser', $user);

$page->setTitle('Flyspray:: ' . $language['editmydetails']);
$page->pushTpl('myprofile.tpl');

?>
