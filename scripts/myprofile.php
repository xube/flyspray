<?php

  /*********************************************************\
  | User Profile Edition                                    |
  | ~~~~~~~~~~~~~~~~~~~~                                    |
  \*********************************************************/

if(!defined('IN_FS')) {
    die('Do not access this file directly.');
}

if ($user->isAnon()) {
    Flyspray::Redirect(CreateURL('error'));
}

$sql = $db->Query("SELECT  group_id, group_name
                             FROM  {groups}
                            WHERE  belongs_to_project = '0'
                         ORDER BY  group_id ASC");
$page->assign('groups', $db->fetchAllArray($sql));
        
$page->assign('theuser', $user);

$page->setTitle('Flyspray:: ' . L('editmydetails'));
$page->pushTpl('myprofile.tpl');

?>
