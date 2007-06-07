<fieldset><legend>{L('createnewgroup')}</legend>

<form action="{CreateUrl(array($do, 'proj' . $proj->id, 'newgroup'))}" method="post" id="newgroup">
  <table class="box">
    <tr>
      <td><label for="groupname">{L('groupname')}</label></td>
      <td><input id="groupname" class="required text" type="text" value="{Req::val('group_name')}" name="group_name" size="20" maxlength="20" /></td>
    </tr>
    <tr>
      <td><label for="groupdesc">{L('description')}</label></td>
      <td><input id="groupdesc" class="text" type="text" value="{Req::val('group_desc')}" name="group_desc" size="50" maxlength="100" /></td>
    </tr>
    <?php foreach ($fs->permgroups as $pgroup): ?>
      <tr><td class="groupsep" colspan="2">{L($pgroup[2])}</td></tr>
      <?php for ($i = $pgroup[0]; $i <= $pgroup[1]; $i++): ?>
      <tr>
        <td><label for="{$fs->perms[$i]}">{L(str_replace('_', '', $fs->perms[$i]))}</label></td>
      <td>{!tpl_checkbox($fs->perms[$i], Req::val($fs->perms[$i]), $fs->perms[$i])}</td>
      </tr>
      <?php endfor; ?>
    <?php endforeach; ?>
    <?php if (!$proj->id): ?>
    <tr>
      <td><label for="groupopen">{L('groupopen')}</label></td>
      <td>{!tpl_checkbox('group_open', Req::val('group_open', Req::val('action') != 'newgroup.newgroup'), 'groupopen')}</td>
    </tr>
    <?php endif; ?>
    <tr>
      <td colspan="2" class="buttons">
        <input type="hidden" name="action" value="newgroup" />
        <input type="hidden" name="do" value="{$do}" />
        <input type="hidden" name="project_id" value="{Req::val('project')}" />
        <button type="submit">{L('addthisgroup')}</button>
      </td>
    </tr>
  </table>
</form>
</fieldset>
