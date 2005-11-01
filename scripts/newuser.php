<?php
$fs->get_language_pack('newuser');
$page->uses('newuser_text');

if (!can_create_user($permissions)) {
    $fs->redirect('./');
}
if (@$permissions['is_admin'] == '1') {
    $sql = $db->Query("SELECT  group_id, group_name
                         FROM  {groups}
                        WHERE  belongs_to_project = '0'
                     ORDER BY  group_id ASC");
    $page->assign('group_names', $db->fetchAllArray($sql));
}
$page->display('newuser.tpl');
?>
