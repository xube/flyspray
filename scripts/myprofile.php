<?php

  /*********************************************************\
  | User Profile Edition                                    |
  | ~~~~~~~~~~~~~~~~~~~~                                    |
  \*********************************************************/

if ($user->isAnon()) {
    $fs->redirect(CreateURL('error'));
}

$sql = $db->Query("SELECT  group_id, group_name
                             FROM  {groups}
                            WHERE  belongs_to_project = '0'
                         ORDER BY  group_id ASC");
$page->assign('groups', $db->fetchAllArray($sql));
        
$page->assign('theuser', $user);

$page->setTitle('Flyspray:: ' . $language['editmydetails']);
$page->pushTpl('myprofile.tpl');

?>
