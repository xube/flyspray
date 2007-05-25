<?php

  /*********************************************************\
  | View a user's profile                                   |
  | ~~~~~~~~~~~~~~~~~~~~                                    |
  \*********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

class FlysprayDoUser extends FlysprayDo
{
    var $user = null;

    function is_projectlevel() {
        return true;
    }

    function is_accessible()
    {
        $id = Flyspray::username_to_id(Get::val('id', Get::val('uid')));
        $this->user = new User($id);
        return !$this->user->isAnon();
    }

    function show()
    {
        global $db, $page, $fs;

        // Some possibly interesting information about the user
        $sql = $db->GetOne('SELECT count(*) FROM {comments} WHERE user_id = ?', array($this->user->id));
        $page->assign('comments', $sql);

        $sql = $db->GetOne('SELECT count(*) FROM {tasks} WHERE opened_by = ?', array($this->user->id));
        $page->assign('tasks', $sql);

        $sql = $db->GetOne('SELECT count(*) FROM {assigned} WHERE user_id = ?', array($this->user->id));
        $page->assign('groups', Flyspray::listallGroups($this->user->id));
        $page->assign('assigned', $sql);

        $page->assign('theuser', $this->user);

        $page->setTitle($fs->prefs['page_title'] . L('viewprofile'));
        $page->pushTpl('profile.tpl');
    }
}

?>
