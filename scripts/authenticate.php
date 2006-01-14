<?php

  /********************************************************\
  | User authentication (no output)                        |
  | ~~~~~~~~~~~~~~~~~~~                                    |
  \********************************************************/

$fs->get_language_pack('authenticate');

if (Req::val('action') == 'logout') {

    // Set cookie expiry time to the past, thus removing them
    $fs->setcookie('flyspray_userid',   '', time()-60);
    $fs->setcookie('flyspray_passhash', '', time()-60);
    $fs->setcookie('flyspray_project',  '', time()-60);
    if (Cookie::has(session_name())) {
        $fs->setcookie(session_name(), '', time()-60);
    }

    // Unset all of the session variables.
    $_SESSION = array();
    session_destroy();
    $fs->redirect($conf['general']['baseurl']);
}

if (Req::has('user_name') && Req::has('password')) {
    // Otherwise, they requested login.  See if they provided the correct credentials...
    $username = Req::val('user_name');
    $password = Req::val('password');

    // Run the username and password through the login checker
    if (!$fs->checkLogin($username, $password)) {
        $_SESSION['ERROR'] = $authenticate_text['loginfailed'];
        $_SESSION['failed_login'] = Req::val('user_name');
        $fs->redirect(Req::val('prev_page'));
    }
    else {
        $user_id = $fs->checkLogin($username, $password);

        // Determine if the user should be remembered on this machine
        if (Req::has('remember_login')) {
            $cookie_time = time() + (60 * 60 * 24 * 30); // Set cookies for 30 days
        }
        else {
            $cookie_time = 0; // Set cookies to expire when session ends (browser closes)
        }

        $user = new User($user_id);

        // Set a couple of cookies
        $fs->setcookie('flyspray_userid',   $user->id, $cookie_time);
        $fs->setcookie('flyspray_passhash', crypt($user->infos['user_pass'], $conf['general']['cookiesalt']), $cookie_time);

        // If the user had previously requested a password change, remove the magic url
        $remove_magic = $db->Query(
                "UPDATE {users} SET magic_url = '' WHERE user_id = ?",
                array($user->id)
            );

        $_SESSION['SUCCESS'] = $authenticate_text['loginsuccessful'];
    }
}
else {
    // If the user didn't provide both a username and a password, show this error:
    $_SESSION['ERROR'] = $authenticate_text['loginfailed'] . ' - ' . $authenticate_text['userandpass'];
}
$fs->redirect(Req::val('prev_page'));
?>
