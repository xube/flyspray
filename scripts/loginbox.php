<?php
require("lang/$lang/loginbox.php");
?>

      <form action="scripts/authenticate.php" method="post">
      <table class="admin" style="margin-top: 25px;">
        <tr>
          <td class="adminlabel"><?php echo $loginbox_text['username'];?></td>
          <td>
          <input class="admintext" type="text" name="username" size="20" maxlength="20" />
          </td>
          <td>
          &nbsp;&nbsp;&nbsp;&nbsp;
          </td>
          <td  class="adminlabel"><?php echo $loginbox_text['password'];?></td>
          <td>
          <input class="admintext" type="password" name="password" size="20" maxlength="20" />
          </td>
          <td>
          <?php
          if ($_GET['do']) {
            echo "<input type=\"hidden\" name=\"task\" value=\"{$_GET['id']}\" />";
          };
          ?>
          <input class="adminbutton" type="submit" value="<?php echo $loginbox_text['login'];?>" />
          </td>
        </tr>
      </table>
      </form>
