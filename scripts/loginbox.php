<?php get_language_pack($lang, 'loginbox'); ?>

<form action="scripts/authenticate.php" method="post">
<table class="login">
  <tr>
    <td><label><?php echo $loginbox_text['username'];?>
      <input type="text" name="username" size="20" maxlength="20"></label>
    </td>
    <td><label><?php echo $loginbox_text['password'];?>
      <input type="password" name="password" size="20" maxlength="20"></label>
    </td>
    <td>
          <?php
          if ($_GET['do']) {
            echo "<input type=\"hidden\" name=\"task\" value=\"{$_GET['id']}\">";
          };
          ?>
    <input class="adminbutton" type="submit" value="<?php echo $loginbox_text['login'];?>">
    </td>
  </tr>
</table>
</form>
