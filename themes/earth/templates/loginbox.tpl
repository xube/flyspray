<form action="{$_SERVER['SCRIPT_NAME']}?do=authenticate" method="post">
	<p>
		<label for="lbl_user_name">{L('username')}</label><input type="text" id="lbl_user_name" name="user_name" size="15"/>
	</p>
	<p>
		<label for="lbl_password">{L('password')}</label>
		<input type="password" id="lbl_password" name="password" maxlength="30" size="15"/>
	</p>
	<p>
		<label for="lbl_remember">{L('rememberme')}</label>
		<input type="checkbox" id="lbl_remember" name="remember_login" />  
		<input type="hidden" name="return_to" value="{$_SERVER['REQUEST_URI']}" />
		<button accesskey="l" type="submit">{L('login')}</button>
	</p>
</form>
<?php if ( $user->isAnon() && $fs->prefs['anon_reg'] || $fs->prefs['user_notify'] || isset( $admin_emails ) ): ?>
	<ul class="menu-list">

	<?php
		if ( $user->isAnon() && $fs->prefs['anon_reg'] ): ?>
		<li><a id="registerlink" href="{CreateURL('register')}">{L('register')}</a></li>
	<?php endif; ?>
	<?php if ($user->isAnon() && $fs->prefs['user_notify']): ?>
		<li><a id="forgotlink" href="{CreateURL('lostpw')}">{L('lostpassword')}</a></li>
	<?php elseif (isset($admin_emails)): ?>
		<li><a id="lostpwlink" href="mailto:<?php foreach($admin_emails as $mail): ?>{str_replace('@', '#', $mail[0])},<?php endforeach;
			?>?subject={rawurlencode(L('lostpwforfs'))}&amp;body={rawurlencode(L('lostpwmsg1'))}{$baseurl}{rawurlencode(L('lostpwmsg2'))}<?php
             if(isset($_SESSION['failed_login'])):
             ?>{rawurlencode($_SESSION['failed_login'])}<?php
             else:
             ?>&lt;{rawurlencode(L('yourusername'))}&gt;<?php
             endif;
             ?>{rawurlencode(L('regards'))}">{L('lostpassword')}</a></li>
		<script type="text/javascript">var link = document.getElementById('lostpwlink');link.href=link.href.replace(/#/g,"@");</script>
	<?php endif; ?>
	</ul>
<?php endif; ?>