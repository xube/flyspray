<?php
   /**********************************************************\
   | This script renames columns, adodb seems to have prob here|
   \**********************************************************/

$dict = NewDataDictionary($db);

$sqlarray = $dict->RenameColumnSQL($conf['database']['dbprefix'] . 'related', 'is_duplicate', 'related_type', 'TYPE INT(8) NOTNULL  DEFAULT 0');
$dict->ExecuteSQLArray($sqlarray);

?>