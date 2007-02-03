<?php
/* Add some new data */
$sql = $db->Query('SELECT * FROM {groups} WHERE view_userlist = 1');
if (!$db->CountRows($sql)) {
    $db->Query('UPDATE {groups} SET view_userlist = 1');
}

?>