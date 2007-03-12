<?php

class FlysprayDo
{
    var $result = array();
    var $default_handler = null;

	function FlysprayDo()
	{
		// check minimum permissions
		if (!$this->is_accessible()) {
			$this->result = array(ERROR_PERMS);
		} else {
			// check if data has been submitted and respond
			$this->result = (array) $this->_onsubmit();
		}

        FlysprayDo::error($this->result);
    }

    function show($area = null)
    {
        return;
    }

	function _onsubmit($action = null) {
		return array(NO_SUBMIT);
	}

	function is_accessible() {
		return false;
	}

	function menu_position() {
		return array(MENU_GLOBAL, 1, 'Name');
	}

	/*
	 * This function calls the right method for each $area/$type
	 *
	 * Additional to that it makes sure that each of those handler
	 * functions actually return a result. It also starts DB transactions
	 * and returns the appropriate error type and message if something failed.
	 *
	 * @param string $type either "area" (show) or "action"
	 * @param string $area ByRef because we might need to set it to the default value
     * @param $param pass some additonal data to it. since the methods are static, they cannot use local properties
	 * @return array which has both an error type and message
	 */
    function handle($type, &$area, $param = null)
    {
    	global $db;

    	$return = array(NO_SUBMIT, '', '');
        if (!$area && $type == 'area') {
            $area = $this->default_handler;
        }
    	// usually everything or nothing here...
    	$db->StartTrans();

        if (method_exists($this, $type . '_' . $area)) {
            $return = call_user_func(array(get_class($this), $type . '_' . $area), $param);
        } else if ($type == 'area') {
            $return = call_user_func(array(get_class($this), $type . '_' . $this->default_handler), $param);
        }

        $db->CompleteTrans();

        if (!isset($return) && $type == 'action') {
        	trigger_error($type . '_' . $area . '() did not return anything!', E_USER_ERROR);
        }

        if ($db->HasFailedTrans()) {
        	$errorno = $db->MetaError();
        	$return = array(ERROR_DB, $db->ErrorMsg($errorno));  // MetaErrorMsg is not exactly precise
        }

        // Fill optional URL and message
        return array_pad( (array) $return, 3, '');
    }

	/*
	 * This function displays an error...nicely :)
	 *
	 * It will stop execution for all interal errors (PHP errors, warnings, notices)
     * and ERROR_PERMS, ERROR_DB, ERROR_INPUT. For ERROR_RECOVER it will only display
     * a failure bar at the bottom of the screen.
	 *
	 * @param mixed $errno this can either be array(ERROR_TYPE, opt message, opt url) or
     *                     an error number when used as PHP error handler
	 * @param string $errstr only used for PHP error handler
     * @param string $errfile only used for PHP error handler
     * @param integer $errline only used for PHP error handler
	 */
    function error($errno, $errstr = '', $errfile = '', $errline = 0)
    {
        global $db;

        if (isset($db) && is_object($db)) {
            $db->CompleteTrans(false); // if possible, undo database queries
        }

        $page = new FSTpl;
        $page->pushTpl('header.tpl');
        $page->assign('do', 'index');
        $page->setTheme();

        if (is_array($errno)) {
            list($errno, $errstr, $url) = array_pad($errno, 3, '');
        } else {
            // ignore E_STRICT and @
            if ($errno > E_ALL || !ini_get('error_reporting')) {
                return;
            }
            $errno = ERROR_INTERNAL;
        }

		switch ($errno)
		{
            case ERROR_INTERNAL:
                $page->assign('file', $errfile);
                $page->assign('line', $errline);
			case ERROR_PERMS:
			case ERROR_DB:
            case ERROR_INPUT:
                @ob_clean(); // make sure that previous output is erased
                $page->assign('type', $errno);
                $page->assign('message', $errstr);
                $page->pushTpl('error.tpl');
                $page->finish();
                exit;
			case ERROR_RECOVER:
                if ($errstr) {
                    $_SESSION['ERROR'] = $errstr;
                }
				break;
			case SUBMIT_OK:
                if ($errstr) {
                    $_SESSION['SUCCESS'] = $errstr;
                }
                if ($url) {
                    Flyspray::Redirect($url);
                }
				break;
		}
	}
}

?>
