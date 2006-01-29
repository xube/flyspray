<?php 
$users_in = array();
foreach($groups as $group): ?>
<a class="grouptitle" href="{CreateURL('editgroup', $group['group_id'], ($proj->id) ? 'pm' : 'admin')}">{$group['group_name']}</a>
<p>{$group['group_desc']}</p>
<form action="{$baseurl}" method="post">
  <div>
    <input type="hidden" name="do" value="modify" />
    <input type="hidden" name="action" value="movetogroup" />
    <input type="hidden" name="old_group" value="{$group['group_id']}" />
    <input type="hidden" name="project_id" value="{$proj->id}" />
    <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
  </div>

  <table class="userlist">
    <tr>
      <th></th>
      <th>{$language['username']}</th>
      <th>{$language['realname']}</th>
      <th>{$language['accountenabled']}</th>
    </tr>
    <?php
    foreach($proj->listUsersIn($group['group_id']) as $usr): ?>
    <tr>
      <td>{!tpl_checkbox('users['.$usr['user_id'].']')}</td>
      <td><a href="{CreateURL('user', $usr['user_id'])}">{$usr['user_name']}</a></td>
      <td>{$usr['real_name']}</td>
      <?php if ($user->infos['account_enabled']): ?>
      <td>{$language['yes']}</td>
      <?php else: ?>
      <td>{$language['no']}</td>
      <?php endif; ?>
    </tr>
    <?php
    $users_in[] = $usr['user_id'];
    endforeach;
    ?>

    <tr>
      <td colspan="4">
        <button type="submit">{$language['moveuserstogroup']}</button>
        <select class="adminlist" name="switch_to_group">
          <?php if ($proj->id): ?>
          <option value="0">{$language['nogroup']}</option>
          <?php endif; ?>
          {!tpl_options($proj->listGroups())}
        </select>
      </td>
    </tr>
  </table>
</form>

<?php endforeach; ?>

<?php if ($proj->id): ?>
<form action="{$baseurl}" method="post">
  <div>
    <input type="hidden" name="do" value="modify" />
    <input type="hidden" name="action" value="addtogroup" />
    <input type="hidden" name="project_id" value="{$proj->id}" />
    <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
    <select class="adminlist" name="user_list[]" multiple="multiple" size="15">
      {!tpl_options($proj->UserList($users_in, true))}
    </select>
    <br />
    <button type="submit">{$language['addtogroup']}</button>
    <select name="add_to_group">
      {!tpl_options($proj->listGroups())}
    </select>
  </div>
</form>
<?php endif; ?>
