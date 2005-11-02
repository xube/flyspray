<?php

/*
  -------------------------------------------------------------
  | Project Managers Toolbox                                  |
  | ------------------------                                  |
  | This script is for Project Managers to modify settings    |
  | for their project, including general permissions,         |
  | members, group permissions, and dropdown list items.      |
  -------------------------------------------------------------
*/

if ($permissions['manage_project'] != '1') {
    $fs->Redirect( $fs->CreateURL('error', null) );
}

$fs->get_language_pack('index');
$fs->get_language_pack('admin');
$fs->get_language_pack('pm');
$page->uses('admin_text', 'pm_text', 'index_text');

switch ($area = Get::val('area', 'prefs')) {
    case 'pendingreq':
        $sql = $db->Query("SELECT  *
                             FROM  {admin_requests} ar
                        LEFT JOIN  {tasks} t ON ar.task_id = t.task_id
                        LEFT JOIN  {users} u ON ar.submitted_by = u.user_id
                            WHERE  project_id = ? AND resolved_by = '0'
                         ORDER BY  ar.time_submitted ASC", array($proj->id));

        $page->assign('pendings', $db->fetchAllArray($sql));

    case 'prefs':
    case 'groups':
    case 'editgroup':
    case 'tt':
    case 'res':
    case 'os':
    case 'ver':
    case 'cat':
        $page->display('pm.menu.tpl');
        $page->display('pm.'.$area.'.tpl');
        break;

    default:
        $fs->Redirect( $fs->CreateURL('error', null) );
}
?>
