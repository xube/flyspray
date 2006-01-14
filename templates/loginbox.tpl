<?php $fs->get_language_pack('loginbox'); global $loginbox_text; ?>
<div id="loginbox">
  <em>{$loginbox_text['login']}</em>
  <form action="{$baseurl}?do=authenticate" method="post">
    <div>
      <label for="lbl_user_name">{$loginbox_text['username']}</label>
      <input class="maintext" type="text" id="lbl_user_name" name="user_name" size="20" maxlength="20" />

      <label for="lbl_password">{$loginbox_text['password']}</label>
      <input class="maintext" type="password" id="lbl_password" name="password" size="20" maxlength="20" />

      <label for="lbl_remember">{$loginbox_text['rememberme']}</label>
      <input type="checkbox" id="lbl_remember" name="remember_login" />

      <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
      <input accesskey="l" class="mainbutton" type="submit" value="{$loginbox_text['login']}" />

      <span id="links">
        <?php
        if ($user->isAnon() && $fs->prefs['anon_reg']):
            if ($fs->prefs['spam_proof']):
        ?>
        <a href="{$fs->CreateURL('register','')}">{$language['register']}</a>
        <?php else: ?>
        <a href="{$fs->CreateURL('newuser','')}">{$language['register']}</a>
        <?php endif;
        endif; ?>
        <?php if ($user->isAnon() && $fs->prefs['user_notify']): ?>
        <a href="{$fs->CreateURL('lostpw','')}">{$loginbox_text['lostpassword']}</a>
        <?php else: ?>
        <a id="lostpwlink" href="mailto:<?php foreach($admin_emails as $mail): ?>{str_replace('@', '#', $mail[0])},<?php endforeach;
        ?>?subject={rawurlencode($loginbox_text['lostpwforfs'])}&amp;body={rawurlencode($loginbox_text['lostpwmsg1'])}{$baseurl}{rawurlencode($loginbox_text['lostpwmsg2'])}<?php
                 if(isset($_SESSION['failed_login'])):
                 ?>{rawurlencode($_SESSION['failed_login'])}<?php
                 else:
                 ?>&lt;{rawurlencode($loginbox_text['yourusername'])}&gt;<?php
                 endif;
                 ?>{rawurlencode($loginbox_text['regards'])}">{$loginbox_text['lostpassword']}</a>
        <?php endif; ?><script type="text/javascript">var link = document.getElementById('lostpwlink');link.href=link.href.replace(/#/g,"@");</script>
      </span>
    </div>
  </form>
</div>
<?php if ($proj->prefs['anon_open']): ?>
<div id="anonopen"><a href="?do=newtask&amp;project={$proj->id}">{$language['opentaskanon']}</a></div>
<?php endif; ?>
