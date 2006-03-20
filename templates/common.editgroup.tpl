<fieldset class="admin"> <legend>{L('editgroup')}</legend>
  <?php $group_details = $fs->getGroupDetails(Get::val('id')); ?>
  <form action="{$baseurl}?project={$group_details['belongs_to_project']}" method="post">
    <table class="admin">
      <tr>
        <td>
          <label for="groupname">{L('groupname')}</label>
        </td>
        <td><input id="groupname" class="text" type="text" name="group_name" size="20" maxlength="20" value="{$group_details['group_name']}" /></td>
      </tr>
      <tr>
        <td><label for="groupdesc">{L('description')}</label></td>
        <td><input id="groupdesc" class="text" type="text" name="group_desc" size="50" maxlength="100" value="{$group_details['group_desc']}" /></td>
      </tr>
      <?php if ($group_details['group_id'] == 1): ?>
      <tr>
        <td colspan="2">{L('notshownforadmin')}</td>
      </tr>
      <?php else: ?>
      <tr>
        <td><label for="projectmanager">{L('projectmanager')}</label></td>
        <td>{!tpl_checkbox('manage_project', $group_details['manage_project'], 'projectmanager')}</td>
      </tr>
      <tr>
        <td><label for="viewtasks">{L('viewtasks')}</label></td>
        <td>{!tpl_checkbox('view_tasks', $group_details['view_tasks'], 'viewtasks')}</td>
      </tr>
      <tr>
        <td><label for="canopenjobs">{L('opennewtasks')}</label></td>
        <td>{!tpl_checkbox('open_new_tasks', $group_details['open_new_tasks'], 'canopenjobs')}</td>
      </tr>
      <tr>
        <td><label for="modifyowntasks">{L('modifyowntasks')}</label></td>
        <td>{!tpl_checkbox('modify_own_tasks', $group_details['modify_own_tasks'], 'modifyowntasks')}</td>
      </tr>
      <tr>
        <td><label for="modifyalltasks">{L('modifyalltasks')}</label></td>
        <td>{!tpl_checkbox('modify_all_tasks', $group_details['modify_all_tasks'], 'modifyalltasks')}</td>
      </tr>
      <tr>
        <td><label for="viewcomments">{L('viewcomments')}</label></td>
        <td>{!tpl_checkbox('view_comments', $group_details['view_comments'], 'viewcomments')}</td>
      </tr>
      <tr>
        <td><label for="canaddcomments">{L('addcomments')}</label></td>
        <td>{!tpl_checkbox('add_comments', $group_details['add_comments'], 'canaddcomments')}</td>
      </tr>
      <tr>
        <td><label for="editowncomments">{L('editowncomments')}</label></td>
        <td>{!tpl_checkbox('edit_own_comments', $group_details['edit_own_comments'], 'editowncomments')}</td>
      </tr>
      <tr>
        <td><label for="editcomments">{L('editcomments')}</label></td>
        <td>{!tpl_checkbox('edit_comments', $group_details['edit_comments'], 'editcomments')}</td>
      </tr>
      <tr>
        <td><label for="deletecomments">{L('deletecomments')}</label></td>
        <td>{!tpl_checkbox('delete_comments', $group_details['delete_comments'], 'deletecomments')}</td>
      </tr>
      <tr>
        <td><label for="viewattachments">{L('viewattachments')}</label></td>
        <td>{!tpl_checkbox('view_attachments', $group_details['view_attachments'], 'viewattachments')}</td>
      </tr>
      <tr>
        <td><label for="createattachments">{L('createattachments')}</label></td>
        <td>{!tpl_checkbox('create_attachments', $group_details['create_attachments'], 'createattachments')}</td>
      </tr>
      <tr>
        <td><label for="deleteattachments">{L('deleteattachments')}</label></td>
        <td>{!tpl_checkbox('delete_attachments', $group_details['delete_attachments'], 'deleteattachments')}</td>
      </tr>
      <tr>
        <td><label for="viewhistory">{L('viewhistory')}</label></td>
        <td>{!tpl_checkbox('view_history', $group_details['view_history'], 'viewhistory')}</td>
      </tr>
      <tr>
        <td><label for="closeowntasks">{L('closeowntasks')}</label></td>
        <td>{!tpl_checkbox('close_own_tasks', $group_details['close_own_tasks'], 'closeowntasks')}</td>
      </tr>
      <tr>
        <td><label for="closeothertasks">{L('closeothertasks')}</label></td>
        <td>{!tpl_checkbox('close_other_tasks', $group_details['close_other_tasks'], 'closeothertasks')}</td>
      </tr>
      <tr>
        <td><label for="assigntoself">{L('assigntoself')}</label></td>
        <td>{!tpl_checkbox('assign_to_self', $group_details['assign_to_self'], 'assigntoself')}</td>
      </tr>
       <tr>
        <td><label for="assignotherstoself">{L('assignotherstoself')}</label></td>
        <td>{!tpl_checkbox('assign_others_to_self', $group_details['assign_others_to_self'], 'assignotherstoself')}</td>
      </tr>
      <tr>
        <td><label for="addtoassignees">{L('addtoassignees')}</label></td>
        <td>{!tpl_checkbox('add_to_assignees', $group_details['add_to_assignees'], 'addtoassignees')}</td>
      </tr>
      <tr>
        <td><label for="viewreports">{L('viewreports')}</label></td>
        <td>{!tpl_checkbox('view_reports', $group_details['view_reports'], 'viewreports')}</td>
      </tr>
      <tr>
        <td><label for="canvote">{L('canvote')}</label></td>
        <td>{!tpl_checkbox('add_votes', $group_details['add_votes'], 'canvote')}</td>
      </tr>

      <?php if (!$proj->id): ?>
      <tr>
        <td><label for="groupopen">{L('groupenabled')}</label></td>
        <td>{!tpl_checkbox('group_open', $group_details['group_open'], 'group_open')}</td>
      </tr>
      <?php endif; ?>
      <?php endif; ?>
      <tr>
        <td colspan="2" class="buttons">
          <input type="hidden" name="do" value="modify" />
          <input type="hidden" name="action" value="editgroup" />
          <input type="hidden" name="group_id" value="{$group_details['group_id']}" />
          <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
          <button type="submit">{L('updatedetails')}</button>
        </td>
      </tr>
    </table>
  </form>
</fieldset>
