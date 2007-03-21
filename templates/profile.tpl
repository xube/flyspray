<fieldset class="box"><legend>{L('profile')} {$theuser->infos['real_name']} ({$theuser->infos['user_name']})</legend>

<table id="profile">
  <tr>
    <th>{L('realname')}</th>
    <td>
      {$theuser->infos['real_name']}
    </td>
  </tr>
  <tr>
  <?php if (!$user->isAnon() && $theuser->infos['show_contact'] || $user->perms('is_admin')): ?>
    <th>{L('emailaddress')}</th>
    <td>
      <a href="mailto:{$theuser->infos['email_address']}">{$theuser->infos['email_address']}</a>
    </td>
  </tr>
  <tr>
    <th>{L('jabberid')}</th>
    <td>
      {$theuser->infos['jabber_id']}
    </td>
  </tr>
  <?php endif; ?>
  <tr>
    <th>{L('groups')}</th>
    <td>
      <?php foreach ($groups as $project => $project_groups): ?>
      <strong>{$project}</strong>: {$project_groups[0]['group_name']} <br />
      <?php endforeach; ?>
    </td>
  </tr>
  <tr>
    <th><a href="{$_SERVER['SCRIPT_NAME']}?opened={$theuser->id}&amp;status[]=">{L('tasksopened')}</a></th>
    <td>
      {$tasks}
    </td>
  </tr>
  <tr>
    <th>{L('comments')}</th>
    <td>
      {$comments}
    </td>
  </tr>
  <?php if ($theuser->infos['register_date']): ?>
  <tr>
    <th>{L('regdate')}</th>
    <td>
      {formatDate($theuser->infos['register_date'])}
    </td>
  </tr>
  <?php endif; ?>
</table>

</fieldset>
