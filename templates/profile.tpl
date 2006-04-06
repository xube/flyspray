<fieldset class="admin"><legend>{L('profile')} {$theuser->infos['real_name']} ({$theuser->infos['user_name']})</legend>

<table id="profile">
  <tr>
    <th>{L('realname')}</th>
    <td>
      {$theuser->infos['real_name']}
    </td>
  </tr>
  <tr>
  <?php if (!$user->isAnon()): ?>
    <th>{L('emailaddress')}</th>
    <td>
      <a href="mailto:{$theuser->infos['email_address']}">{$theuser->infos['email_address']}</a>
    </td>
  </tr>
  <?php endif; ?>
  <tr>
    <th>{L('jabberid')}</th>
    <td>
      {$theuser->infos['jabber_id']}
    </td>
  </tr>
  <tr>
    <th>{L('globalgroup')}</th>
    <td>
      {$groups[Flyspray::array_find('group_id', $theuser->infos['global_group'], $groups)]['group_name']}
    </td>
  </tr>
  <?php if ($proj->id): ?>
  <tr>
    <th>{L('projectgroup')}</th>
    <td>
      <?php if ($theuser->infos['project_group']): ?>
      {$project_groups[Flyspray::array_find('group_id', $theuser->infos['project_group'], $project_groups)]['group_name']}
      <?php else: ?>
      {L('none')}
      <?php endif; ?>
    </td>
  </tr>
  <tr>
    <th><a href="{$baseurl}?opened={$theuser->id}">{L('tasksopened')}</a></th>
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
  <?php endif; ?>
  <?php if ($theuser->infos['register_date']): ?>
  <tr>
    <th>{L('regdate')}</th>
    <td>
      {formatDate($user->infos['register_date'])}
    </td>
  </tr> 
  <?php endif; ?>
</table>
    
</fieldset>