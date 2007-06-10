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
        global $fs, $db, $proj, $user, $conf, $baseurl;

        if (Post::val('user_name') == '' || Post::val('password') == '') {
            return array(ERROR_RECOVER, L('error8'), $baseurl);
        }

        // See if they provided the correct credentials...
        $username = Backend::clean_username(Post::val('user_name'));
        $password = Post::val('password');

        // Run the username and password through the login checker
        if ( ($user_id = Flyspray::checkLogin($username, $password)) < 1) {
            if ($fs->prefs['ldap_enabled']) {

                // Does user exist in LDAP server?
                $ldapconn = ldap_connect($fs->prefs['ldap_server']);

                ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);

                if ($fs->prefs['ldap_bind_method'] == 'anonymous') {

                    $ldapsearch = @ldap_search($ldapconn, $fs->prefs['ldap_basedn'], "{$fs->prefs['ldap_userkey']}={$username}");
                    $ldapentries = @ldap_get_entries($ldapconn, $ldapsearch);

                    foreach ($ldapentries as $ldapentry) {
                        $ldapbind = @ldap_bind($ldapconn, $ldapentry['uid'][0], $poassword);
                        if ($ldapbind) {
                            break;
                        }
                    }


                } elseif ($fs->prefs['ldap_bind_method'] == 'bind_dn') {

                    $ldapbind = @ldap_bind($ldapconn, $fs->prefs['ldap_bind_dn'], $fs->prefs['ldap_bind_pw']);
                    $ldapsearch = @ldap_search($ldapconn, $fs->prefs['ldap_basedn'], "{$fs->prefs['ldap_userkey']}={$username}");
                    $ldapentries = @ldap_get_entries($ldapconn, $ldapsearch);

                    foreach ($ldapentries as $ldapentry) {
                        $ldapbind = @ldap_bind($ldapconn, $ldapentry['uid'][0], $poassword);
                        if ($ldapbind) {
                            break;
                        }
                    }

                } elseif ($fs->prefs['ldap_bind_method'] == 'direct') {

                    $ldapbind = @ldap_bind($ldapconn, $fs->prefs['ldap_userkey'] . '=' . $username . ',' . $fs->prefs['ldap_basedn'], $password);
                }

                // if all OK, ad user to flyspray DB
                if ($ldapbind) {
                    Backend::create_user($username, $password, $username,
                                         '', '', 1, 0, $fs->prefs['anon_group']);
                    $user_id = Flyspray::checkLogin($username, $password);
                    @ldap_close($ldapconn);
                }
            }

            $_SESSION['failed_login'] = Post::val('user_name');
            if ($user_id === -2) {
                return array(ERROR_RECOVER, L('usernotexist'), $baseurl);
            } elseif ($user_id === -1) {
                return array(ERROR_RECOVER, L('error23'), $baseurl);
            } elseif ($user_id == 0) {
                // just some extra check here so that never ever an account can get locked when it's already disabled
                // ... that would make it easy to get enabled
                $db->Execute('UPDATE {users} SET login_attempts = login_attempts1 WHERE account_enabled = 1 AND user_name = ?',
                             array($username));
                // Lock account if failed too often for a limited amount of time
                $db->Execute('UPDATE {users} SET lock_until = ?, account_enabled = 0 WHERE login_attempts > ? AND user_name = ?',
                             array(time() + 60 * $fs->prefs['lock_for'], LOGIN_ATTEMPTS, $username));

                if ($db->Affected_Rows()) {
                    return array(ERROR_RECOVER, sprintf(L('error71'), $fs->prefs['lock_for']), CreateUrl('index'));
                } else {
                    return array(ERROR_RECOVER, L('error7'), $baseurl);
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
            Flyspray::setcookie('flyspray_passhash', hash_hmac('md5', $user->infos['user_pass'], $conf['general']['cookiesalt']), $cookie_time);

            // If the user had previously requested a password change, remove the magic url
            $remove_magic = $db->Execute("UPDATE {users} SET magic_url = '' WHERE user_id = ?",
                                          array($user->id));
            // Save for displaying
            if ($user->infos['login_attempts'] > 0) {
                $_SESSION['login_attempts'] = $user->infos['login_attempts'];
            }

            $db->Execute('UPDATE {users} SET login_attempts = 0 WHERE user_id = ?', array($user->id));
            // restore previous project cookie
            Flyspray::setCookie('flyspray_project', Post::val('project_id'));
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
