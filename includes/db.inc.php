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

require_once ( $conf['general']['adodbpath'] );

class Database
{
    var $dbprefix;
    var $cache = array();

    function dbOpenFast($conf)
    {
        extract($conf);
        $this->dbOpen($dbhost, $dbuser, $dbpass, $dbname, $dbtype, $dbprefix);
    }

    function dbOpen($dbhost = '', $dbuser = '', $dbpass = '', $dbname = '', $dbtype = '', $dbprefix = '')
    {
        $this->dbtype   = $dbtype;
        $this->dbprefix = $dbprefix;

        $this->dblink = NewADOConnection($dbtype);
        $res = $this->dblink->Connect($dbhost, $dbuser, $dbpass, $dbname);
        $this->dblink->SetFetchMode(ADODB_FETCH_BOTH);

        if (!$res) {
            die('Flyspray was unable to connect to the database.  '
               .'Check your settings in flyspray.conf.php');
        }
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
                $this->dblink->qmagic($arr[$i]);
            }
        }
        return $arr;
    }

    function dbExec($sql, $inputarr=false, $numrows=-1, $offset=-1)
    {
        // auto add $dbprefix where we have {table}
        $sql = preg_replace('/{([\w\-]*?)}/', $this->dbprefix.'\1', $sql);
        // replace undef values (treated as NULL in SQL database) with empty
        // strings
        $inputarr = $this->dbUndefToEmpty($inputarr);
        //$inputarr = $this->dbMakeSqlSafe($inputarr);

        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
        if (($numrows>=0) or ($offset>=0)) {
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
        return $result;
    }

    function CountRows(&$result)
    {
        return $result->RecordCount();
    }
    
    function AffectedRows()
    {
        return $this->dblink->Affected_Rows();
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
        return (count($row) > 0 ? $row[0] : null);
    }

    function FetchAllArray(&$result)
    {
        return $result->GetArray();
    }

    function GetColumnNames($table)
    {
        $table = preg_replace('/{([\w\-]*?)}/', $this->dbprefix.'\1', $table);
        return $this->dblink->MetaColumnNames($table,$numericIndex=true);
    }
    // End of Database Class
}

?>
