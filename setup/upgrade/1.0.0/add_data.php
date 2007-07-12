<?php
/* Add some new data */
$sql = $db->query('SELECT * FROM {groups} WHERE view_userlist = 1');
if ($sql && !$sql->FetchRow()) {
    $db->x->execParam('UPDATE {groups} SET view_userlist = 1');
}

$db->manager->dropTable('{list_os}');
$db->manager->dropTable('{list_status}');
$db->manager->dropTable('{list_resolution}');
$db->manager->dropTable('{list_tasktype}');
$db->manager->dropTable('{list_version}');

?>
