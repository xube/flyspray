<h3>{$proj->prefs['project_title']} :: {$newtask_text['newtask']}</h3>

<div id="taskdetails">
  <form enctype="multipart/form-data" action="{$baseurl}" method="post">
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
          <td><label for="productcategory">{$newtask_text['category']}</label></td>
          <td>
            <select class="adminlist" name="product_category" id="productcategory">
              {!tpl_options($proj->listCatsIn())}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="itemstatus">{$newtask_text['status']}</label></td>
          <td>
            <select id="itemstatus" name="item_status" <?php if (!$user->perms['modify_all_tasks']) echo ' disabled="disabled"';?>>
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
            <select id="assignedto" name="assigned_to" <?php if (!$user->perms['modify_all_tasks']) echo ' disabled="disabled"';?>>
              <option value="0">{$newtask_text['noone']}</option>
              <?php $fs->ListUsers($proj->id); ?>
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="operatingsystem">{$newtask_text['operatingsystem']}</label></td>
          <td>
            <select id="operatingsystem" name="operating_system">
              {!tpl_options($proj->listOs())}
            </select>
          </td>
        </tr>
      </table>
    </div>

    <div id="taskfields2">
      <table>
        <tr>
          <td><label for="taskseverity">{$newtask_text['severity']}</label></td>
          <td>
            <select id="taskseverity" class="adminlist" name="task_severity">
              {!tpl_options($severity_list, 2)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="task_priority">{$newtask_text['priority']}</label></td>
          <td>
            <select id="task_priority" name="task_priority" <?php if (!$user->perms['modify_all_tasks']) echo ' disabled="disabled"';?>>
              {!tpl_options($priority_list, 2)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="productversion">{$newtask_text['reportedversion']}</label></td>
          <td>
            <select class="adminlist" name="product_version" id="productversion">
              {!tpl_options($proj->listVersions(false, 2))}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="closedbyversion">{$newtask_text['dueinversion']}</label></td>
          <td>
            <select id="closedbyversion" name="closedby_version" <?php if (!$user->perms['modify_all_tasks']) echo ' disabled="disabled"';?>>
              <option value=\"\">{$newtask_text['undecided']}</option>
              {!tpl_options($proj->listVersions(false, 3))}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="duedate">{$newtask_text['duedate']}</label></td>
          <td id="duedate">
            <input id="duedatehidden" type="hidden" name="due_date" value="" />
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
    </div>
    <input class="adminbutton" type="button" onclick="addUploadFields()" value="{$details_text['selectmorefiles']}" />
    <?php endif; ?>

    <input type="hidden" name="do" value="modify" />
    <input type="hidden" name="action" value="newtask" />
    <input type="hidden" name="project_id" value="{$proj->id}" />
    <input class="adminbutton" type="submit" name="buSubmit" value="{$newtask_text['addthistask']}" accesskey="s"/>

    <?php if (!$user->isAnon()): ?>
    &nbsp;&nbsp;<input class="admintext" type="checkbox" name="notifyme" value="1" checked="checked" />
    {$newtask_text['notifyme']}
    <?php endif; ?>
  </form>
</div>

