<?php $fs->get_language_pack('loginbox'); global $loginbox_text; ?>
<div id="loginbox">
  <em>{$loginbox_text['login']}</em>
  <form action="{$baseurl}index.php?do=authenticate" method="post">
    <div>
      <label>{$loginbox_text['username']}</label>
      <input class="maintext" type="text" name="user_name" size="20" maxlength="20" />

      <label>{$loginbox_text['password']}</label>
      <input class="maintext" type="password" name="password" size="20" maxlength="20" />

      <label>{$loginbox_text['rememberme']}</label>
      <input type="checkbox" name="remember_login" />

      <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
      <input class="mainbutton" type="submit" value="{$loginbox_text['login']}" />

      <span id="links">
        <?php
        if (!Cookie::has('flyspray_userid') && $fs->prefs['anon_reg']):
            if ($fs->prefs['spam_proof']):
        ?>
        <a href="{$fs->CreateURL('register','')}">{$language['register']}</a>
        <?php else: ?>
        <a href="{$fs->CreateURL('newuser','')}">{$language['register']}</a>
        <?php endif;
        endif; ?>
        <a href="{$fs->CreateURL('lostpw','')}">{$loginbox_text['lostpassword']}</a>
      </span>
    </div>
  </form>
</div>
<?php if ($project_prefs['anon_open']): ?>
<div id="anonopen"><a href="?do=newtask&amp;project={$project_id}">{$language['opentaskanon']}</a></div>
<?php endif; ?>
