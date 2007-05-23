<!--
  <span id="links">
    <?php if ($user->isAnon() && $fs->prefs['anon_reg']): ?>
    <a id="registerlink" href="{CreateURL('register')}">{L('register')}</a>
    <?php endif; ?>
    <?php if ($user->isAnon() && $fs->prefs['user_notify']): ?>
    <a id="forgotlink" href="{CreateURL('lostpw')}">{L('lostpassword')}</a>
    <?php elseif (isset($admin_emails)): ?>
    <a id="lostpwlink" href="mailto:<?php foreach($admin_emails as $mail): ?>{str_replace('@', '#', reset($mail))},<?php endforeach;
    ?>?subject={rawurlencode(L('lostpwforfs'))}&amp;body={rawurlencode(L('lostpwmsg1'))}{$baseurl}{rawurlencode(L('lostpwmsg2'))}<?php
             if(isset($_SESSION['failed_login'])):
             ?>{rawurlencode($_SESSION['failed_login'])}<?php
             else:
             ?>&lt;{rawurlencode(L('yourusername'))}&gt;<?php
             endif;
             ?>{rawurlencode(L('regards'))}">{L('lostpassword')}</a>
    <script type="text/javascript">var link = document.getElementById('lostpwlink');link.href=link.href.replace(/#/g,"@");</script>
    <?php endif; ?>
  </span>
	-->