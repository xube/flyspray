<?php
/* Add some new data */
$sql = $db->Execute('SELECT * FROM {groups} WHERE view_userlist = 1');
if ($sql && !$sql->FetchRow()) {
    $db->Execute('UPDATE {groups} SET view_userlist = 1');
}


$dict = NewDataDictionary($db);

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