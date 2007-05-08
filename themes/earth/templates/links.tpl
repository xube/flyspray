<?php
	if ( $user->isAnon() ):
          $this->display('loginbox.tpl');
	else:
?>
<ul id="menu-list">
	<li class="first">
		<a id="profilelink" href="{CreateURL('myprofile')}" title="{L('editmydetails')}">
			<em>{$user->infos['real_name']} ({$user->infos['user_name']})</em>
		</a>
	</li>
	<li>
		<a id="lastsearchlink" href="#" accesskey="m" onclick="showhidestuff('mysearches');return false;" class="inactive">{L('mysearch')}</a>
		<div id="mysearches">
			<?php $this->display('links.searches.tpl'); ?>
		</div>
	</li>
	<?php if ($user->perms('is_admin')): ?>
		<li>
			<a id="optionslink" href="{CreateURL(array('admin', 'prefs'))}">{L('admintoolbox')}</a>
		</li>
	<?php endif; ?>

	<li>
		<a id="logoutlink" href="{CreateURL('authenticate', array('logout' => 1))}" accesskey="l">{L('logout')}</a>
	</li>
</ul>

<?php if (isset($_SESSION['was_locked'])): ?>
<p id="locked">{L('accountwaslocked')}</p>
<?php elseif (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] > 0): ?>
<p id="locked">{sprintf(L('failedattempts'), $_SESSION['login_attempts'])}</p>
<?php endif; unset($_SESSION['login_attempts'], $_SESSION['was_locked']); ?>

<?php endif; ?>