<div id="toolbox">
  <h3>{$pm_text['pmtoolbox']} :: {$project_prefs['project_title']} : {$admin_text['editgroup']}</h3>

  <fieldset class="admin">
    <legend>{$admin_text['editgroup']}</legend>
    <?php $group_details = $fs->getGroupDetails(Get::val('id')); ?>
    <form action="{$baseurl}?project={$group_details['belongs_to_project']}" method="post">
      <table class="admin">
        <tr>
          <td>
            <label for="groupname">{$admin_text['groupname']}</label>
          </td>
          <td><input id="groupname" type="text" name="group_name" size="20" maxlength="20" value="{$group_details['group_name']}" /></td>
        </tr>
        <tr>
          <td><label for="groupdesc">{$admin_text['description']}</label></td>
          <td><input id="groupdesc" type="text" name="group_desc" size="50" maxlength="100" value="{$group_details['group_desc']}" /></td>
        </tr>
        <tr>
          <td><label for="projectmanager">{$admin_text['projectmanager']}</label></td>
          <td>{!tpl_checkbox('manage_project', $group_details['manage_project'], 'projectmanager')}</td>
        </tr>
        <tr>
          <td><label for="viewtasks">{$admin_text['viewtasks']}</label></td>
          <td>{!tpl_checkbox('view_tasks', $group_details['view_tasks'], 'viewtasks')}</td>
        </tr>
        <tr>
          <td><label for="canopenjobs">{$admin_text['opennewtasks']}</label></td>
          <td>{!tpl_checkbox(open_new_tasks, $group_details['open_new_tasks'], canopenjobs)}</td>
        </tr>
        <tr>
          <td><label for="modifyowntasks">{$admin_text['modifyowntasks']}</label></td>
          <td>{!tpl_checkbox(modify_own_tasks, $group_details['modify_own_tasks'], modifyowntasks)}</td>
        </tr>
        <tr>
          <td><label for="modifyalltasks">{$admin_text['modifyalltasks']}</label></td>
          <td>{!tpl_checkbox(modify_all_tasks, $group_details['modify_all_tasks'], modifyalltasks)}</td>
        </tr>
        <tr>
          <td><label for="viewcomments">{$admin_text['viewcomments']}</label></td>
          <td>{!tpl_checkbox(view_comments, $group_details['view_comments'], viewcomments)}</td>
        </tr>
        <tr>
          <td><label for="canaddcomments">{$admin_text['addcomments']}</label></td>
          <td>{!tpl_checkbox(add_comments, $group_details['add_comments'], canaddcomments)}</td>
        </tr>
        <tr>
          <td><label for="editcomments">{$admin_text['editcomments']}</label></td>
          <td>{!tpl_checkbox(edit_comments, $group_details['edit_comments'], editcomments)}</td>
        </tr>
        <tr>
          <td><label for="deletecomments">{$admin_text['deletecomments']}</label></td>
          <td>{!tpl_checkbox(delete_comments, $group_details['delete_comments'], deletecomments)}</td>
        </tr>
        <tr>
          <td><label for="viewattachments">{$admin_text['viewattachments']}</label></td>
          <td>{!tpl_checkbox(view_attachments, $group_details['view_attachments'], viewattachments)}</td>
        </tr>
        <tr>
          <td><label for="createattachments">{$admin_text['createattachments']}</label></td>
          <td>{!tpl_checkbox(create_attachments, $group_details['create_attachments'], createattachments)}</td>
        </tr>
        <tr>
          <td><label for="deleteattachments">{$admin_text['deleteattachments']}</label></td>
          <td>{!tpl_checkbox(delete_attachments, $group_details['delete_attachments'], deleteattachments)}</td>
        </tr>
        <tr>
          <td><label for="viewhistory">{$admin_text['viewhistory']}</label></td>
          <td>{!tpl_checkbox(view_history, $group_details['view_history'], viewhistory)}</td>
        </tr>
        <tr>
          <td><label for="closeowntasks">{$admin_text['closeowntasks']}</label></td>
          <td>{!tpl_checkbox(close_own_tasks, $group_details['close_own_tasks'], closeowntasks)}</td>
        </tr>
        <tr>
          <td><label for="closeothertasks">{$admin_text['closeothertasks']}</label></td>
          <td>{!tpl_checkbox(close_other_tasks, $group_details['close_other_tasks'], closeothertasks)}</td>
        </tr>
        <tr>
          <td><label for="assigntoself">{$admin_text['assigntoself']}</label></td>
          <td>{!tpl_checkbox(assign_to_self, $group_details['assign_to_self'], assigntoself)}</td>
        </tr>
        <tr>
          <td><label for="assignotherstoself">{$admin_text['assignotherstoself']}</label></td>
          <td>{!tpl_checkbox(assign_others_to_self, $group_details['assign_others_to_self'], assignotherstoself)}</td>
        </tr>
        <tr>
          <td><label for="viewreports">{$admin_text['viewreports']}</label></td>
          <td>{!tpl_checkbox(view_reports, $group_details['view_reports'], viewreports)}</td>
        </tr>
        <tr>
          <td colspan="2" class="buttons">
            <input type="hidden" name="do" value="modify" />
            <input type="hidden" name="action" value="editgroup" />
            <input type="hidden" name="group_id" value="{$group_details['group_id']}" />
            <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
            <input class="adminbutton" type="submit" value="{$admin_text['updatedetails']}" />
          </td>
        </tr>
      </table>
    </form>
  </fieldset>
</div>
