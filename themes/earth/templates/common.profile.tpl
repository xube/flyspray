<form action="<?php if ($do == 'myprofile'): ?>{CreateUrl('myprofile')}<?php else: ?>{CreateUrl(array('admin', 'user'), array('user_id' => $theuser->id))}<?php endif; ?>" method="post">
	<table class="table-main">
		<tr class="table-row-heading">
			<td colspan="2">{L('general')}</td>
		</tr>
		  <tr class="table-row-{$i = 0}">
				<td><h5>{L('realname')}</h5></td>
				<td class="table-right">
				  <input id="realname" class="text" type="text" name="real_name" size="30" maxlength="100"
					value="{Post::val('real_name', $theuser->infos['real_name'])}" />
				</td>
		  </tr>
		  <tr class="table-row-{++$i % 2}">
				<td><h5>{L('emailaddress')}</h5></td>
				<td class="table-right">
				  <input id="emailaddress" class="text" type="text" name="email_address" size="30" maxlength="100"
					value="{Post::val('email_address', $theuser->infos['email_address'])}" />
				</td>
		  </tr>
		  <tr class="table-row-{++$i % 2}">
				<td><h5>{L('jabberid')}</h5></td>
				<td class="table-right">
				  <input id="jabberid" class="text" type="text" name="jabber_id" size="30" maxlength="100"
					value="{Post::val('jabber_id', $theuser->infos['jabber_id'])}" />
				</td>
		  </tr>
		  <tr class="table-row-{++$i % 2}">
				<td><h5>{L('notifytype')}</h5></td>
				<td class="table-right">
				  <?php if ($fs->prefs['user_notify'] == '1'): ?>
				  <select id="notifytype" name="notify_type">
					{!tpl_options(array(L('none'),
										L('email'),
										L('jabber'),
										L('both')),
										Post::val('notify_type', $theuser->infos['notify_type']))}
				  </select>
				  <?php else: ?>
				  {L('setglobally')}
				  <?php endif; ?>

				   {L('notifyown')}{!tpl_checkbox('notify_own', Post::val('notify_own', !Post::val('action') && $theuser->infos['notify_own']), 'notify_own')}

				</td>
		  </tr>

		<tr class="table-row-heading">
			<td colspan="2">{L('date')}</td>
		</tr>
		 <tr class="table-row-{++$i % 2}">
				<td><h5>{L('dateformat')}</h5></td>
				<td class="table-right">
				  <input id="dateformat" class="text" name="dateformat" type="text" size="30" maxlength="30"
					value="{Post::val('dateformat', $theuser->infos['dateformat'])}" />
				</td>
		  </tr>
		  <tr class="table-row-{++$i % 2}">
				<td><h5>{L('dateformat_extended')}</h5></td>
				<td class="table-right">
				  <input id="dateformat_extended" class="text" name="dateformat_extended" type="text"
					size="30" maxlength="30" value="{Post::val('dateformat_extended', $theuser->infos['dateformat_extended'])}" />
				</td>
		  </tr>
		<tr class="table-row-{++$i % 2}">
				<td><h5>{L('timezone')}</h5></td>
				<td class="table-right">
				  <select id="time_zone" name="time_zone">
					{!tpl_options(tpl_TimeZones(), Post::val('time_zone', $theuser->infos['time_zone']))}
				  </select>
				</td>
		  </tr>

		<tr class="table-row-heading">
			<td colspan="2">{L('preferences')}</td>
		</tr>
		  <tr class="table-row-{++$i % 2}">
				<td><h5>{L('showcontact')}</h5></td>
				<td class="table-right">{!tpl_checkbox('show_contact', Req::val('show_contact', !Post::val('action') && $theuser->infos['show_contact']), 'show_contact')}</h5></td>
		  </tr>
		<tr class="table-row-{++$i % 2}">
				<td><h5>{L('tasksperpage')}</h5></td>
				<td class="table-right">
				  <select name="tasks_perpage" id="tasks_perpage">
					{!tpl_options(array(0 => L('unlimited'), 10 => 10, 25 => 25, 50 => 50, 100 => 100, 250 => 250), Post::val('tasks_perpage', $theuser->infos['tasks_perpage']))}
				  </select>
				</td>
		  </tr>
		 <tr class="table-row-{++$i % 2}">
				<td><h5>{L('preferredlanguage')}</h5></td>
				<td class="table-right">
				  <select name="lang_code" id="lang_code">
					{!tpl_options(array_merge(array(0 => L('any')), Flyspray::listLangs()), Post::val('lang_code', $user->infos['lang_code']), true)}
				  </select>
				</td>
		  </tr>
		  <tr class="table-row-{++$i % 2}">
				<td><h5>{L('defaultsortcolumn')}</h5></td>
				<td class="table-right">
					<label class="left notable">{!tpl_checkbox('defaultorder', $theuser->infos['defaultorder'] == 'asc', null, 'asc', null, 'radio')} {L('asc')}
					<label class="left notable">{!tpl_checkbox('defaultorder', $theuser->infos['defaultorder'] == 'desc', null, 'desc', null, 'radio')} {L('desc')}
					{!tpl_double_select('defaultsortcolumn[]', $proj->columns, explode(' ', $theuser->infos['defaultsortcolumn']), true)}
				</td>
		  </tr>
		 <tr class="table-row-{++$i % 2}">
				<td><h5>{L('notifyblacklist')}</h5></td>
				<td class="table-right">
					<select id="notify_blacklist" size="10" multiple="multiple" name="notify_blacklist[]">
					{!tpl_options(array(0 => L('none'),
										NOTIFY_TASK_OPENED     => L('taskopened'),
										NOTIFY_TASK_CHANGED    => L('pm.taskchanged'),
										NOTIFY_TASK_CLOSED     => L('taskclosed'),
										NOTIFY_TASK_REOPENED   => L('pm.taskreopened'),
										NOTIFY_DEP_ADDED       => L('pm.depadded'),
										NOTIFY_DEP_REMOVED     => L('pm.depremoved'),
										NOTIFY_COMMENT_ADDED   => L('commentadded'),
										NOTIFY_REL_ADDED       => L('relatedadded'),
										NOTIFY_OWNERSHIP       => L('ownershiptaken'),
										NOTIFY_PM_REQUEST      => L('pmrequest'),
										NOTIFY_PM_DENY_REQUEST => L('pmrequestdenied'),
										NOTIFY_NEW_ASSIGNEE    => L('newassignee'),
										NOTIFY_REV_DEP         => L('revdepadded'),
										NOTIFY_REV_DEP_REMOVED => L('revdepaddedremoved'),
										NOTIFY_ADDED_ASSIGNEES => L('assigneeadded')),
										Post::val('notify_types', explode(' ', $user->infos['notify_blacklist'])))}
					</select>
				</td>
		  </tr>
		  <tr class="table-row-heading">
			<td colspan="2">{L('user')}</td>
		</tr>
		  <?php if ($user->perms('is_admin')): ?>
		  <tr class="table-row-{++$i % 2}">
				<td><h5>{L('accountenabled')}</h5></td>
				<td class="table-right">{!tpl_checkbox('account_enabled', Post::val('account_enabled', !Post::val('action') && $theuser->infos['account_enabled']), 'accountenabled')}</h5></td>
		  </tr>
		  <tr class="table-row-{++$i % 2}">
				<td><h5>{L('deleteuser')}</h5></td>
				<td class="table-right">{!tpl_checkbox('delete_user', false, 'delete_user')}</h5></td>
		  </tr>
		  <?php endif; ?>
		  <tr class="table-row-{++$i % 2}">
				<td><h5>{L('globalgroup')}</h5></td>
				<td class="table-right">
				  <select id="groupin" class="adminlist" name="group_in" {tpl_disableif(!$user->perms('is_admin'))}>
					{!tpl_options($groups, Post::val('group_in', $theuser->infos['global_group']))}
				  </select>
				  <input type="hidden" name="old_global_id" value="{$theuser->infos['global_group']}" />
				</td>
		  </tr>
		  <tr class="table-row-{++$i % 2}">
				<td><h5>{L('groups')}</h5></td>
				<td class="table-right">
					<?php foreach ($all_groups as $project => $project_groups): ?>
					<strong>{$project}</strong>: {$project_groups[0]['group_name']} <br />
					<?php endforeach; ?>
				</td>
		  </tr>
		  <tr class="table-row-heading">
				<td colspan="2">{L('changepass')}</td>
		  </tr>
		  <?php if (!$user->perms('is_admin') || $user->id == $theuser->id): ?>
		  <tr class="table-row-{++$i % 2}">
				<td><h5>{L('oldpass')}</h5></td>
				<td class="table-right">
					<input id="oldpass" class="password" type="password" name="oldpass" value="{Post::val('oldpass')}" size="30" maxlength="100" />
				</td>
		  </tr>
		  <?php endif; ?>
		  <tr class="table-row-{++$i % 2}">
				<td><h5>{L('changepass')}</h5></td>
				<td class="table-right">
					<input id="changepass" class="password" type="password" name="changepass" value="{Post::val('changepass')}" size="30" maxlength="100" />
				</td>
		  </tr>
		  <tr class="table-row-{++$i % 2}">
				<td><h5>{L('confirmpass')}</h5></td>
				<td class="table-right">
					<input id="confirmpass" class="password" type="password" name="confirmpass" value="{Post::val('confirmpass')}" size="30" maxlength="100" />
				</td>
		  </tr>
	</table>
	<p>
	  <input type="hidden" name="action" value="edituser" />
	  <input type="hidden" name="do" value="{$do}" />
	  <input type="hidden" name="user_id" value="{$theuser->id}" />
	  <button type="submit">{L('updatedetails')}</button>
	</p>
</form>
