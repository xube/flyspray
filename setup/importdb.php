<?php
// This is just a developer's tool. Needs tables with flyspray_ prefix.

error_reporting(E_ALL);
define('IN_FS', true);
$file = 'exportdb.xml';

require_once dirname(__FILE__) . '/../includes/fix.inc.php';
require_once dirname(__FILE__) . '/../includes/class.database.php';
 
$conf    = @parse_ini_file('../flyspray.conf.php', true) or die('Cannot open config file.');
 
define('DEBUG_SQL', true);
$db = NewDatabase($conf['database']);

require_once 'MDB2/Schema.php';

$db->setOption('idxname_format', '%s');
$schema =& MDB2_Schema::factory($db);
$schema->setOption('force_defaults', false);

if (isset($_GET['create'])) {
    $definition = $schema->parseDatabaseDefinitionFile($file, array('db_prefix' => $conf['database']['dbprefix'], 'db_name' => 'dbimport'));
    if (Pear::isError($definition)) {
        var_dump($definition);
        exit;
    }
    $res = $schema->createDatabase($definition);
} else {
    $previous_schema = $schema->getDefinitionFromDatabase();
    $res = $schema->updateDatabase($file, $previous_schema, array('db_prefix' => $conf['database']['dbprefix'], 'db_name' => $conf['database']['dbname']));
}
var_dump($res);
?>