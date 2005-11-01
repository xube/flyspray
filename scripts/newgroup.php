<?php

// Make sure that only admins are using this page
if (!can_create_group($permissions)) {
    echo $newgroup_text['nopermission'];
    exit;
}

$fs->get_language_pack('newgroup');

if (Get::val('project')) {
    $result = $db->Query("SELECT  * FROM {projects}
                           WHERE  project_id = ?", array(Get::val('project')));
    $project_details = $db->FetchArray($result);
    $forproject = $project_details['project_title'];
} else {
    $forproject = $newgroup_text['globalgroups'];
}

$page->uses('newgroup_text');
$page->assign('forproject', $forproject);
$page->display('newgroup.tpl');
?>
