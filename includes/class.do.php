<?php

class FlysprayDo
{
    var $result = array();
    var $default_handler = null;

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
		return array(MENU_GLOBAL, 1);
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

	function FlysprayDo()
	{
		// check minimum permissions
		if (!$this->is_accessible()) {
			$this->result = array(ERROR_PERMS);
		} else {
			// check if data has been submitted and respond
			$this->result = $this->_onsubmit();
		}

        FlysprayDo::error($this->result);
    }

    function error($error)
    {
        global $page;
        
        if(!is_array($error)) {
            return;
        }
        
        list($type, $msg, $url) = array_pad($error, 3, '');
		switch ($type)
		{
			case ERROR_PERMS:
			case ERROR_DB:
            case ERROR_INPUT:
                $page->assign('type', $type);
                $page->assign('message', $msg);
                $page->pushTpl('error.tpl');
                $page->finish();
                exit;
			case ERROR_RECOVER:
                if ($msg) {
                    $_SESSION['ERROR'] = $msg;
                }
				break;
			case SUBMIT_OK:
                if ($msg) {
                    $_SESSION['SUCCESS'] = $msg;
                }
                if ($url) {
                    Flyspray::Redirect($url);
                }
				break;
		}
	}
}

?>