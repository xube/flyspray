<h3>{$proj->prefs['project_title']} :: {L('newtask')}</h3>

<div id="taskdetails">
    <h2 class="severity2" id="edit_summary">
      <label for="itemsummary">{L('summary')}</label>
      <input id="itemsummary" class="text severity2" type="text"
        name="item_summary" size="80" maxlength="100" />
    </h2>
  <form enctype="multipart/form-data" action="{$baseurl}" method="post" onsubmit="return checknewtask('{L('summaryanddetails')}')">
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
              {!tpl_options($proj->listCatsIn())}
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

        <tr>
          <td>
            <?php if (!$user->perms['modify_all_tasks']): ?>
            <input type="hidden" name="item_status"   value="1" />
            <input type="hidden" name="task_priority" value="2" />
            <?php endif; ?>
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
            <select onchange="getElementById('edit_summary').className = 'severity' + this.value;
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
        <tr>
          <td><label for="duedate">{L('duedate')}</label></td>
          <td id="duedate">
            <input id="duedatehidden" type="hidden" name="due_date" value="" />
            <?php if ($user->perms['modify_all_tasks']): ?>
            {!tpl_datepicker('due_', L('selectduedate'), L('selectduedate'))}
            <?php endif; ?>
          </td>
        </tr>
      </table>
    </div>

    <div id="taskdetailsfull">
      <label for="details">{L('details')}</label>
      <textarea id="details" name="detailed_desc" cols="70" rows="10"></textarea>
      <?php if ($user->perms['create_attachments']): ?>
        <div id="uploadfilebox">
          <span style="display: none"><?php // this span is shown/copied in javascript when adding files ?>
            <input class="file" type="file" size="55" name="userfile[]" />
            <a href="javascript://" onclick="removeUploadField(this);">{L('remove')}</a>
            <br />
          </span>
        </div>
        <button id="attachafile" type="button" onclick="addUploadFields()">{L('uploadafile')}</button>
        <button id="attachanotherfile" style="display:none" type="button" onclick="addUploadFields()">
          {L('attachanotherfile')}
        </button>
        
      <?php endif; ?>
      
    </div>

    <div>
    <input type="hidden" name="do" value="modify" />
    <input type="hidden" name="action" value="newtask" />
    <input type="hidden" name="project_id" value="{$proj->id}" />
    <button accesskey="s" type="submit">{L('addthistask')}</button>

    <?php if (!$user->isAnon()): ?>
    &nbsp;&nbsp;<input class="text" type="checkbox" id="notifyme" name="notifyme" value="1" checked="checked" />
    <label class="left" for="notifyme">{L('notifyme')}</label>
    <?php endif; ?>
    </div>
  </form>
</div>

