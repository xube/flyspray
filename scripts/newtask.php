<?php

  /********************************************************\
  | Task Creation                                          |
  | ~~~~~~~~~~~~~                                          |
  \********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

class FlysprayDoNewtask extends FlysprayDo
{
    function action_newtask()
    {
        global $user;

        if (!Post::val('item_summary') || !Post::val('detailed_desc')) {
            return array(ERROR_RECOVER, L('summaryanddetails'));
        }

        list($task_id, $token) = Backend::create_task($_POST);

        // Status and redirect
        if ($task_id) {
            $url = CreateURL('details', $task_id);
            if ($user->isAnon()) {
                $url = CreateURL('details', $task_id, null, array('task_token' => $token));
            }
            if (Post::val('more_tasks')) {
                $url = '';
            }
            return array(SUBMIT_OK, L('newtaskadded'), $url);
        }
        return array(ERROR_DB);
    }

    function is_accessible()
    {
        global $user, $proj;
        return $user->can_open_task($proj);
    }

    function _onsubmit()
    {
        global $page, $db;

        return $this->handle('action', Post::val('action'));
    }

    function _show()
    {
        global $page, $fs, $proj;

        $page->setTitle($fs->prefs['page_title'] . $proj->prefs['project_title'] . ': ' . L('newtask'));
        $page->assign('userlist', array());
        $page->assign('old_assigned', '');
        $page->pushTpl('newtask.tpl');
    }
}

?>
