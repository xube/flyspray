<?php get_language_pack($lang, 'loginbox'); ?>


<map id="loginboxform" name="loginboxform">
<form action="index.php?do=authenticate" method="post">
<table class="login">
  <tr>
    <td><label><?php echo $loginbox_text['username'];?>
      <input type="text" name="username" size="20" maxlength="20" /></label>
    </td>
    <td><label><?php echo $loginbox_text['password'];?>
      <input type="password" name="password" size="20" maxlength="20" /></label>
    </td>
    <td><label><input type="checkbox" name="remember_login" />
        <?php echo $loginbox_text['rememberme'];?></label>
    </td> 
    <td>
    
    <?php
    // This generates an URL to take us back to the previous page
    $page = sprintf("%s",$_SERVER["REQUEST_URI"]);
    echo '<input type="hidden" name="prev_page" value="' . $page . '" />';
    ?>
    
    <input class="adminbutton" type="submit" value="<?php echo $loginbox_text['login'];?>" />
    </td>
  </tr>

  </form>
  <br />

  <tr>
    <td colspan="4" style="text-align: center;">
    <?php
    // If we want to use confirmation codes in the signup form
    if (!$_COOKIE['flyspray_userid']
        && $flyspray_prefs['spam_proof'] == '1'
        && $flyspray_prefs['anon_reg'] == '1' ) {
    	
      echo "<p class=\"unregistered\"><a href=\"index.php?do=register\">{$language['register']}</a>&nbsp;&nbsp;|&nbsp;&nbsp;";

    // ...and if we don't care about them
    } elseif (!$_COOKIE['flyspray_userid']
              && $flyspray_prefs['spam_proof'] != '1'
              && $flyspray_prefs['anon_reg'] == '1') {
    
      echo "<p class=\"unregistered\"><a href=\"index.php?do=newuser\">{$language['register']}</a>&nbsp;&nbsp;|&nbsp;&nbsp;";

    };
    

    echo '<a href="?do=admin&amp;area=lostpw">' . $loginbox_text['lostpassword'] . '</a></p>';
    ?>
    </td>
  </tr>
</table>

</map>
