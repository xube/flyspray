<?php

   /*
   ------------------------------------------------------
   | This script allows users to request a notification |
   | that contains a link to a new password             |
   ------------------------------------------------------
*/

$lang = $flyspray_prefs['lang_code'];
$fs->get_language_pack($lang, 'admin');

  // Step One: user requests magic url
  if (!isset($_GET['magic'])
      && !isset($_COOKIE['flyspray_userid'])) {

    echo '<h3>' . $admin_text['lostpw'] . '</h3>' . "\n";
    echo $admin_text['lostpwexplain'] . "\n";

    echo '<br /><br />' . "\n";

    echo '<div class="admin">' . "\n";
    echo '<form action="' . $conf['general']['baseurl'] . 'index.php" method="post">' . "\n";
    echo '<input type="hidden" name="do" value="modify" />' . "\n";
    echo '<input type="hidden" name="action" value="sendmagic" />' . "\n";
    echo '<b>' . $admin_text['username'] . '</b>' . "\n";
    echo '<input class="admintext" type="text" name="user_name" size="20" maxlength="20" />' . "\n";
    echo '<input class="adminbutton" type="submit" value="' . $admin_text['sendlink'] . '" />' . "\n";
    echo '</form>' . "\n";
    echo '</div>' . "\n";


  // Step Two: user enters new password
} elseif (isset($_GET['magic'])
          && !isset($_COOKIE['flyspray_userid']))
{
   // Check that the magic url is valid
   $check_magic = $db->Query("SELECT * FROM {$dbprefix}users
                              WHERE magic_url = ?",
                              array($_GET['magic'])
                            );

   if (!$db->CountRows($check_magic))
   {
//       echo "<div class=\"redirectmessage\"><p><em>{$admin_text['badmagic']}</em></p></div>";
//       echo '<meta http-equiv="refresh" content="2; URL=index.php">';
      $_SESSION['ERROR'] = $admin_text['badmagic'];
      $fs->redirect("./");

   } else
   {
      echo '<h3>' . $admin_text['changepass'] . '</h3>' . "\n";

      echo '<br />' . "\n";

      echo '<form action="index.php" method="post">' . "\n";

      echo '<table class="admin">' . "\n";
      echo '<input type="hidden" name="do" value="modify" />' . "\n";
      echo '<input type="hidden" name="action" value="chpass" />' . "\n";
      echo '<input type="hidden" name="magic_url" value="' . $_GET['magic'] . '" />' . "\n";
      echo '<tr><td><b>' . $admin_text['changepass'] . '</b></td>' . "\n";
      echo '<td><input class="admintext" type="password" name="pass1" size="20" /></td></tr>' . "\n";
      echo '<tr><td><b>' . $admin_text['confirmpass'] . '</b></td>' . "\n";
      echo '<td><input class="admintext" type="password" name="pass2" size="20" /></tr>' . "\n";
      echo '<tr><td></td><td><input class="adminbutton" type="submit" value="' . $admin_text['savenewpass'] . '" /></td></tr>' . "\n";
      echo '</table>' . "\n";
      echo '</form>' . "\n";
      echo '</div>' . "\n";

   // End of checking magic url validity
   }

// End of checking for magic url
}
?>