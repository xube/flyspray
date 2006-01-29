<div id="loginbox">
  <em>{$language['login']}</em>
  <form action="{$baseurl}?do=authenticate" method="post">
    <div>
      <label for="lbl_user_name">{$language['username']}</label>
      <input class="text" type="text" id="lbl_user_name" name="user_name" size="20" maxlength="20" />

      <label for="lbl_password">{$language['password']}</label>
      <input class="password" type="password" id="lbl_password" name="password" size="20" maxlength="20" />

      <label for="lbl_remember">{$language['rememberme']}</label>
      <input type="checkbox" id="lbl_remember" name="remember_login" />

      <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
      <button accesskey="l" type="submit">{$language['login']}</button>

      <span id="links">
        <?php
        if ($user->isAnon() && $fs->prefs['anon_reg']):
            if ($fs->prefs['spam_proof']):
        ?>
        <a href="{CreateURL('register','')}">{$language['register']}</a>
        <?php else: ?>
        <a href="{CreateURL('newuser','')}">{$language['register']}</a>
        <?php endif;
        endif; ?>
        <?php if ($user->isAnon() && $fs->prefs['user_notify']): ?>
        <a href="{CreateURL('lostpw','')}">{$language['lostpassword']}</a>
        <?php else: ?>
        <a id="lostpwlink" href="mailto:<?php foreach($admin_emails as $mail): ?>{str_replace('@', '#', $mail[0])},<?php endforeach;
        ?>?subject={rawurlencode($language['lostpwforfs'])}&amp;body={rawurlencode($language['lostpwmsg1'])}{$baseurl}{rawurlencode($language['lostpwmsg2'])}<?php
                 if(isset($_SESSION['failed_login'])):
                 ?>{rawurlencode($_SESSION['failed_login'])}<?php
                 else:
                 ?>&lt;{rawurlencode($language['yourusername'])}&gt;<?php
                 endif;
                 ?>{rawurlencode($language['regards'])}">{$language['lostpassword']}</a>
        <script type="text/javascript">var link = document.getElementById('lostpwlink');link.href=link.href.replace(/#/g,"@");</script>
        <?php endif; ?>
      </span>
    </div>
  </form>
</div>
<?php if ($proj->prefs['anon_open']): ?>
<div id="anonopen"><a href="?do=newtask&amp;project={$proj->id}">{$language['opentaskanon']}</a></div>
<?php endif; ?>
