<?php

/*
 * Base class for authentication and interface
 * for other auth plugins at the same time.
 */
class FlysprayAuth
{
    var $authenticators = array();
    
    function FlysprayAuth()
    {
        $plugins = glob_compat(dirname(__FILE__) . '/class.*');

        foreach ($plugins as $plugin) {
            require_once($plugin);
            $class = substr(basename($plugin), 6, -4);
            // better not include yourself ;)
            if (strcasecmp($class, get_class($this)) == 0) {
                continue;
            }
            $class = new $class;
            $this->authenticators[$class->getOrder()] = $class;
        }
        ksort($this->authenticators);
    }
    
    /*
     * Decides on the order the auth plugins
     * are "asked".
     *
     * @return int lower = earlier
     */
    function getOrder() {
        return 1;
    }

    /*
     * Authenticates login, when user specifies a name
     * and password.
     *
     * @param string $username
     * @param string $password
     * @return mixed a valid user ID or array(ERROR_TYPE, ERROR_MSG, REDIRECT_TO)
     */
    function checkLogin($username, $password) {
        global $fs, $db, $proj, $conf, $baseurl;

        if ($username == '' || $password == '') {
            return array(ERROR_RECOVER, L('error8'));
        }

        $username = Backend::clean_username($username);

        // first a does-user-exist check
        $user = $db->x->getRow('SELECT * FROM {users} WHERE user_name = ?', null, $username);
        if (!$user) {
            // at this point, provide the possibility to ask for LDAP users etc.
            foreach ($this->authenticators as $auth) {
                $user = $auth->checkLogin($username, $password);
                if (is_array($user))  {
                    return $user; // some error message
                } else if ($user) {
                    $user = $db->x->getRow('SELECT * FROM {users} WHERE user_id = ?', null, $user);
                    break;
                }
            }
            // still not?
            if (!$user) {
                return array(ERROR_RECOVER, L('usernotexist'));
            }
        }
        
        // now check if he is actually allowed to login
        $group = $db->x->getRow('SELECT g.*
                                   FROM {groups} g
                              LEFT JOIN {users_in_groups} uig ON g.group_id = uig.group_id
                                  WHERE  uig.user_id = ? AND g.project_id = 0
                               ORDER BY g.group_id', null, $user['user_id']);
        // that's not good. a user always has to be in a single global group
        if (!$group) {
            return array(ERROR_INPUT, L('usernotinglobalgroup'));
        }
        // revert lock if any
        if ($user['lock_until'] > 0 && $user['lock_until'] < time()) {
            $db->x->execParam('UPDATE {users} SET lock_until = 0, account_enabled = 1, login_attempts = 0
                                WHERE user_id = ?', $user['user_id']);
            $user['account_enabled'] = 1;
            $_SESSION['was_locked'] = true;
        }
        // may not login
        if (!$user['account_enabled'] || !$group['group_open']) {
            return array(ERROR_INPUT, L('error23'));
        }
        
        $salt = $user['password_salt'] ? $user['password_salt'] : null;
        
        // now check password
        if ($user['user_pass'] !== Flyspray::cryptPassword($password, $salt)) {
            // possibly we have to lock the account

            // just some extra check here so that never ever an account can get locked when it's already disabled
            // ... that would make it easy to get enabled
            $db->x->execParam('UPDATE {users} SET login_attempts = login_attempts+1 WHERE account_enabled = 1 AND user_id = ?', $user['user_id']);
            // Lock account if failed too often for a limited amount of time
            $num = $db->x->execParam('UPDATE {users} SET lock_until = ?, account_enabled = 0 WHERE login_attempts > ? AND user_id = ?',
                                     array(time() + 60 * $fs->prefs['lock_for'], LOGIN_ATTEMPTS, $user['user_id']));

            if ($num) {
                // let user know about the lock
                return array(ERROR_RECOVER, sprintf(L('error71'), $fs->prefs['lock_for']));
            } else {
                // just plain wrong password
                return array(ERROR_RECOVER, L('error7'));
            }
        }

        // [BC] let's add a user's password salt if he doesn't have one yet
        if (!$user['password_salt']) {
            $salt = md5(uniqid(mt_rand() , true));
            $db->x->execParam('UPDATE {users} SET user_pass = ?, password_salt = ? WHERE user_id = ?',
                              array(Flyspray::cryptPassword($password, $salt), $salt, $user['user_id']));
        }
        
        // last, some post-login stuff
        // Determine if the user should be remembered on this machine
        $cookie_time = (Post::has('remember_login') ? time() + (60 * 60 * 24 * 30) : 0);
        Flyspray::setcookie('flyspray_userid', $user['user_id'], $cookie_time);
        Flyspray::setcookie('flyspray_passhash', hash_hmac('md5', $user['user_pass'], $conf['general']['cookiesalt']), $cookie_time);

        // If the user had previously requested a password change, remove the magic url
        $db->x->execParam('UPDATE {users} SET magic_url = NULL WHERE user_id = ?', $user['user_id']);
        // Save for displaying
        if ($user['login_attempts'] > 0) {
            $_SESSION['login_attempts'] = $user['login_attempts'];
        }
        $db->x->execParam('UPDATE {users} SET login_attempts = 0 WHERE user_id = ?', $user['user_id']);

        return $user['user_id'];
    }

    /*
     * Authenticates an existing cookie for auto-login.
     *
     * @param int $userid
     * @param string $passhash
     * @return bool
     */
    function checkCookie($userid, $passhash) {
        global $conf, $db;
        
        $user = $db->x->getRow('SELECT u.*, g.group_open
                                  FROM {users} u
                             LEFT JOIN {users_in_groups} uig ON u.user_id = uig.user_id
                             LEFT JOIN {groups} g ON uig.group_id = g.group_id
                                 WHERE u.user_id = ? AND g.project_id = 0', null, $userid);
        // sort out most bad cases
        if (!$user || !$user['account_enabled'] || !$user['group_open']) {
            return false;
        }
        
        if ($passhash !== hash_hmac('md5', $user['user_pass'], $conf['general']['cookiesalt']))
        {
            // try other authenticators, maybe use cookies from other software
            foreach ($this->authenticators as $auth) {
                if ($auth->checkCookie($userid, $passhash)) {
                    return true;
                }
            }
            
            // delete this rubbish
            Flyspray::setcookie('flyspray_userid',   '', time()-60);
            Flyspray::setcookie('flyspray_passhash', '', time()-60);
            return false;
        }
        return true;
    }
}

?>