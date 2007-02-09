<?php

  /********************************************************\
  | User authentication (no output)                        |
  | ~~~~~~~~~~~~~~~~~~~                                    |
  \********************************************************/

if (!defined('IN_FS')) {
    die('Do not access this file directly.');
}

if (Get::val('logout')) {
    $user->logout();
    Flyspray::Redirect($baseurl);
}

if (Post::val('user_name') != '' && Post::val('password') != '') {
    // Otherwise, they requested login.  See if they provided the correct credentials...
    $username = Post::val('user_name');
    $password = Post::val('password');

    // Run the username and password through the login checker
    if (($user_id = Flyspray::checkLogin($username, $password)) < 1) {
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
        if ($user_id == -1) {
            Flyspray::show_error(23);
        } elseif ($user_id == 0) {
            Flyspray::show_error(7);
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
        $remove_magic = $db->Query("UPDATE {users} SET magic_url = '' WHERE user_id = ?",
                                    array($user->id));

        $_SESSION['SUCCESS'] = L('loginsuccessful');
    }
}
else {
    // If the user didn't provide both a username and a password, show this error:
    Flyspray::show_error(8);
}

Flyspray::Redirect(Post::val('return_to'));
?>
