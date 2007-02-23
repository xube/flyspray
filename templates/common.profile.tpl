  <form action="<?php if ($do == 'myprofile'): ?>{CreateUrl('myprofile')}<?php else: ?>{CreateUrl('edituser', $theuser->id)}<?php endif; ?>" method="post">
    <table class="box">
      <tr>
        <td><label for="realname">{L('realname')}</label></td>
        <td>
          <input id="realname" class="text" type="text" name="real_name" size="50" maxlength="100"
            value="{Post::val('real_name', $theuser->infos['real_name'])}" />
        </td>
      </tr>
      <tr>
        <td><label for="emailaddress">{L('emailaddress')}</label></td>
        <td>
          <input id="emailaddress" class="text" type="text" name="email_address" size="50" maxlength="100"
            value="{Post::val('email_address', $theuser->infos['email_address'])}" />
        </td>
      </tr>
      <tr>
        <td><label for="jabberid">{L('jabberid')}</label></td>
        <td>
          <input id="jabberid" class="text" type="text" name="jabber_id" size="50" maxlength="100"
            value="{Post::val('jabber_id', $theuser->infos['jabber_id'])}" />
        </td>
      </tr>
      <tr>
        <td><label for="notifytype">{L('notifytype')}</label></td>
        <td>
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
          {!tpl_checkbox('notify_own', Post::val('notify_own', !Post::val('action') && $theuser->infos['notify_own']), 'notify_own')}
          <label class="left notable" for="notify_own">{L('notifyown')}</label>
        </td>
      </tr>
      <tr>
        <td><label for="dateformat">{L('dateformat')}</label></td>
        <td>
          <input id="dateformat" class="text" name="dateformat" type="text" size="40" maxlength="30"
            value="{Post::val('dateformat', $theuser->infos['dateformat'])}" />
        </td>
      </tr>
      <tr>
        <td><label for="dateformat_extended">{L('dateformat_extended')}</label></td>
        <td>
          <input id="dateformat_extended" class="text" name="dateformat_extended" type="text"
            size="40" maxlength="30" value="{Post::val('dateformat_extended', $theuser->infos['dateformat_extended'])}" />
        </td>
      </tr>
      <tr>
        <td><label for="tasks_perpage">{L('tasksperpage')}</label></td>
        <td>
          <select name="tasks_perpage" id="tasks_perpage">
            {!tpl_options(array(0 => L('unlimited'), 10 => 10, 25 => 25, 50 => 50, 100 => 100, 250 => 250), Post::val('tasks_perpage', $theuser->infos['tasks_perpage']))}
          </select>
        </td>
      </tr>
      <tr>
        <td><label for="time_zone">{L('timezone')}</label></td>
        <td>
          <select id="time_zone" name="time_zone">
            <?php
              $times = array();
              for ($i = -12; $i <= 13; $i++) {
                $times[$i] = L('GMT') . (($i == 0) ? ' ' : (($i > 0) ? '+' . $i : $i));
              }
            ?>
            {!tpl_options($times, Post::val('time_zone', $theuser->infos['time_zone']))}
          </select>
        </td>
      </tr>
      <tr>
        <td><label>{L('defaultsortcolumn')}</label></td>
        <td>
            <label class="left notable">{!tpl_checkbox('defaultorder', $theuser->infos['defaultorder'] == 'asc', null, 'asc', null, 'radio')} {L('asc')}</label>
            <label class="left notable">{!tpl_checkbox('defaultorder', $theuser->infos['defaultorder'] == 'desc', null, 'desc', null, 'radio')} {L('desc')}</label>
            {!tpl_double_select('defaultsortcolumn[]', $proj->columns, explode(' ', $theuser->infos['defaultsortcolumn']), true)}
        </td>
      </tr>
      <tr>
        <td><label>{L('notifyblacklist')}</label></td>
        <td>
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
                                Post::val('notify_types', Flyspray::int_explode(' ', $user->infos['notify_blacklist'])))}
            </select>
        </td>
      </tr>
      <tr>
        <td colspan="2"><hr /></td>
      </tr>
      <?php if ($user->perms('is_admin')): ?>
      <tr>
        <td><label for="accountenabled">{L('accountenabled')}</label></td>
        <td>{!tpl_checkbox('account_enabled', Post::val('account_enabled', !Post::val('action') && $theuser->infos['account_enabled']), 'accountenabled')}</td>
      </tr>
      <tr>
        <td><label for="delete_user">{L('deleteuser')}</label></td>
        <td>{!tpl_checkbox('delete_user', false, 'delete_user')}</td>
      </tr>
      <?php endif; ?>
      <tr>
        <td><label for="groupin">{L('globalgroup')}</label></td>
        <td>
          <select id="groupin" class="adminlist" name="group_in" {tpl_disableif(!$user->perms('is_admin'))}>
            {!tpl_options($groups, Post::val('group_in', $theuser->infos['global_group']))}
          </select>
          <input type="hidden" name="old_global_id" value="{$theuser->infos['global_group']}" />
        </td>
      </tr>
      <tr>
        <td><label>{L('groups')}</label></td>
        <td>
            <?php foreach ($all_groups as $project => $project_groups): ?>
            <strong>{$project}</strong>: {$project_groups[0]['group_name']} <br />
            <?php endforeach; ?>
        </td>
      </tr>
      <tr>
        <td colspan="2"><hr /></td>
      </tr>
      <?php if (!$user->perms('is_admin') || $user->id == $theuser->id): ?>
      <tr>
        <td><label for="oldpass">{L('oldpass')}</label></td>
        <td><input id="oldpass" class="password" type="password" name="oldpass" value="{Post::val('oldpass')}" size="40" maxlength="100" /></td>
      </tr>
      <?php endif; ?>
      <tr>
        <td><label for="changepass">{L('changepass')}</label></td>
        <td><input id="changepass" class="password" type="password" name="changepass" value="{Post::val('changepass')}" size="40" maxlength="100" /></td>
      </tr>
      <tr>
        <td><label for="confirmpass">{L('confirmpass')}</label></td>
        <td><input id="confirmpass" class="password" type="password" name="confirmpass" value="{Post::val('confirmpass')}" size="40" maxlength="100" /></td>
      </tr>
      <tr>
        <td colspan="2" class="buttons">
          <input type="hidden" name="action" value="{Req::val('action', $do . '.edituser')}" />
          <input type="hidden" name="user_id" value="{$theuser->id}" />
          <button type="submit">{L('updatedetails')}</button>
        </td>
      </tr>
    </table>
  </form>
</fieldset>