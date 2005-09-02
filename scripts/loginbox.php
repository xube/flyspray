<?php
$fs->get_language_pack($lang, 'loginbox');
?>


<div id="loginbox">

<em><?php echo $loginbox_text['login'];?></em>

<form action="<?php echo $conf['general']['baseurl'];?>index.php?do=authenticate" method="post">
<div>
   <label><?php echo $loginbox_text['username'];?></label>
   <input class="maintext" type="text" name="user_name" size="20" maxlength="20" />

   <label><?php echo $loginbox_text['password'];?></label>
   <input class="maintext" type="password" name="password" size="20" maxlength="20" />

   <label><?php echo $loginbox_text['rememberme'];?></label>
   <input type="checkbox" name="remember_login" />

   <?php
   // This generates an URL so that the action script takes us back to the previous page
   $this_page = sprintf("%s",$_SERVER["REQUEST_URI"]);
   $this_page = str_replace('&', '&amp;', $this_page);
   echo '<input type="hidden" name="prev_page" value="' . $this_page . '" />';
   ?>

   <input class="mainbutton" type="submit" value="<?php echo $loginbox_text['login'];?>" />

   <span id="links">
      <?php
         if (!isset($_COOKIE['flyspray_userid']) && $flyspray_prefs['anon_reg'] == '1')
         {
            // If we want to use confirmation codes in the signup form
            if ($flyspray_prefs['spam_proof'] == '1')
            {
               echo '<a href="' . $fs->CreateURL('register','') . '">' . $language['register'] . '</a>';

            // ...and if we don't care about them
            } else
            {
               echo '<a href="' . $fs->CreateURL('newuser','') . '">' . $language['register'] . '</a>';
            }
         }

         echo '<a href="' . $fs->CreateURL('lostpw','') . '">' . $loginbox_text['lostpassword'] . '</a>';
         ?>
      </span>
</div>
   </form>

</div>
