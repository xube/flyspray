<fieldset class="box"> <legend>{L('editgroup')}</legend>
    <form action="{$this->relativeUrl($baseurl)}index.php" method="get">
        <div>
            <label for="selectgroup">{L('editgroup')}</label>
            <select name="group_id" id="selectgroup">{!tpl_options(Flyspray::ListGroups($proj->id), Req::num('group_id'))}</select>
            <button type="submit">{L('edit')}</button>
            <input type="hidden" name="do" value="{Req::val('do')}" />
            <input type="hidden" name="area" value="editgroup" />
        </div>
    </form>
    <hr />
  <form action="{$this->url(array($do, 'proj' . $proj->id, 'editgroup'), array('group_id' => Req::num('group_id')))}" method="post">
    <table class="box">
      <tr>
        <td>
          <label for="groupname">{L('groupname')}</label>
        </td>
        <td><input id="groupname" class="text" type="text" name="group_name" size="20" maxlength="20" value="{Req::val('group_name', $group['group_name'])}" /></td>
      </tr>
      <tr>
        <td><label for="groupdesc">{L('description')}</label></td>
        <td><input id="groupdesc" class="text" type="text" name="group_desc" size="50" maxlength="100" value="{Req::val('group_desc', $group['group_desc'])}" /></td>
      </tr>
      <?php if ($group['group_id'] == 1): ?>
      <tr>
        <td colspan="2">{L('notshownforadmin')}</td>
      </tr>
      <?php else: ?>
      <?php foreach ($fs->permgroups as $pgroup): ?>
      <tr><td class="groupsep" colspan="2">{L($pgroup[2])}</td></tr>
      <?php for ($i = $pgroup[0]; $i <= $pgroup[1]; $i++): ?>
      <tr>
        <td><label for="{$fs->perms[$i]}">{L(str_replace('_', '', $fs->perms[$i]))}</label></td>
        <td>{!tpl_checkbox($fs->perms[$i], Req::val($fs->perms[$i], !Req::val('action') && $group[$fs->perms[$i]]), $fs->perms[$i])}</td>
      </tr>
      <?php endfor; ?>
      <?php endforeach; ?>
      <?php if (!$proj->id): ?>
      <tr>
        <td><label for="groupopen">{L('groupopen')}</label></td>
        <td>{!tpl_checkbox('group_open', Req::val('group_open', !Req::val('action') && $group['group_open']), 'groupopen')}</td>
      </tr>
      <?php endif; ?>
      <?php endif; ?>
      <?php if ($group['group_id'] != '1'): ?>
      <tr>
        <td><label><input type="checkbox" name="delete_group" /> {L('deletegroup')}</label></td>
        <td><select name="move_to">
              {!tpl_options( array_merge( ($proj->id) ? array(L('nogroup')) : array(), Flyspray::listGroups($proj->id)), null, false, null, $group['group_id'])}
            </select>
        </td>
      </tr>
      <?php endif; ?>
      <tr>
        <td><label for="add_user">{L('addusergroup')}</label></td>
        <td>
            {!tpl_userselect('uid', '', 'add_user')}
        </td>
      </tr>
      <tr>
        <td colspan="2" class="buttons">
          <input type="hidden" name="project_id" value="{$proj->id}" />
          <input type="hidden" name="do" value="{$do}" />
          <input type="hidden" name="action" value="editgroup" />
          <input type="hidden" name="area" value="editgroup" />
          <input type="hidden" name="group_id" value="{Req::num('group_id')}" />
          <button type="submit">{L('updatedetails')}</button>
        </td>
      </tr>
    </table>
  </form>

  <hr />

  <h3>
    {L('quicklinks')}:
    <a href="{$this->url(array($do, 'proj' . $proj->id, 'users'), array('group_id[]' => $group['group_id']))}">{L('groupmembers')} ({$group['num_users']})</a>
    |
    <a href="{$this->url(array($do, 'newgroup'), $newparams)}">{L('createnewfromthis')}</a>
  </h3>

</fieldset>
