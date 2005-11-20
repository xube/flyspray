<div id="taskdetails">
  <form action="{$baseurl}" method="post">
    <div>
      <h2 class="severity{$task_details['task_severity']}">
        FS#{$task_details['task_id']} &mdash;
        <input class="severity{$task_details['task_severity']}" type="text"
         name="item_summary" size="80" maxlength="100"
         value="{$task_details['item_summary']}" />
      </h2>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="update" />
      <input type="hidden" name="task_id" value="{Get::val('id')}" />
      <input type="hidden" name="edit_start_time" value="{date('U')}" />

      <div id="fineprint">
        {$details_text['attachedtoproject']} &mdash;
        <select name="attached_to_project">
         {!tpl_options($project_list, $proj->id)}
        </select>
        <br />
        {$details_text['openedby']} {!tpl_userlink($task_details['opened_by'])}
        - {!$fs->formatDate($task_details['date_opened'], true)}
        <?php if ($task_details['last_edited_by']): ?>
        <br />
        {$details_text['editedby']}  {!tpl_userlink($task_details['last_edited_by'])}
        - {$fs->formatDate($task_details['last_edited_time'], true)}
        <?php endif; ?>
      </div>

      <div id="taskfields1">
        <table class="taskdetails">
         <tr class="tasktype">
          <td><label for="tasktype">{$details_text['tasktype']}</label></td>
          <td>
            <select id="tasktype" name="task_type">
             {!tpl_options($proj->listTaskTypes(), $task_details['task_type'])}
            </select>
          </td>
         </tr>
         <tr class="category">
          <td><label for="category">{$details_text['category']}</label></td>
          <td>
            <select id="category" name="product_category">
             {!tpl_options($proj->listCatsIn(), $task_details['product_category'])}
            </select>
          </td>
         </tr>
         <tr class="status">
          <td><label for="status">{$details_text['status']}</label></td>
          <td>
            <select id="status" name="item_status">
             {!tpl_options($status_list, $task_details['item_status'])}
            </select>
          </td>
         </tr>
         <tr class="assignedto">
          <td><label for="assignedto">{$details_text['assignedto']}</label></td>
          <td>
            <a href="#users" id="selectusers" class="button" onclick="showhidestuff('multiuserlist');">Select Users</a>
            <!--<input type="hidden" name="old_assigned" value="{$task_details['assigned_to']}" />
            <select id="assignedto" name="assigned_to">
             <option value="0">{$details_text['noone']}</option>
             <?php $fs->ListUsers($proj->id, $task_details['assigned_to']); ?>
            </select>-->
            <div id="multiuserlist">
             {!tpl_double_select('assigned_to', $userlist, $assigned_users, false)}
            </div>
          </td>
         </tr>
         <tr class="os">
          <td><label for="os">{$details_text['operatingsystem']}</label></td>
          <td>
            <select id="os" name="operating_system">
             {!tpl_options($proj->listOs(), $task_details['operating_system'])}
            </select>
          </td>
         </tr>
        </table>
      </div>

      <div id="taskfields2">
        <table class="taskdetails">
         <tr class="severity">
          <td><label for="severity">{$details_text['severity']}</label></td>
          <td>
            <select id="severity" name="task_severity">
             {!tpl_options($severity_list, $task_details['task_severity'])}
            </select>
          </td>
         </tr>
         <tr class="priority">
          <td><label for="priority">{$details_text['priority']}</label></td>
          <td>
            <select id="priority" name="task_priority">
             {!tpl_options($priority_list, $task_details['task_priority'])}
            </select>
          </td>
         </tr>
         <tr class="reportedver">
          <td><label for="reportedver">{$details_text['reportedversion']}</label></td>
          <td>
            <select id="reportedver" name="reportedver">
            {!tpl_options($proj->listVersions(false, 2, $task_details['product_version']), $task_details['product_version'])}
            </select>
          </td>
         </tr>
         <tr class="dueversion">
          <td><label for="dueversion">{$details_text['dueinversion']}</label></td>
          <td>
            <select id="dueversion" name="closedby_version">
             <option value="">{$details_text['undecided']}</option>
             {!tpl_options($proj->listVersions(), $task_details['closedby_version'])}
            </select>
          </td>
         </tr>
         <tr class="duedate">
          <td><label for="duedate">{$details_text['duedate']}</label></td>
          <td id="duedate">
            <?php
            $due_date  = $fs->formatDate($task_details['due_date'], false, '');
            $view_date = $fs->formatDate($task_details['due_date'], false, $details_text['undecided']);
            ?>
            <input id="duedatehidden" type="hidden" name="due_date" value="{$due_date}" />
            <span id="duedateview">{$view_date}</span> <small>|</small>
            <a href="#" onclick="document.getElementById('duedatehidden').value = '0';document.getElementById('duedateview').innerHTML = '{$details_text['undecided']}'">X</a>
            <script type="text/javascript">
             Calendar.setup({
               inputField  : "duedatehidden", // ID of the input field
               ifFormat    : "%d-%b-%Y",      // the date format
               displayArea : "duedateview",   // The display field
               daFormat    : "%d-%b-%Y",
               button      : "duedateview"    // ID of the button
             });
            </script>
          </td>
         </tr>
         <tr class="percent">
          <td><label for="percent">{$details_text['percentcomplete']}</label></td>
          <td>
            <select id="percent" name="percent_complete">
             <?php $arr = array(); for ($i = 0; $i<=100; $i+=10) $arr[$i] = $i.'%'; ?>
             {!tpl_options($arr, $task_details['percent_complete'])}
            </select>
          </td>
         </tr>
        </table>
      </div>

      <div id="taskdetailsfull">
        <label for="details">{$details_text['details']}</label>
        <textarea id="details" name="detailed_desc"
          cols="70" rows="10">{$task_details['detailed_desc']}</textarea>
        <table class="taskdetails">
          <tr><td>&nbsp;</td></tr>
          <tr>
            <td class="buttons">
              <input class="adminbutton" type="submit" accesskey="s" name="buSubmit"
                value="{$details_text['savedetails']}" />
              <input class="adminbutton" type="reset" name="buReset" value="{$details_text['reset']}" />
            </td>
          </tr>
        </table>
      </div>
    </div>
  </form>
</div>
