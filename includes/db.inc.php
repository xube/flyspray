<?php

/*
   ---------------------------------------
   | Flyspray database access functions, |
   | utilising ADOdb                     |
   ---------------------------------------
*/


class Database
{
    var $dbprefix;

    function dbOpenFast($conf)
    {
        extract($conf);
        return $this->dbOpen($dbhost, $dbuser, $dbpass, $dbname, $dbtype, $dbprefix);
    }

    function dbOpen($dbhost = '', $dbuser = '', $dbpass = '', $dbname = '', $dbtype = '', $dbprefix = '')
    {
        $this->dbtype   = $dbtype;
        $this->dbprefix = $dbprefix;

        $this->dblink = NewADOConnection($dbtype);
        $res = $this->dblink->Connect($dbhost, $dbuser, $dbpass, $dbname);
        $this->dblink->SetFetchMode(ADODB_FETCH_BOTH);

        return $res;
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

    function CountRows($result)
    {
        $num_rows = $result->RecordCount();
        return $num_rows;
    }

    function FetchRow(&$result)
    {
        $row = $result->FetchRow();
        return $row;
    }

    /* compatibility functions */
    function Query($sql, $inputarr=false, $numrows=-1, $offset=-1)
    {
        $result = $this->dbExec($sql, $inputarr, $numrows, $offset);
        return $result;
    }

    function FetchArray(&$result)
    {
        $row = $this->FetchRow($result);
        return $row;
    }

    function FetchOne(&$result)
    {
        $row = $this->FetchArray($result);
        return (count($row) > 0 ? $row[0] : null);
    }

    // End of Database Class
}

?>
