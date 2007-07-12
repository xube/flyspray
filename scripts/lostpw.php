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

    function action_chpass()
    {
        global $db;

        // Check that the user submitted both the fields, and they are the same
        if (!Post::val('pass1') || strlen(trim(Post::val('magic_url'))) !== 32) {
            return array(ERROR_RECOVER, L('erroronform'));
        }

        if (Post::val('pass1') != Post::val('pass2')) {
            return array(ERROR_RECOVER, L('passnomatch'));
        }
        $new_salt = md5(uniqid(mt_rand(), true));
        $new_pass_hash = Flyspray::cryptPassword(Post::val('pass1'), $new_salt);
        $db->x->execParam("UPDATE  {users} SET user_pass = ?, password_salt = ? , magic_url = ''
                            WHERE  magic_url = ?",
                          array($new_pass_hash, $new_salt, Post::val('magic_url')));

        return array(SUBMIT_OK, L('passchanged'), $baseurl);
    }

    function action_sendmagic()
    {
        global $db, $baseurl;

        // Check that the username exists
        if (strpos(Post::val('user_name'), '@') === false) {
            $user = Flyspray::getUserDetails(Flyspray::username_to_id(Post::val('user_name')));
        } else {
            $user_id = $db->x->GetOne('SELECT user_id FROM {users} WHERE email_address = ?', null, Post::val('user_name'));
            $user = Flyspray::getUserDetails($user_id);
        }

        // If the username doesn't exist, throw an error
        if (!is_array($user) || !count($user)) {
            return array(ERROR_RECOVER, L('usernotexist'));
        }

        //no microtime(), time,even with microseconds is predictable ;-)
        $magic_url    = md5(uniqid(mt_rand(), true));

        // Insert the random "magic url" into the user's profile
        $db->x->execParam('UPDATE {users}
                              SET magic_url = ?
                            WHERE user_id = ?',
                           array($magic_url, $user['user_id']));

        Notifications::send($user['user_id'], ADDRESS_USER, NOTIFY_PW_CHANGE, array($baseurl, $magic_url));

        return array(SUBMIT_OK, L('magicurlsent'));
    }

    function _onsubmit()
    {
        return $this->handle('action', Post::val('action'));
    }

    function show()
    {
        global $page, $fs, $db;

        $page->setTitle($fs->prefs['page_title'] . L('lostpw'));

        if (!Req::has('magic_url')) {
            // Step One: user requests magic url
            $page->pushTpl('lostpw.step1.tpl');
        } else {
            // Step Two: user enters new password
            $check_magic = $db->x->getRow('SELECT user_id, user_name FROM {users} WHERE magic_url = ?',
                                          null, array(Req::val('magic_url')));

            if ($check_magic) {
                $page->assign('userinfo', $check_magic);
                $page->pushTpl('lostpw.step2.tpl');
            } else {
                $page->pushTpl('lostpw.step1.tpl');
            }
        }
    }
}


?>
