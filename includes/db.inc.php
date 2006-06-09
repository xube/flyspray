<?php

/*
   ---------------------------------------
   | Flyspray database access functions, |
   | utilising ADOdb                     |
   ---------------------------------------
*/

if(!defined('IN_FS')) {
    die('Do not access this file directly.');
}

require_once ( $conf['general']['adodbpath']);

class Database
{
    var $dbprefix;
    var $cache = array();

    function dbOpenFast($conf)
    {
        extract($conf, EXTR_REFS|EXTR_SKIP);
        $this->dbOpen($dbhost, $dbuser, $dbpass, $dbname, $dbtype, $dbprefix);
    }

    function dbOpen($dbhost = '', $dbuser = '', $dbpass = '', $dbname = '', $dbtype = '', $dbprefix = '')
    {
        
        $this->dbtype   = $dbtype;
        $this->dbprefix = $dbprefix;
        $ADODB_COUNTRECS = false;
        $dsn = "$dbtype://$dbuser:$dbpass@$dbhost/$dbname";
        $this->dblink = NewADOConnection($dsn);

        if ($this->dblink === false ) {

            die('Flyspray was unable to connect to the database.  '
               .'Check your settings in flyspray.conf.php');
        }
            $this->dblink->SetFetchMode(ADODB_FETCH_BOTH);

           !defined('DEBUG_SQL') || $this->dblink->debug= true;
    }

    function dbClose()
    {
        $this->dblink->Close();
    }

    /* Replace undef values (treated as NULL in SQL database) with empty
       strings.
       @param arr        input array or false
       @return        SQL safe array (without undefined values)
     */
    function dbUndefToEmpty($arr)
    {
        if (is_array($arr)) {
            $c = count($arr);

            for($i=0; $i<$c; $i++) {
                if (!isset($arr[$i])) {
                    $arr[$i] = '';
                }
                // This line safely escapes sql before it goes to the db
                $this->dblink->qstr($arr[$i]);
            }
        }
        return $arr;
    }

    function dbExec($sql, $inputarr=false, $numrows=-1, $offset=-1)
    {
        // auto add $dbprefix where we have {table}
        $sql = $this->_add_prefix($sql);
        // replace undef values (treated as NULL in SQL database) with empty
        // strings
        $inputarr = $this->dbUndefToEmpty($inputarr);
        //$inputarr = $this->dbMakeSqlSafe($inputarr);

        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

       if($this->dblink->hasTransactions === true) {

            $this->dblink->StartTrans();
       }

        if (($numrows >= 0 ) or ($offset >= 0 )) {
            $result =  $this->dblink->SelectLimit($sql, $numrows, $offset, $inputarr);
        } else {

           $result =  $this->dblink->Execute($sql, $inputarr);
        }

       if (!$result) {

            if (function_exists("debug_backtrace")) {
                echo "<pre style='text-align: left;'>";
                var_dump(debug_backtrace());
                echo "</pre>";
            }

            die (sprintf("Query {%s} with params {%s} Failed! (%s)",
                        $sql, implode(', ', $inputarr),
                        $this->dblink->ErrorMsg()));
        }

        if($this->dblink->hasTransactions === true) {

           $this->dblink->CompleteTrans();
        }

        return $result;
    }

    function CountRows(&$result)
    {
        return (int) $result->RecordCount();
    }

    function AffectedRows()
    {
        return (int) $this->dblink->Affected_Rows();
    }

    function FetchRow(&$result)
    {
        return $result->FetchRow();
    }

    function fetchCol(&$result, $col=0)
    {
        $tab = array();
        while ($tmp = $result->fetchRow()) {
            $tab[] = $tmp[$col];
        }
        return $tab;
    }

    /* compatibility functions */
    function Query($sql, $inputarr=false, $numrows=-1, $offset=-1)
    {
        return $this->dbExec($sql, $inputarr, $numrows, $offset);
    }

    function _cached_query($idx, $sql, $sqlargs = array())
    {
        if (isset($this->cache[$idx])) {
            return $this->cache[$idx];
        }

        $sql = $this->Query($sql, $sqlargs);
        return ($this->cache[$idx] = $this->fetchAllArray($sql));
    }

    function FetchArray(&$result)
    {
        return $this->FetchRow($result);
    }

    function FetchOne(&$result)
    {
        $row = $this->FetchArray($result);
        return (count($row) ? $row[0] : '');
    }

    function FetchAllArray(&$result)
    {
        return $result->GetArray();
    }

    function GetColumnNames($table, $alt, $prefix)
    {
        global $conf;
        if (strcasecmp($conf['database']['dbtype'], 'pgsql')) {
            return $alt;
        }
        
        $table = $this->_add_prefix($table);
        $fetched_columns = $this->Query('SELECT column_name FROM information_schema.columns WHERE table_name = ?',
                                         array(str_replace('"', '', $table)));
        $fetched_columns = $this->FetchAllArray($fetched_columns);
        
        foreach ($fetched_columns as $key => $value)
        {
            $col_names[$key] = $prefix . $value[0];
        }
        
        $groupby = implode(', ', $column_names);
        
        return $groupby;
    }

    function Replace($table, $field, $keys, $autoquote = true)
    {
        $table = $this->_add_prefix($table);
        return $this->dblink->Replace($table, $field, $keys, $autoquote);
    }

    /**
     * Adds the table prefix
     * @param string $sql_data table name or sql query
     * @return string sql with correct,quoted table prefix
     * @access private
     * @since 0.9.9
     */

    function _add_prefix($sql_data)
    {
        return (string) preg_replace('/{([\w\-]*?)}/', $this->QuoteIdentifier($this->dbprefix . '\1'), $sql_data);
    }
    
    /**
     * Helper method to quote an indentifier 
     * (table or field name) with the database specific quote
     * @param string $ident table or field name to be quoted
     * @return string
     * @access public
     * @since 0.9.9
     */
    
    function QuoteIdentifier($ident)
    {
        return (string) $this->dblink->nameQuote . $ident . $this->dblink->nameQuote ;
    }
    // End of Database Class
}

?>
