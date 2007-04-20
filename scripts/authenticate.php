<?php

  /********************************************************\
  | User authentication (no output)                        |
  | ~~~~~~~~~~~~~~~~~~~                                    |
  \********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

class FlysprayDoAuthenticate extends FlysprayDo
{
    function action_login()
    {
        global $fs, $db, $proj, $user, $conf;

        if (Post::val('user_name') == '' || Post::val('password') == '') {
            return array(ERROR_RECOVER, L('error8'), './');
        }

        // See if they provided the correct credentials...
        $username = Backend::clean_username(Post::val('user_name'));
        $password = Post::val('password');

        // Run the username and password through the login checker
        if ( ($user_id = Flyspray::checkLogin($username, $password)) < 1) {
            if ($fs->prefs['ldap_enabled']) {
                // Does user exist in LDAP server? If so, add to DB
                $LDAP_CONNECT_OPTIONS = array(
                    array('OPTION_NAME' => LDAP_OPT_DEREF, 'OPTION_VALUE' => 2),
                    array('OPTION_NAME' => LDAP_OPT_SIZELIMIT,'OPTION_VALUE' => 100),
                    array('OPTION_NAME' => LDAP_OPT_TIMELIMIT,'OPTION_VALUE' => 30),
                    array('OPTION_NAME' => LDAP_OPT_PROTOCOL_VERSION,'OPTION_VALUE' => 3),
                    array('OPTION_NAME' => LDAP_OPT_ERROR_NUMBER,'OPTION_VALUE' => 13),
                    array('OPTION_NAME' => LDAP_OPT_REFERRALS,'OPTION_VALUE' => false),
                    array('OPTION_NAME' => LDAP_OPT_RESTART,'OPTION_VALUE' => false)
                );

                $ldap = NewADOConnection('ldap');
                $ldap->Connect($fs->prefs['ldap_server'], $fs->prefs['ldap_user'], $fs->prefs['ldap_password'], $fs->prefs['ldap_base_dn']);
                $ldap->SetFetchMode(ADODB_FETCH_ASSOC);

                $filter = '(|(' . $fs->prefs['ldap_userkey'] . '=' . $username . '))';

                $rs = $ldap->Execute($filter);
                if ($rs && $arr = $rs->FetchRow()) {
                    $compare_password = $password;
                    if (substr($arr['userPassword'], 0 ,5) == '{SHA}') {
                        $compare_password = base64_encode(pack('H*', sha1($compare_password)));
                        echo $compare_password."<br />";
                    } else if (substr($arr['userPassword'], 0 ,5) == '{MD5}') {
                        $compare_password = base64_encode(pack('H*', md5($compare_password)));
                    }

                    // make sure that the user has to provide the correct password if stored in LDAP
                    if (!isset($arr['userPassword']) || substr($arr['userPassword'], 5) == $compare_password) {
                        Backend::create_user($username, $password, $username,
                                             array_get($arr, 'jid', ''), array_get($arr, 'email', ''),
                                             1, 0, $fs->prefs['anon_group']);
                        $user_id = Flyspray::checkLogin($username, $password);
                    }
                }
            }

            $_SESSION['failed_login'] = Post::val('user_name');
            if ($user_id === -2) {
                return array(ERROR_RECOVER, L('usernotexist'), './');
            } elseif ($user_id === -1) {
                return array(ERROR_RECOVER, L('error23'), './');
            } elseif ($user_id == 0) {
                // just some extra check here so that never ever an account can get locked when it's already disabled
                // ... that would make it easy to get enabled
                $db->Execute('UPDATE {users} SET login_attempts = login_attempts+1 WHERE account_enabled = 1 AND user_name = ?',
                             array($username));
                // Lock account if failed too often for a limited amount of time
                $db->Execute('UPDATE {users} SET lock_until = ?, account_enabled = 0 WHERE login_attempts > ? AND user_name = ?',
                             array(time() + 60 * $fs->prefs['lock_for'], LOGIN_ATTEMPTS, $username));

                if ($db->Affected_Rows()) {
                    return array(ERROR_RECOVER, sprintf(L('error71'), $fs->prefs['lock_for']), CreateUrl('index'));
                } else {
                    return array(ERROR_RECOVER, L('error7'), './');
                }
            }
        }

        // give LDAP a chance
        if ($user_id > 0) {
            // Determine if the user should be remembered on this machine
            if (Post::has('remember_login')) {
                $cookie_time = time() + (60 * 60 * 24 * 30); // Set cookies for 30 days
            } else {
                $cookie_time = 0; // Set cookies to expire when session ends (browser closes)
            }

            $user = new User($user_id);

            // Set a couple of cookies
            Flyspray::setcookie('flyspray_userid', $user->id, $cookie_time);
            Flyspray::setcookie('flyspray_passhash', crypt($user->infos['user_pass'], $conf['general']['cookiesalt']), $cookie_time);

            // If the user had previously requested a password change, remove the magic url
            $remove_magic = $db->Execute("UPDATE {users} SET magic_url = '' WHERE user_id = ?",
                                          array($user->id));
            // Save for displaying
            if ($user->infos['login_attempts'] > 0) {
                $_SESSION['login_attempts'] = $user->infos['login_attempts'];
            }

            $db->Execute('UPDATE {users} SET login_attempts = 0 WHERE user_id = ?', array($user->id));
            return array(SUBMIT_OK, L('loginsuccessful'), Post::val('return_to'));
        }
    }

	function _onsubmit()
	{
        return $this->handle('action', $area = 'login');
	}

	function is_accessible()
	{
		return true;
	}
}

?>
