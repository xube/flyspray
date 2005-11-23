<h3>{$proj->prefs['project_title']} :: {$newtask_text['newtask']}</h3>

<div id="taskdetails">
  <form enctype="multipart/form-data" action="{$baseurl}" method="post" onsubmit="return checknewtask('{$modify_text['summaryanddetails']}')">
    <table>
      <tr>
        <td><label for="itemsummary">{$newtask_text['summary']}</label></td>
        <td>
          <input id="itemsummary" type="text" name="item_summary" size="50" maxlength="100" />
        </td>
      </tr>
    </table>

    <div id="taskfields1">

      <table>
        <tr>
          <td><label for="tasktype">{$newtask_text['tasktype']}</label></td>
          <td>
            <select name="task_type" id="tasktype">
              {!tpl_options($proj->listTaskTypes())}
            </select>
          </td>
        </tr>

        <tr>
          <td><label for="category">{$newtask_text['category']}</label></td>
          <td>
            <select class="adminlist" name="product_category" id="category">
              {!tpl_options($proj->listCatsIn())}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="status">{$newtask_text['status']}</label></td>
          <td>
            <select id="status" name="item_status" <?php if (!$user->perms['modify_all_tasks']) echo ' disabled="disabled"';?>>
              {!tpl_options($status_list, 2)}
            </select>
          </td>
        </tr>

        <tr>
          <td>
            <?php if (!$user->perms['modify_all_tasks']): ?>
            <input type="hidden" name="item_status"   value="1" />
            <input type="hidden" name="task_priority" value="2" />
            <?php endif; ?>
            <label for="assignedto">{$newtask_text['assignedto']}</label>
          </td>
          <td>
            <?php if ($user->perms['modify_all_tasks']): ?>
            <a href="#users" id="selectusers" class="button" onclick="showhidestuff('multiuserlist');">{$details_text['selectusers']}</a>
            <div id="multiuserlist">
                {!tpl_double_select('assigned_to', $userlist, $assigned_users, false)}
            </div>
            <?php endif; ?>
          </td>
        </tr>
        <tr>
          <td><label for="os">{$newtask_text['operatingsystem']}</label></td>
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
          <td><label for="severity">{$newtask_text['severity']}</label></td>
          <td>
            <select id="severity" class="adminlist" name="task_severity">
              {!tpl_options($severity_list, 2)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="priority">{$newtask_text['priority']}</label></td>
          <td>
            <select id="priority" name="task_priority" <?php if (!$user->perms['modify_all_tasks']) echo ' disabled="disabled"';?>>
              {!tpl_options($priority_list, 2)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="reportedver">{$newtask_text['reportedversion']}</label></td>
          <td>
            <select class="adminlist" name="product_version" id="reportedver">
              {!tpl_options($proj->listVersions(false, 2))}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="dueversion">{$newtask_text['dueinversion']}</label></td>
          <td>
            <select id="dueversion" name="closedby_version" <?php if (!$user->perms['modify_all_tasks']) echo ' disabled="disabled"';?>>
              <option value="">{$newtask_text['undecided']}</option>
              {!tpl_options($proj->listVersions(false, 3))}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="duedate">{$newtask_text['duedate']}</label></td>
          <td id="duedate">
            <input id="duedatehidden" type="hidden" name="due_date" value="" />
            <?php if ($user->perms['modify_all_tasks']): ?>
            <span id="duedateview">{$index_text['selectduedate']}</span> <small>|</small>
            <a href="#" onclick="document.getElementById('duedatehidden').value = '0';document.getElementById('duedateview').innerHTML = '{$index_text['selectduedate']?>'">X</a>
            <script type="text/javascript">
              Calendar.setup({
              inputField  : "duedatehidden",// ID of the input field
              ifFormat    : "%d-%b-%Y",     // the date format
              displayArea : "duedateview",  // The display field
              daFormat    : "%d-%b-%Y",
              button      : "duedateview"   // ID of the button
              });
            </script>
            <?php endif; ?>
          </td>
        </tr>
      </table>
    </div>

    <div id="taskdetailsfull">
      <label for="details">{$newtask_text['details']}</label>
      <textarea id="details" name="detailed_desc" cols="70" rows="10"></textarea>
    </div>

    <?php if ($user->perms['create_attachments']): ?>
    <div id="uploadfilebox">
      {$details_text['uploadafile']}
      <input type="file" size="55" name="userfile[]" /><br />
    </div><div>
    <input class="adminbutton" type="button" onclick="addUploadFields()" value="{$details_text['selectmorefiles']}" />
    <?php endif; ?>

    <input type="hidden" name="do" value="modify" />
    <input type="hidden" name="action" value="newtask" />
    <input type="hidden" name="project_id" value="{$proj->id}" />
    <input class="adminbutton" type="submit" name="buSubmit" value="{$newtask_text['addthistask']}" accesskey="s"/>

    <?php if (!$user->isAnon()): ?>
    &nbsp;&nbsp;<input class="admintext" type="checkbox" id="notifyme" name="notifyme" value="1" checked="checked" />
    <label class="default" for="notifyme">{$newtask_text['notifyme']}</label>
    <?php endif; ?>
    </div>
  </form>
</div>

