<?php

/*
   ------------------------------------------------------
   | This script allows users to request a notification |
   | that contains a link to a new password             |
   ------------------------------------------------------
*/

$fs->get_language_pack('admin');

if (!Get::has('magic') && !Cookie::has('flyspray_userid')):     // Step One: user requests magic url
?>
    <h3><?php echo $admin_text['lostpw'] ?></h3>
    <?php echo $admin_text['lostpwexplain'] ?>
    <br /><br />

    <div class="admin">
      <form action="<?php echo $baseurl ?>index.php" method="post">
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="sendmagic" />
        <b><?php echo $admin_text['username'] ?></b>
        <input class="admintext" type="text" name="user_name" size="20" maxlength="20" />
        <input class="adminbutton" type="submit" value="<?php echo $admin_text['sendlink'] ?>" />
      </form>
    </div>
<?php
elseif (!Get::has('magic') && !Cookie::has('flyspray_userid')): // Step Two: user enters new password
    // Check that the magic url is valid
    $check_magic = $db->Query(
            "SELECT * FROM {users} WHERE magic_url = ?",
            array($_GET['magic']));

    if (!$db->CountRows($check_magic)) {
        $_SESSION['ERROR'] = $admin_text['badmagic'];
        $fs->redirect('./');
    }
?>
    <h3><?php echo $admin_text['changepass'] ?></h3>
    <br />

    <div class="admin">
    <form action="index.php" method="post">
      <table>
        <tr>
          <td>
            <b><?php echo $admin_text['changepass'] ?></b>
            <input type="hidden" name="do" value="modify" />
            <input type="hidden" name="action" value="chpass" />
            <input type="hidden" name="magic_url" value="<?php echo Get::val('magic'); ?>" />
          </td>
          <td><input class="admintext" type="password" name="pass1" size="20" /></td>
        </tr>
        <tr>
          <td><b><?php echo $admin_text['confirmpass'] ?></b></td>
          <td><input class="admintext" type="password" name="pass2" size="20" /></td>
        </tr>
        <tr>
          <td></td>
          <td><input class="adminbutton" type="submit" value="<?php echo $admin_text['savenewpass'] ?>" /></td>
        </tr>
      </table>
    </form>
    </div>
<?php
endif;                                                          // End of checking for magic url
?>
