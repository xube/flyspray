<h1>{$newgroup_text['createnewgroup']} - {$forproject}</h1>
<p><em>{$newgroup_text['requiredfields']}</em> <strong>*</strong></p>

<form action="{$baseurl}" method="post" id="newgroup">
  <table class="admin">
    <tr>
      <td><label for="groupname">{$newgroup_text['groupname']}</label></td>
      <td><input id="groupname" type="text" name="group_name" size="20" maxlength="20" /> <strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="groupdesc">{$newgroup_text['description']}</label></td>
      <td><input id="groupdesc" type="text" name="group_desc" size="50" maxlength="100" /> <strong>*</strong></td>
    </tr>
    <tr>
      <td><label for="manageproject">{$newgroup_text['projectmanager']}</label></td>
      <td><input id="manageproject" type="checkbox" name="manage_project" value="1" /></td>
    </tr>
    <tr>
      <td><label for="viewnewtasks">{$newgroup_text['viewtasks']}</label></td>
      <td><input id="viewasks" type="checkbox" name="view_tasks" value="1" checked="checked" /></td>
    </tr>
    <tr>
      <td><label for="opennewtasks">{$newgroup_text['opennewtasks']}</label></td>
      <td><input id="opennewtasks" type="checkbox" name="open_new_tasks" value="1" checked="checked" /></td>
    </tr>
    <tr>
      <td><label for="modifyowntasks">{$newgroup_text['modifyowntasks']}</label></td>
      <td><input id="modifyowntasks" type="checkbox" name="modify_own_tasks" value="1" /></td>
    </tr>
    <tr>
      <td><label for="modifyalltasks">{$newgroup_text['modifyalltasks']}</label></td>
      <td><input id="modifyalltasks" type="checkbox" name="modify_all_tasks" value="1" /></td>
    </tr>
    <tr>
      <td><label for="viewcomments">{$newgroup_text['viewcomments']}</label></td>
      <td><input id="viewcomments" type="checkbox" name="view_comments" value="1" checked="checked" /></td>
    </tr>
    <tr>
      <td><label for="addcomments">{$newgroup_text['addcomments']}</label></td>
      <td><input id="addcomments" type="checkbox" name="add_comments" value="1" /></td>
    </tr>
    <tr>
      <td><label for="editcomments">{$newgroup_text['editcomments']}</label></td>
      <td><input id="editcomments" type="checkbox" name="edit_comments" value="1" /></td>
    </tr>
    <tr>
      <td><label for="deletecomments">{$newgroup_text['deletecomments']}</label></td>
      <td><input id="deletecomments" type="checkbox" name="delete_comments" value="1" /></td>
    </tr>
    <tr>
      <td><label for="createattachments">{$newgroup_text['createattachments']}</label></td>
      <td><input id="createattachments" type="checkbox" name="create_attachments" value="1" /></td>
    </tr>
    <tr>
      <td><label for="deleteattachments">{$newgroup_text['deleteattachments']}</label></td>
      <td><input id="deleteattachments" type="checkbox" name="delete_attachments" value="1" /></td>
    </tr>
    <tr>
      <td><label for="viewhistory">{$newgroup_text['viewhistory']}</label></td>
      <td><input id="viewhistory" type="checkbox" name="view_history" value="1" checked="checked" /></td>
    </tr>
    <tr>
      <td><label for="closeowntasks">{$newgroup_text['closeowntasks']}</label></td>
      <td><input id="closeowntasks" type="checkbox" name="close_own_tasks" value="1" /></td>
    </tr>
    <tr>
      <td><label for="closeothertasks">{$newgroup_text['closeothertasks']}</label></td>
      <td><input id="closeothertasks" type="checkbox" name="close_other_tasks" value="1" /></td>
    </tr>
    <tr>
      <td><label for="assigntoself">{$newgroup_text['assigntoself']}</label></td>
      <td><input id="assigntoself" type="checkbox" name="assign_to_self" value="1" /></td>
    </tr>
    <tr>
      <td><label for="assignotherstoself">{$newgroup_text['assignotherstoself']}</label></td>
      <td><input id="assignotherstoself" type="checkbox" name="assign_others_to_self" value="1" /></td>
    </tr>
    <tr>
      <td><label for="viewreports">{$newgroup_text['viewreports']}</label></td>
      <td><input id="viewreports" type="checkbox" name="view_reports" value="1" checked="checked" /></td>
    </tr>
    <tr>
      <td><label for="groupopen">{$newgroup_text['groupenabled']}</label></td>
      <td><input id="groupopen" type="checkbox" name="group_open" value="1" checked="checked" /></td>
    </tr>
    <tr>
      <td colspan="2" class="buttons">
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="newgroup" />
        <input type="hidden" name="project" value="{Get::val('project')}" />
        <input class="adminbutton" type="submit" value="{$newgroup_text['addthisgroup']}" />
      </td>
    </tr>
  </table>
</form>

