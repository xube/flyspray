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

require_once dirname(dirname(__FILE__)) . '/adodb/adodb.inc.php';

/**
 * fill_placeholders
 *  a convenience function to fill sql query placeholders
 *  according to the number of columns to be used.
 * @param array $cols
 * @param integer $additional generate N additional placeholders
 * @access public
 * @return string comma separated "?" placeholders
 * @static
 */
function fill_placeholders($cols, $additional=0)
{
    if (is_array($cols) && count($cols) && is_int($additional)) {

        return join(',', array_fill(0, (count($cols) + $additional), '?'));

    } else {
        //this is not an user error, is a programmer error.
        trigger_error("incorrect data passed to fill_placeholders", E_USER_ERROR);
    }
}

/**
 * Shows a database error and exits. Since this should not happen usually, we don't need
 * any fancy error message here. Also, we must make sure that database errors don't
 * stay unnoticed during development.
 */
function show_dberror()
{
    echo 'A database error occured. Details below:' . "\n";
    $print = func_get_args();
    array_pop($print); // do adodb object please
    print_r($print);
    if (!defined('IN_UPGRADER')) exit;
}

/**
 * This function adds a table prefix to an SQL query
 * see $db->fnExecute
 *
 * @author Florian Schmitz
 * @author Cristian Rodriguez
 */
function &_table_prefix(&$db, &$sql, $inputarray)
{
    if (!defined('DB_PREFIX')) {
        die ('No table prefix set!');
    }

    $sql = preg_replace('/{([\w\-]+?)}/', $db->nameQuote . DB_PREFIX . '\1' . $db->nameQuote, $sql);

    $null = null;
    return $null;
}

// if set to false, we'll get errors
$ADODB_COUNTRECS = true;
$ADODB_CACHE_DIR = BASEDIR . '/cache';

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

    $dsn = "$dbtype://$dbuser:$dbpass@$dbhost/$dbname";
    $db =& ADONewConnection($dsn);
    $dbprefix = isset($dbprefix) ? $dbprefix : '';

    if ($db === false || (!empty($dbprefix) && !preg_match('/^[a-z][a-z0-9_]+$/i', $dbprefix))) {
        die('Flyspray was unable to connect to the database. '
            .'Check your settings in flyspray.conf.php');
    }

    $db->SetFetchMode(ADODB_FETCH_BOTH);
    /*
     * this will work only in the following systems/PHP versions
     *
     * PHP4 and 5 with postgresql
     * PHP5 with "mysqli" or "pdo_mysql" driver (not "mysql" driver)
     * using mysql 4.1.11 or later and mysql 5.0.6 or later.
     *
     * in the rest of the world, it will silently return FALSE.
     */

    $db->SetCharSet('utf8');
    $db->fnExecute = '_table_prefix';
    $db->fnCacheExecute = '_table_prefix';
    $db->raiseErrorFn = 'show_dberror';

    //enable debug if constact DEBUG_SQL is defined.
    !defined('DEBUG_SQL') || $db->debug = true;
    define('DB_PREFIX', $dbprefix);

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

    if (strcasecmp($conf['database']['dbtype'], 'pgsql') || !ctype_alnum($prefix)) {
        return $alt;
    }

    $col_names = $db->MetaColumnNames($table);

    return implode(', ', array_map(create_function('$x', 'return "' . $prefix . '" . $x;'), $col_names));
}

/**
 * GroupBy
 *
 * This groups a result by a single column the way
 * MySQL would do it. Postgre doesn't like the queries MySQL needs.
 *
 * @param object $result
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
    while (!$result->EOF) {
        foreach ($collect_columns as $col) {
            if (isset($rows[$result->fields[$column]][$col])) {
                $result->fields[$col] = array_merge( (array) $rows[$result->fields[$column]][$col],
                                                             array($result->fields[$col]));
            }
        }
        $rows[$result->fields[$column]] = $result->fields;
        $result->MoveNext();
    }
    return ($reindex) ? array_values($rows) : $rows;
}

?>