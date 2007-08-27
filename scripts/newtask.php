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
    function is_projectlevel() {
        return true;
    }

    function action_newtask()
    {
        global $user;

        list($task_id, $token, $ok) = Backend::create_task($_POST);

        // Status and redirect
        if ($ok) {
            $url = CreateURL(array('details', 'task' . $task_id));
            if ($user->isAnon()) {
                $url = CreateURL(array('details', 'task' . $task_id), array('task_token' => $token));
            }
            if (Post::val('more_tasks')) {
                $url = '';
            }
            return array(SUBMIT_OK, L('newtaskadded'), $url);
        } else {
            return array($task_id, $token); // create_task will out error info there
        }
    }

    function is_accessible()
    {
        global $user, $proj;
        return $user->can_open_task($proj);
    }

    function _onsubmit()
    {
        global $page, $db;
        $area = Post::val('action');
        return $this->handle('action', $area);
    }

    function show()
    {
        global $page, $fs, $proj;

        $page->setTitle($fs->prefs['page_title'] . $proj->prefs['project_title'] . ': ' . L('newtask'));
        $page->assign('userlist', array());
        $page->pushTpl('newtask.tpl');
    }
}

?>
