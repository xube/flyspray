<?php
// This script authenticates the user, and sets up a session.

$fs->get_language_pack($lang, 'authenticate');

// If logout was requested, log the user out.
if (isset($_REQUEST['action']) && $_REQUEST['action'] == "logout")
{
   // Set cookie expiry time to the past, thus removing them
   setcookie('flyspray_userid', '', time()-60, '/');
   setcookie('flyspray_passhash', '', time()-60, '/');

   // Set status message and redirect
   $_SESSION['SUCCESS'] = $authenticate_text['youareloggedout'];
   $fs->redirect($conf['general']['baseurl']);

// Otherwise, they requested login.  See if they provided the correct credentials...
} elseif (isset($_REQUEST['user_name']) && isset($_REQUEST['password']) )
{
   $username = $_REQUEST['user_name'];
   $password = $_REQUEST['password'];

   // Run the username and password through the login checker
   if (!$fs->checkLogin($username, $password))
   {
      $_SESSION['ERROR'] = $authenticate_text['loginfailed'];
      $fs->redirect($_REQUEST['prev_page']);

   } else
   {
      $user_id = $fs->checkLogin($username, $password);

      // Determine if the user should be remembered on this machine
      if (isset($_REQUEST['remember_login']) )
      {
         $cookie_time = time() + (60 * 60 * 24 * 30); // Set cookies for 30 days
      } else
      {
         $cookie_time = 0; // Set cookies to expire when session ends (browser closes)
      }

      $user = $fs->getUserDetails($user_id);

      // Set a couple of cookies
      setcookie('flyspray_userid', $user['user_id'], $cookie_time, "/");
      setcookie('flyspray_passhash', crypt($user['user_pass'], $cookiesalt), $cookie_time, "/");

      // If the user had previously requested a password change, remove the magic url
      $remove_magic = $db->Query("UPDATE {$dbprefix}users SET
                                  magic_url = ''
                                  WHERE user_id = ?",
                                  array($user['user_id'])
                                );

      $_SESSION['SUCCESS'] = $authenticate_text['loginsuccessful'];
      $fs->redirect($_REQUEST['prev_page']);
   // End of checking credentials
   }

} else
{
   // If the user didn't provide both a username and a password, show this error:
   $_SESSION['ERROR'] = $authenticate_text['loginfailed'] . ' - ' . $authenticate_text['userandpass'];
   $fs->redirect($_REQUEST['prev_page']);
}
?>