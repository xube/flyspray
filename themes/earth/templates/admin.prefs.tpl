<h1>{L('admintoolboxlong')} | {L('preferences')}</h3>

<form action="{CreateURL(array('admin', 'prefs'))}" method="post">
	<ul id="submenu">
	   <li><a href="#general">{L('general')}</a></li>
	   <li><a href="#userregistration">{L('userregistration')}</a></li>
	   <li><a href="#notifications">{L('notifications')}</a></li>
	   <li><a href="#lookandfeel">{L('lookandfeel')}</a></li>
	</ul>

	<div id="general" class="tab">
		<table class="table-main">
			<tr class="table-row-{$i = 0}">
				<td><h5>{L('pagetitle')}</h5></td>
				<td class="table-right">
					<input id="pagetitle" name="page_title" type="text" class="text" size="40" maxlength="100" value="{$fs->prefs['page_title']}" />
				</td>
			</tr>
			<tr class="table-row-{++$i % 2}">
				  <td><h5>{L('defaultproject')}</h5></td>
				  <td class="table-right">
					<select id="defaultproject" name="default_project">
					  {!tpl_options(array_merge(array(0 => L('allprojects')), Flyspray::listProjects()), $fs->prefs['default_project'])}
					</select>
				  </td>
			</tr>
			<tr class="table-row-{++$i % 2}">
				  <td><h5>{L('language')}</h5></td>
				  <td class="table-right">
					<select id="langcode" name="lang_code">
					  {!tpl_options(Flyspray::listLangs(), $fs->prefs['lang_code'], true)}
					</select>
				  </td>
			</tr>
			<tr class="table-row-{++$i % 2}">
				  <td><h5>{L('dateformat')}</h5></td>
				  <td class="table-right">
					<input id="dateformat" name="dateformat" type="text" class="text" size="40" maxlength="30" value="{$fs->prefs['dateformat']}" />
				  </td>
			</tr>
			<tr class="table-row-{++$i % 2}">
				  <td><h5>{L('dateformat_extended')}</h5></td>
				  <td class="table-right">
					<input id="dateformat_extended" name="dateformat_extended" class="text" type="text" size="40" maxlength="30" value="{$fs->prefs['dateformat_extended']}" />
				  </td>
			</tr>
			<tr class="table-row-{++$i % 2}">
				  <td><h5>{L('cache_feeds')}</h5></td>
				  <td class="table-right">
					<select id="cache_feeds" name="cache_feeds">
					{!tpl_options(array('0' => L('no_cache'), '1' => L('cache_disk'), '2' => L('cache_db')), $fs->prefs['cache_feeds'])}
					</select>
				  </td>
			</tr>
			<tr class="table-row-{++$i % 2}">
				  <td><h5>{L('allowanonuserlist')}</h5></td>
				  <td class="table-right">{!tpl_checkbox('anon_userlist', $fs->prefs['anon_userlist'], 'anon_userlist')}</h5></td>
			</tr>
			<tr class="table-row-{++$i % 2}">
				  <td><h5>{L('resolutionlist')}</h5></td>
				 <td class="table-right">
					<select id="resolution_list" name="resolution_list">
					{!tpl_options($lists, $fs->prefs['resolution_list'])}
					</select>
				  </td>
			</tr>
			<?php if ($fs->prefs['resolution_list']): ?>
			<tr class="table-row-{++$i % 2}">
				  <td><h5>{L('duplicateres')}</h5></td>
				  <td class="table-right">
					<select id="resolution_dupe" name="resolution_dupe">
					{!tpl_options($proj->get_list(array('list_id' => $fs->prefs['resolution_list'])), $fs->prefs['resolution_dupe'])}
					</select>
				  </td>
			</tr>
        <?php endif; ?>
		</table>
    </div>

    <div id="userregistration" class="tab">
		  <table class="table-main">
				<tr class="table-row-{$i = 0}">
					  <td><h5>{L('anonreg')}</h5></td>
					  <td class="table-right">{!tpl_checkbox('anon_reg', $fs->prefs['anon_reg'], 'allowusersignups')}</h5></td>
				</tr>
				<tr class="table-row-{++$i % 2}">
					  <td><h5>{L('spamproof')}</h5></td>
					  <td class="table-right">{!tpl_checkbox('spam_proof', $fs->prefs['spam_proof'], 'spamproof')}</h5></td>
				</tr>
				<tr class="table-row-{++$i % 2}">
					  <td><h5>{L('notify_registration')}</h5></td>
					  <td class="table-right">{!tpl_checkbox('notify_registration', $fs->prefs['notify_registration'], 'notify_registration')}</h5></td>
				</tr>
				<tr class="table-row-{++$i % 2}">
					  <td><h5>{L('defaultglobalgroup')}</h5></td>
					  <td class="table-right">
							<select id="defaultglobalgroup" name="anon_group">
							  {!tpl_options(Flyspray::listGroups(), $fs->prefs['anon_group'])}
							</select>
					  </td>
				</tr>
				<?php if (function_exists('ldap_connect')): ?>
				<tr class="table-row-heading">
					<th colspan="2">
						<hr />
						{L('ldap')}
				  </th>
				</tr>
				<tr class="table-row-{++$i % 2}">
					  <td><h5>{L('ldapenabled')}</h5></td>
					  <td class="table-right">{!tpl_checkbox('ldap_enabled', $fs->prefs['ldap_enabled'], 'ldap_enabled')}</h5></td>
				</tr>
				<tr class="table-row-{++$i % 2}">
					  <td><h5>{L('ldapserver')}</h5></td>
					  <td class="table-right"><input id="ldap_server" name="ldap_server" class="text" type="text" size="40" value="{$fs->prefs['ldap_server']}" /></td>
				</tr>
				<tr class="table-row-{++$i % 2}">
					  <td><h5>{L('ldapbasedn')}</h5></td>
					  <td class="table-right"><input id="ldap_base_dn" name="ldap_base_dn" class="text" type="text" size="40" value="{$fs->prefs['ldap_base_dn']}" /></td>
				</tr>
				<tr class="table-row-{++$i % 2}">
					  <td><h5>{L('ldapuserkey')}</h5></td>
					  <td class="table-right"><input id="ldap_userkey" name="ldap_userkey" class="text" type="text" size="40" value="{$fs->prefs['ldap_userkey']}" /></td>
				</tr>
				<tr class="table-row-{++$i % 2}">
					  <td><h5>{L('ldapuser')}</h5></td>
					  <td class="table-right"><input id="ldap_user" name="ldap_user" class="text" type="text" size="40" value="{$fs->prefs['ldap_user']}" /></td>
				</tr>
				<tr class="table-row-{++$i % 2}">
					  <td><h5>{L('ldappassword')}</h5></td>
					  <td class="table-right"><input id="ldap_password" name="ldap_password" class="text" type="text" size="40" value="{$fs->prefs['ldap_password']}" /></td>
				</tr>
		  </table>
		  <?php else: ?>
		  </table>
      <p><em>{L('ldapnotsupported')}</em></p>
      <?php endif; ?>
    </div>

    <div id="notifications" class="tab">
		  <table class="table-main">
				<tr class="table-row-{$i = 0}">
					<td><h5>{L('forcenotify')}</h5></td>
					<td class="table-right">
						<select id="usernotify" name="user_notify">
						  {!tpl_options(array(L('neversend'), L('userchoose'), L('email'), L('jabber')), $fs->prefs['user_notify'])}
						</select>
					</td>
				</tr>
				<tr class="table-row-{++$i % 2}">
					<td><h5>{L('sendbackground')}</h5></td>
					<td class="table-right">
						  {!tpl_checkbox('send_background', $fs->prefs['send_background'], 'send_background', 1)}
					</td>
				</tr>
				<tr class="table-row-heading">
					<td colspan="2">
						{L('emailnotify')}
					</td>
				</tr>
				<tr class="table-row-{++$i % 2}">
					<td><h5>{L('fromaddress')}</h5></td>
					<td class="table-right">
						<input id="adminemail" name="admin_email" class="text" type="text" size="40" maxlength="100" value="{$fs->prefs['admin_email']}" />
					</td>
				</tr>
                <tr class="table-row-{++$i % 2}">
                  <td><h5>{L('bounceaddress')}</h5></td>
                  <td>
                    <input id="bounceemail" name="bounce_email" class="text" type="text" size="40" maxlength="100" value="{$fs->prefs['bounce_email']}" />
                  </td>
                </tr>
				<tr class="table-row-{++$i % 2}">
					<td><h5>{L('smtpserver')}</h5></td>
					<td class="table-right">
						<input id="smtpserv" name="smtp_server" class="text" type="text" size="40" maxlength="100" value="{$fs->prefs['smtp_server']}" />
						<?php if (extension_loaded('openssl')) : ?>
						{!tpl_checkbox('email_ssl', $fs->prefs['email_ssl'], 'email_ssl')} <label class="inline" for="email_ssl">{L('ssl')}
						{!tpl_checkbox('email_tls', $fs->prefs['email_tls'], 'email_tls')} <label class="inline" for="email_tls">{L('tls')}
						<?php endif; ?>
					</td>
				</tr>
				<tr class="table-row-{++$i % 2}">
					<td><h5>{L('smtpuser')}</h5></td>
					<td class="table-right">
						<input id="smtpuser" name="smtp_user" class="text" type="text" size="40" maxlength="100" value="{$fs->prefs['smtp_user']}" />
					</td>
				</tr>
				<tr class="table-row-{++$i % 2}">
					<td><h5>{L('smtppass')}</h5></td>
					<td class="table-right">
						<input id="smtppass" name="smtp_pass" class="text" type="text" size="40" maxlength="100" value="{$fs->prefs['smtp_pass']}" />
					</td>
				</tr>
				<?php if (extension_loaded('xml')) : ?>
				<tr class="table-row-heading">
					<td colspan="2">
						{L('jabbernotify')}
					</td>
				</tr>
				<tr class="table-row-{++$i % 2}">
					<td><h5>{L('jabberserver')}</h5></td>
					<td class="table-right">
						<input id="jabberserver" class="text" type="text" name="jabber_server" size="40" maxlength="100" value="{$fs->prefs['jabber_server']}" />
						<?php if (extension_loaded('openssl')) : ?>
						{!tpl_checkbox('jabber_ssl', $fs->prefs['jabber_ssl'], 'jabber_ssl')} <label class="inline" for="jabber_ssl">{L('ssl')}
						<?php endif; ?>
					</td>
				</tr>
				<tr class="table-row-{++$i % 2}">
				  <td><h5>{L('jabberport')}</h5></td>
				  <td class="table-right">
					<input id="jabberport" class="text" type="text" name="jabber_port" size="40" maxlength="100" value="{$fs->prefs['jabber_port']}" />
				  </td>
				</tr>
				<tr class="table-row-{++$i % 2}">
				  <td><h5>{L('jabberuser')}</h5></td>
				  <td class="table-right">
					<input id="jabberusername" class="text" type="text" name="jabber_username" size="40" maxlength="100" value="{$fs->prefs['jabber_username']}" />
				  </td>
				</tr>
				<tr class="table-row-{++$i % 2}">
				  <td><h5>{L('jabberpass')}</h5></td>
				  <td class="table-right">
					<input id="jabberpassword" name="jabber_password" class="password" type="password" size="40" maxlength="100" value="{$fs->prefs['jabber_password']}" />
				  </td>
				</tr>
			<?php else: ?>
				<tr class="table-row-{++$i % 2}">
					<td>
						<em>{L('jabbernotsupported')}</em>
					</td>
				</tr>
			<?php endif; ?>
		  </table>
    </div>

    <div id="lookandfeel" class="tab">
		 <table class="table-main">
				<tr class="table-row-{$i = 0}">
					<td><h5>{L('globaltheme')}</h5></td>
					  <td class="table-right">
						<select id="globaltheme" name="global_theme">
						  {!tpl_options(Flyspray::listThemes(), $fs->prefs['global_theme'], true)}
						</select>
					  </td>
				</tr>
				<tr class="table-row-{++$i % 2}">
					  <td><h5>{L('visiblecolumns')}</h5></td>
					  <td class="table-right">
						<?php // Set the selectable column names
						$selectedcolumns = explode(' ', $fs->prefs['visible_columns']);
						?>
						{!tpl_double_select('visible_columns', $proj->columns, $selectedcolumns)}
					  </td>
				</tr>
		  </table>
    </div>

    <div class="tbuttons">
      <input type="hidden" name="action" value="globaloptions" />
      <button type="submit">{L('saveoptions')}</button>

      <button type="reset">{L('resetoptions')}</button>
    </div>

</form>