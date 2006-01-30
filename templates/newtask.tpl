<h3>{$proj->prefs['project_title']} :: {$language['newtask']}</h3>

<div id="taskdetails">
  <form enctype="multipart/form-data" action="{$baseurl}" method="post" onsubmit="return checknewtask('{$language['summaryanddetails']}')">
    <table>
      <tr>
        <td><label for="itemsummary">{$language['summary']}</label></td>
        <td>
          <input id="itemsummary" class="text" type="text" name="item_summary" size="50" maxlength="100" />
        </td>
      </tr>
    </table>

    <div id="taskfields1">

      <table>
        <tr>
          <td><label for="tasktype">{$language['tasktype']}</label></td>
          <td>
            <select name="task_type" id="tasktype">
              {!tpl_options($proj->listTaskTypes())}
            </select>
          </td>
        </tr>

        <tr>
          <td><label for="category">{$language['category']}</label></td>
          <td>
            <select class="adminlist" name="product_category" id="category">
              {!tpl_options($proj->listCatsIn())}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="status">{$language['status']}</label></td>
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
            <label>{$language['assignedto']}</label>
          </td>
          <td>
            <?php if ($user->perms['modify_all_tasks']): ?>
            <a href="#users" id="selectusers" class="button" onclick="showhidestuff('multiuserlist');">{$language['selectusers']}</a>
            <div id="multiuserlist">
                {!tpl_double_select('assigned_to', $userlist, $assigned_users, false, false)}
                <button type="button" onclick="hidestuff('multiuserlist')">{$language['OK']}</button>
            </div>
            <?php endif; ?>
          </td>
        </tr>
        <tr>
          <td><label for="os">{$language['operatingsystem']}</label></td>
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
          <td><label for="severity">{$language['severity']}</label></td>
          <td>
            <select id="severity" class="adminlist" name="task_severity">
              {!tpl_options($severity_list, 2)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="priority">{$language['priority']}</label></td>
          <td>
            <select id="priority" name="task_priority" <?php if (!$user->perms['modify_all_tasks']) echo ' disabled="disabled"';?>>
              {!tpl_options($priority_list, 2)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="reportedver">{$language['reportedversion']}</label></td>
          <td>
            <select class="adminlist" name="product_version" id="reportedver">
              {!tpl_options($proj->listVersions(false, 2))}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="dueversion">{$language['dueinversion']}</label></td>
          <td>
            <select id="dueversion" name="closedby_version" <?php if (!$user->perms['modify_all_tasks']) echo ' disabled="disabled"';?>>
              <option value="0">{$language['undecided']}</option>
              {!tpl_options($proj->listVersions(false, 3))}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="duedate">{$language['duedate']}</label></td>
          <td id="duedate">
            <input id="duedatehidden" type="hidden" name="due_date" value="" />
            <?php if ($user->perms['modify_all_tasks']): ?>
            {!tpl_datepicker('due_', $language['selectduedate'], $language['selectduedate'])}
            <?php endif; ?>
          </td>
        </tr>
      </table>
    </div>

    <div id="taskdetailsfull">
      <label for="details">{$language['details']}</label>
      <textarea id="details" name="detailed_desc" cols="70" rows="10"></textarea>
      <?php if ($user->perms['create_attachments']): ?>
        <div id="uploadfilebox">
          <span style="display: none"><?php // this span is shown/copied in javascript when adding files ?>
            <input class="file" type="file" size="55" name="userfile[]" />
            <a href="javascript://" onclick="removeUploadField(this);">{$language['remove']}</a>
            <br />
          </span>
        </div>
        <button id="attachafile" type="button" onclick="addUploadFields()">{$language['uploadafile']}</button>
        <button id="attachanotherfile" style="display:none" type="button" onclick="addUploadFields()">
          {$language['attachanotherfile']}
        </button>
        
      <?php endif; ?>
      
    </div>

    <div>
    <input type="hidden" name="do" value="modify" />
    <input type="hidden" name="action" value="newtask" />
    <input type="hidden" name="project_id" value="{$proj->id}" />
    <button accesskey="s" type="submit">{$language['addthistask']}</button>

    <?php if (!$user->isAnon()): ?>
    &nbsp;&nbsp;<input class="text" type="checkbox" id="notifyme" name="notifyme" value="1" checked="checked" />
    <label class="left" for="notifyme">{$language['notifyme']}</label>
    <?php endif; ?>
    </div>
  </form>
</div>

