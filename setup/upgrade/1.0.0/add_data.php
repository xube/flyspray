<?php
/* Add some new data */
$sql = $db->Query('SELECT * FROM {groups} WHERE view_userlist = 1');
if (!$db->CountRows($sql)) {
    $db->Query('UPDATE {groups} SET view_userlist = 1');
}


$dict = NewDataDictionary($db->dblink);

$sqlarray = $dict->DropTableSQL($conf['database']['dbprefix'] . 'list_os');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($conf['database']['dbprefix'] . 'list_status');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($conf['database']['dbprefix'] . 'list_resolution');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($conf['database']['dbprefix'] . 'list_tasktype');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL($conf['database']['dbprefix'] . 'list_version');
$dict->ExecuteSQLArray($sqlarray);

?>