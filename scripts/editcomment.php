<?php

  /************************************\
  | Edit comment                       |
  | ~~~~~~~~~~~~                       |
  | This script allows users           |
  | to edit comments attached to tasks |
  \************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

class FlysprayDoEditcomment extends FlysprayDo
{
    var $comment = array();

    function is_projectlevel() {
        return true;
    }

    function is_accessible()
    {
        global $db, $page, $user;

        $this->comment = $db->x->getRow('SELECT  c.*, u.real_name
                             FROM  {comments} c
                       INNER JOIN  {users}    u ON c.user_id = u.user_id
                            WHERE  comment_id = ? AND task_id = ?', null,
                            array(Get::num('id', 0), Get::num('task_id', 0)));
        return $user->can_edit_comment($this->comment);
    }

    function show()
    {
        global $page;
        $page->assign('comment', $this->comment);
        $page->pushTpl('editcomment.tpl');
    }
}
?>
