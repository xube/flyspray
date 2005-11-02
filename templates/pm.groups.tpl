<div id="toolbox">
  <h3>{$pm_text['pmtoolbox']} :: {$project_prefs['project_title']} : {$pm_text['groupmanage']}</h3>
  <fieldset class="admin">
    <legend>{$admin_text['usergroups']}</legend>
    <p><a href="{$fs->CreateURL('newgroup', $project_id)}">{$admin_text['newgroup']}</a></p>

    <?php foreach($fs->listGroupsIn($project_id) as $group): ?>
    <a class="grouptitle" href="{$fs->CreateURL('projgroup', $group['group_id'])}">{$group['group_name']}</a>
    <p>{$group['group_desc']}</p>
    <form action="{$baseurl}" method="post">
      <div>
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="movetogroup" />
        <input type="hidden" name="old_group" value="{$group['group_id']}" />
        <input type="hidden" name="project_id" value="{$project_id}" />
        <input type="hidden" name="prev_page" value="{$this_page}" />
      </div>
    
      <table class="userlist">
        <tr>
          <th></th>
          <th>{$admin_text['username']}</th>
          <th>{$admin_text['realname']}</th>
          <th>{$admin_text['accountenabled']}</th>
        </tr>
        <?php foreach($fs->listUsersInGroup($project_id, $group['group_id']) as $user): ?>
        <tr>
          <td>{!tpl_checkbox('users['.$user['user_id'].']')}</td>
          <td><a href="{$fs->CreateURL('user', $user['user_id'])}">{$user['user_name']}</a></td>
          <td>{$user['real_name']}</td>
          <?php if ($user['account_enabled']): ?>
          <td>{$admin_text['yes']}</td>
          <?php else: ?>
          <td>{$admin_text['no']}</td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>

        <tr>
          <td colspan="4">
            <input class="adminbutton" type="submit" value="{$admin_text['moveuserstogroup']}" />
            <select class="adminlist" name="switch_to_group">
              <option value="0">{$admin_text['nogroup']}</option>
              {!tpl_options($group_list)}
            </select>
          </td>
        </tr>
      </table>
    </form>
    <?php endforeach; ?>

    <form action="{$baseurl}" method="post">
      <div>
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="addtogroup" />
        <input type="hidden" name="project_id" value="{$project_id}" />
        <input type="hidden" name="prev_page" value="{$this_page}" />
        <select class="adminlist" name="user_list[]" multiple="multiple" size="15">
          <?php foreach($fs->listUsersInGroup($project_id) as $user): ?>
          <option value="{$user['user_id']}">{$user['user_name']} ({$user['real_name']})</option>
          <?php endforeach; ?>
        </select>
        <br />
        <input class="adminbutton" type="submit" value="{$admin_text['addtogroup']}" />
        <select class="adminbutton" name="add_to_group">
          {!tpl_options($group_list)}
        </select>
      </div>
    </form>
  </fieldset>
</div>
