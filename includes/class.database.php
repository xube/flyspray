<?php
/**
 * Flyspray's database functions
 *
 * @license http://opensource.org/licenses/lgpl-license.php Lesser GNU Public License
 * @package flyspray
 * @author Florian Schmitz
 * @author Cristian Rodriguez
 */

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

require_once 'MDB2.php';


/**
 * Shows a database error and exits. Since this should not happen usually, we don't need
 * any fancy error message here. Also, we must make sure that database errors don't
 * stay unnoticed during development.
 */
function show_dberror($error)
{
    @ob_clean();
    global $db;

    // error could happen anywhere, we might not have templates or anything else
    // at our disposal yet => quick and dirty
    echo '<fieldset><legend>Database error</legend>
          <p>
            A database error occured. Please <a href="http://forum.flyspray.org">report this problem</a> to the developers unless
            the error message below indicates that it is a configuration issue or the like.
          </p>
          <pre>' . htmlspecialchars(wordwrap($error->userinfo), ENT_QUOTES, 'utf-8') . '</pre>
          <hr /><table><caption>Debug trace</caption><tr><th>File</th><th>Line</th></tr>';

    if(count($error->backtrace)) {
        foreach ($error->backtrace as $trace) {

            if(!isset($trace['file']) || !isset($trace['line'])) {
                    continue;
              }
            echo '<tr><td>' . htmlspecialchars(str_replace(BASEDIR . DIRECTORY_SEPARATOR, '@',  $trace['file']) , ENT_QUOTES, 'utf-8') . '</td><td>' . intval($trace['line']) . '</td></tr>';
        }
    }
    echo '</table></fieldset>';
    if ($db->inTransaction()) {
        $db->rollback();
    }
    exit;
}

/**
 * This function adds a table prefix to an SQL query
 *
 * @author Florian Schmitz
 * @author Cristian Rodriguez
 */
function _table_prefix(&$db, $scope, $message, $is_manip = null)
{

    if (strpos($message, 'SET') !== 0 && ($scope === 'query' || $scope === 'prepare')) {
        if (defined('DEBUG_SQL')) {
            echo $message . '<hr />';
        }
        return preg_replace('/{([\w\-]+?)}/', DB_PREFIX . '\1', $message);
    }

    return $message;
}

/**
 * This function is a replacement for ADONewConnection, it does some
 * basic additional configuration and prepares the table prefix stuff
 *
 * @param $conf usually $conf['database'] in Flyspray
 * @return object
 */
function &NewDatabase($conf = array())
{
    if (!is_array($conf) || extract($conf, EXTR_REFS|EXTR_SKIP) < 5) {
        die( 'Flyspray was unable to connect to the database. '
            .'Check your settings in flyspray.conf.php');
    }

    $dbpass = rawurlencode($dbpass);
    if ($dbtype == 'sqlite') {
        $dsn = "$dbtype:///$dbname?mode=0666";
    } else {
        $dsn = "$dbtype://$dbuser:$dbpass@$dbhost/$dbname?charset=utf8";
    }
    $db =& MDB2::factory($dsn);

    $db->loadModule('Extended', 'x', false);
    if (defined('IN_UPGRADER') || defined('IN_SETUP')) {
        $db->loadModule('Manager');
    }


    $dbprefix = isset($dbprefix) ? $dbprefix : '';

    if ($db === false || (!empty($dbprefix) && !preg_match('/^[a-z][a-z0-9_]+$/i', $dbprefix))) {
        die('Flyspray was unable to connect to the database. '
            .'Check your settings in flyspray.conf.php');
    }
    define('DB_PREFIX', $dbprefix);

    $db->setFetchMode(MDB2_FETCHMODE_ASSOC);
    
    $db->setOption('debug', true);
    $db->setOption('debug_handler', '_table_prefix');
    $db->setOption('quote_identifier', true);
    // upgrader can handle that on its own
    if (!defined('IN_UPGRADER') && !defined('IN_SETUP') || defined('DEBUG_SQL')) {
        $db->setErrorHandling(PEAR_ERROR_CALLBACK, 'show_dberror');
    }

    return $db;
}

/**
 * GetColumnNames
 *
 * @param mixed $table
 * @param mixed $alt
 * @param mixed $prefix
 * @access public
 * @return void
 */

function GetColumnNames($table, $alt, $prefix)
{
    global $conf, $db;

    if (strcasecmp($conf['database']['dbtype'], 'pgsql') || !Filters::isAlnum($prefix)) {
        return $alt;
    }
    $db->loadModule('Manager');
    $col_names = $db->manager->listTableFields($table);

    return implode(', ', array_map(create_function('$x', 'return "' . $prefix . '.' . '" . $x;'), $col_names));
}

/**
 * GroupBy
 *
 * This groups a result by a single column the way
 * MySQL would do it. Postgre doesn't like the queries MySQL needs.
 *
 * @param array $result
 * @param string $column
 * @param array $collect_columns collect data.
 *  example: user tables with groups joined. collect group_id when grouping by user_id to
 *           get an array of group_ids per user instead of a single group_id
 * @access public
 * @return array process the returned array with foreach ($return as $row) {}
 */
function GroupBy(&$result, $column, $collect_columns = array(), $reindex = true)
{
    $rows = array();
    foreach ($result as $row) {
        foreach ($collect_columns as $col) {
            if (isset($rows[$row[$column]][$col])) {
                $row[$col] = array_merge( (array) $rows[$row[$column]][$col], array($row[$col]));
            }
        }
        $rows[$row[$column]] = $row;
    }
    return ($reindex) ? array_values($rows) : $rows;
}

?>
