<h3>{$proj->prefs['project_title']} :: {L('newtask')}</h3>

<div id="taskdetails">
    <form enctype="multipart/form-data" action="{$baseurl}" method="post">
    <h2 class="severity2 summary" id="edit_summary">
      <label for="itemsummary">{L('summary')}</label>
      <input id="itemsummary" class="text severity2" type="text"
        name="item_summary" size="80" maxlength="100" />
    </h2>
    <div id="taskfields1">

      <table>
        <tr>
          <td><label for="tasktype">{L('tasktype')}</label></td>
          <td>
            <select name="task_type" id="tasktype">
              {!tpl_options($proj->listTaskTypes())}
            </select>
          </td>
        </tr>

        <tr>
          <td><label for="category">{L('category')}</label></td>
          <td>
            <select class="adminlist" name="product_category" id="category">
              {!tpl_options($proj->listCategories())}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="status">{L('status')}</label></td>
          <td>
            <select id="status" name="item_status" <?php if (!$user->perms['modify_all_tasks']) echo ' disabled="disabled"';?>>
              {!tpl_options($proj->listTaskStatuses(), 2)}
            </select>
          </td>
        </tr>
        <?php if ($user->perms['modify_all_tasks']): ?>
        <tr>
          <td>
            <label>{L('assignedto')}</label>
          </td>
          <td>
            <?php if ($user->perms['modify_all_tasks']): ?>
            <a href="#users" id="selectusers" class="button" onclick="showhidestuff('multiuserlist');">{L('selectusers')}</a>
            <div id="multiuserlist">
                {!tpl_double_select('assigned_to', $userlist, $assigned_users, false, false)}
                <button type="button" onclick="hidestuff('multiuserlist')">{L('OK')}</button>
            </div>
            <?php endif; ?>
          </td>
        </tr>
        <?php endif; ?>
        <tr>
          <td><label for="os">{L('operatingsystem')}</label></td>
          <td>
            <select id="os" name="operating_system">
              {!tpl_options($proj->listOs())}
            </select>
          </td>
        </tr>
      </table>
    </div>

    <div id="taskfields2">
      <table>
        <tr>
          <td><label for="severity">{L('severity')}</label></td>
          <td>
            <select onchange="getElementById('edit_summary').className = 'summary severity' + this.value;
                              getElementById('itemsummary').className = 'text severity' + this.value;"
                              id="severity" class="adminlist" name="task_severity">
              {!tpl_options($severity_list, 2)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="priority">{L('priority')}</label></td>
          <td>
            <select id="priority" name="task_priority" <?php if (!$user->perms['modify_all_tasks']) echo ' disabled="disabled"';?>>
              {!tpl_options($priority_list, 2)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="reportedver">{L('reportedversion')}</label></td>
          <td>
            <select class="adminlist" name="product_version" id="reportedver">
              {!tpl_options($proj->listVersions(false, 2))}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="dueversion">{L('dueinversion')}</label></td>
          <td>
            <select id="dueversion" name="closedby_version" <?php if (!$user->perms['modify_all_tasks']) echo ' disabled="disabled"';?>>
              <option value="0">{L('undecided')}</option>
              {!tpl_options($proj->listVersions(false, 3))}
            </select>
          </td>
        </tr>
        <?php if ($user->perms['modify_all_tasks']): ?>
        <tr>
          <td><label for="duedate">{L('duedate')}</label></td>
          <td id="duedate">
            <input id="duedatehidden" type="hidden" name="due_date" value="" />
            {!tpl_datepicker('due_', L('selectduedate'), L('selectduedate'))}
          </td>
        </tr>
        <?php endif; ?>
      </table>
    </div>

    <div id="taskdetailsfull">
      <label for="details">{L('details')}</label>
      <?php if (defined('FLYSPRAY_HAS_PREVIEW')): ?>
      <div class="hide preview" id="preview"></div>
      <?php endif; ?>
      {!TextFormatter::textarea('detailed_desc', 10, 70, array('id' => 'details'))}
      <?php if ($user->perms['create_attachments']): ?>
      <div id="uploadfilebox">
        <span style="display: none"><?php // this span is shown/copied in javascript when adding files ?>
          <input tabindex="5" class="file" type="file" size="55" name="userfile[]" />
            <a href="javascript://" tabindex="6" onclick="removeUploadField(this, 'uploadfilebox');">{L('remove')}</a><br />
        </span>    
      </div>
      <button id="uploadfilebox_attachafile" tabindex="7" type="button" onclick="addUploadFields('uploadfilebox')">
        {L('uploadafile')} ({L('max')} {$fs->max_file_size} {L('MiB')})
      </button>
      <button id="uploadfilebox_attachanotherfile" tabindex="7" style="display: none" type="button" onclick="addUploadFields('uploadfilebox')">
         {L('attachanotherfile')} ({L('max')} {$fs->max_file_size} {L('MiB')})
      </button>
      <?php endif; ?>
    </div>

    <div>
    <?php if ($user->isAnon()): ?>
    <label for="anon_email">{L('youremail')}</label><input type="text" class="text" id="anon_email" name="anon_email" size="30" /><br />
    <?php endif; ?>
    <?php if (!$user->perms['modify_all_tasks']): ?>
    <input type="hidden" name="item_status"   value="1" />
    <input type="hidden" name="task_priority" value="2" />
    <?php endif; ?>
    <input type="hidden" name="do" value="modify" />
    <input type="hidden" name="action" value="newtask" />
    <input type="hidden" name="project_id" value="{$proj->id}" />
    <button accesskey="s" type="submit">{L('addthistask')}</button>
    <?php if (defined('FLYSPRAY_HAS_PREVIEW')): ?>
    <button tabindex="9" type="button" onclick="showPreview('details', '{$baseurl}', 'preview')">{L('preview')}</button>
    <?php endif; ?>

    <?php if (!$user->isAnon()): ?>
    &nbsp;&nbsp;<input class="text" type="checkbox" id="notifyme" name="notifyme" value="1" checked="checked" />
    <label class="left" for="notifyme">{L('notifyme')}</label>
    <?php endif; ?>
    </div>
  </form>
</div>

