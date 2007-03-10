<?php

  /*********************************************************\
  | Deal with lost passwords                                |
  | ~~~~~~~~~~~~~~~~~~~~~~~~                                |
  \*********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

class FlysprayDoLostpw extends FlysprayDo
{
    function is_accessible()
    {
        global $user;
        return $user->isAnon();
    }

    function _onsubmit()
    {
        global $page, $db;

        if (Get::has('magic_url')) {
            // Step Two: user enters new password
            $check_magic = $db->GetOne('SELECT user_id FROM {users} WHERE magic_url = ?',
                                        array(Get::val('magic_url')));

            if (!$check_magic) {
                return array(ERROR_INPUT, L('error12'));
            }
            $page->pushTpl('lostpw.step2.tpl');
        }
    }

    function _show()
    {
        global $page, $fs;

        $page->setTitle($fs->prefs['page_title'] . L('lostpw'));

        if (!Get::has('magic_url')) {
            // Step One: user requests magic url
            $page->pushTpl('lostpw.step1.tpl');
        }
    }
}


?>
