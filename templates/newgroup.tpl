<h1>{L('createnewgroup')} - {$forproject}</h1>
<p><em>{L('requiredfields')}</em> <strong>*</strong></p>

<form action="{$baseurl}" method="post" id="newgroup">
  <table class="admin">
    <tr>
      <td><label for="groupname">{L('groupname')}</label></td>
      <td><input id="groupname" class="text" type="text" name="group_name" size="20" maxlength="20" /> <strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="groupdesc">{L('description')}</label></td>
      <td><input id="groupdesc" class="text" type="text" name="group_desc" size="50" maxlength="100" /> <strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="manageproject">{L('projectmanager')}</label></td>
      <td><input id="manageproject" type="checkbox" name="manage_project" value="1" /></td>
    </tr>
    <tr>
      <td><label for="viewnewtasks">{L('viewtasks')}</label></td>
      <td><input id="viewasks" type="checkbox" name="view_tasks" value="1" checked="checked" /></td>
    </tr>
    <tr>
      <td><label for="opennewtasks">{L('opennewtasks')}</label></td>
      <td><input id="opennewtasks" type="checkbox" name="open_new_tasks" value="1" checked="checked" /></td>
    </tr>
    <tr>
      <td><label for="modifyowntasks">{L('modifyowntasks')}</label></td>
      <td><input id="modifyowntasks" type="checkbox" name="modify_own_tasks" value="1" /></td>
    </tr>
    <tr>
      <td><label for="modifyalltasks">{L('modifyalltasks')}</label></td>
      <td><input id="modifyalltasks" type="checkbox" name="modify_all_tasks" value="1" /></td>
    </tr>
    <tr>
      <td><label for="viewcomments">{L('viewcomments')}</label></td>
      <td><input id="viewcomments" type="checkbox" name="view_comments" value="1" checked="checked" /></td>
    </tr>
    <tr>
      <td><label for="addcomments">{L('addcomments')}</label></td>
      <td><input id="addcomments" type="checkbox" name="add_comments" value="1" /></td>
    </tr>
    <tr>
      <td><label for="editcomments">{L('editcomments')}</label></td>
      <td><input id="editcomments" type="checkbox" name="edit_comments" value="1" /></td>
    </tr>
    <tr>
      <td><label for="viewattachments">{L('viewattachments')}</label></td>
      <td><input id="viewattachments" type="checkbox" name="view_attachments" value="1" /></td>
    </tr>
    <tr>
      <td><label for="deletecomments">{L('deletecomments')}</label></td>
      <td><input id="deletecomments" type="checkbox" name="delete_comments" value="1" /></td>
    </tr>
    <tr>
      <td><label for="createattachments">{L('createattachments')}</label></td>
      <td><input id="createattachments" type="checkbox" name="create_attachments" value="1" /></td>
    </tr>
    <tr>
      <td><label for="deleteattachments">{L('deleteattachments')}</label></td>
      <td><input id="deleteattachments" type="checkbox" name="delete_attachments" value="1" /></td>
    </tr>
    <tr>
      <td><label for="viewhistory">{L('viewhistory')}</label></td>
      <td><input id="viewhistory" type="checkbox" name="view_history" value="1" checked="checked" /></td>
    </tr>
    <tr>
      <td><label for="closeowntasks">{L('closeowntasks')}</label></td>
      <td><input id="closeowntasks" type="checkbox" name="close_own_tasks" value="1" /></td>
    </tr>
    <tr>
      <td><label for="closeothertasks">{L('closeothertasks')}</label></td>
      <td><input id="closeothertasks" type="checkbox" name="close_other_tasks" value="1" /></td>
    </tr>
    <tr>
      <td><label for="assigntoself">{L('assigntoself')}</label></td>
      <td><input id="assigntoself" type="checkbox" name="assign_to_self" value="1" /></td>
    </tr>
    <tr>
      <td><label for="assignotherstoself">{L('assignotherstoself')}</label></td>
      <td><input id="assignotherstoself" type="checkbox" name="assign_others_to_self" value="1" /></td>
    </tr>
    <tr>
      <td><label for="viewreports">{L('viewreports')}</label></td>
      <td><input id="viewreports" type="checkbox" name="view_reports" value="1" checked="checked" /></td>
    </tr>
    <tr>
      <td><label for="groupopen">{L('groupenabled')}</label></td>
      <td><input id="groupopen" type="checkbox" name="group_open" value="1" checked="checked" /></td>
    </tr>
    <tr>
      <td colspan="2" class="buttons">
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="newgroup" />
        <input type="hidden" name="project" value="{Get::val('project')}" />
        <button type="submit">{L('addthisgroup')}</button>
      </td>
    </tr>
  </table>
</form>

