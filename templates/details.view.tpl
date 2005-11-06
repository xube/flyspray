<span id="navigation">
  <?php if ($previous_id): ?>
  {!tpl_tasklink($details_text['previoustask'], $previous_id, array('id'=>'prev'))}
  <?php endif; ?>
  <?php if ($previous_id && $next_id): ?> | <?php endif; ?>
  <?php if ($next_id): ?>
  {!tpl_tasklink($details_text['nexttask'], $next_id, array('id'=>'next'))}
  <?php endif; ?>
</span>
<div id="taskdetails" ondblclick='openTask("{$fs->CreateURL('edittask', $task_details['task_id'])}")'>

  <h2 class="severity{$task_details['task_severity']}">
    FS#{$task_details['task_id']} &mdash; {!tpl_formatText($task_details['item_summary'])}
  </h2>

  <div id="fineprint">
    {$details_text['attachedtoproject']} &mdash;
    <a href="{$baseurl}?project={$task_details['attached_to_project']}">
      {$task_details['project_title']}</a>
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
    <table>
      <tr>
        <td><label for="tasktype">{$details_text['tasktype']}</label></td>
        <td id="tasktype">{$task_details['tasktype_name']}</td>
      </tr>
      <tr>
        <td><label for="category">{$details_text['category']}</label></td>
        <td id="category">
          <?php if ($task_details['parent_category_name']): ?>
          {$task_details['parent_category_name']} &mdash;
          <?php endif; ?>
          {$task_details['category_name']}
        </td>
      </tr>
      <tr>
        <td><label for="status">{$details_text['status']}</label></td>
        <td id="status">
          <?php if ($task_details['is_closed']): ?>
          {$details_text['closed']}
          <?php else: ?>
          {$task_details['status_name']}
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <td><label for="assignedto">{$details_text['assignedto']}</label></td>
        <td id="assignedto">
          <?php if (!$task_details['assigned_to']): ?>
          {$details_text['noone']}
          <?php else: ?>
          {$task_details['assigned_to_name']}
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <td><label for="os">{$details_text['operatingsystem']}</label></td>
        <td id="os">{$task_details['os_name']}</td>
      </tr>
    </table>
  </div>

  <div id="taskfields2">
    <table>
      <tr>
        <td><label for="severity">{$details_text['severity']}</label></td>
        <td id="severity">{$task_details['severity_name']}</td>
      </tr>
      <tr>
        <td><label for="priority">{$details_text['priority']}</label></td>
        <td id="priority">{$task_details['priority_name']}</td>
      </tr>
      <tr>
        <td><label for="reportedver">{$details_text['reportedversion']}</label></td>
        <td id="reportedver">{$task_details['reported_version_name']}</td>
      </tr>
      <tr>
        <td><label for="dueversion">{$details_text['dueinversion']}</label></td>
        <td id="dueversion">
          <?php if ($task_details['due_in_version_name']): ?>
          {$task_details['due_in_version_name']}
          <?php else: ?>
          {$details_text['undecided']}
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <td><label for="duedate">{$details_text['duedate']}</label></td>
        <td id="duedate">
          <?php if ($task_details['due_date']): ?>
          {$fs->formatDate($task_details['due_date'], false)}
          <?php else: ?>
          {$details_text['undecided']}
          <?php endif; ?>
        </td>
      </tr>
      <tr>
        <td><label for="percent">{$details_text['percentcomplete']}</label></td>
        <td id="percent">
          <img src="{$baseurl}themes/{$proj->prefs['theme_style']}/percent-{$task_details['percent_complete']}.png"
            title="{$task_details['percent_complete']}% {$details_text['complete']}"
            alt="{$task_details['percent_complete']}%" />
        </td>
      </tr>
    </table>
  </div>

  <div id="taskdetailsfull">
    <label>{$details_text['details']}</label>
    {!tpl_formatText($task_details['detailed_desc'])}
  </div>

  <div id="deps">
    <div id="taskdeps">
      <b>{$details_text['taskdependson']}</b>
      <br />
      <?php foreach ($deps as $dependency): ?>
      <?php if ($dependency['is_closed']): ?>
      <a class="closedtasklink" href="{$fs->CreateURL('details', $dependency['dep_task_id'])}">
        FS#{$dependency['task_id']} - {$dependency['item_summary']}</a>
      <?php else: ?>
      <a href="{$fs->CreateURL('details', $dependency['dep_task_id'])}">
        FS#{$dependency['task_id']} - {$dependency['item_summary']}</a>
      <?php endif; ?>
      <?php if ($user->can_edit_task($task_details)): ?>
      <span class="DoNotPrint"> &mdash;
        <a class="removedeplink"
          href="{$baseurl}?do=modify&amp;action=removedep&amp;depend_id={$dependency['depend_id']}">
          {$details_text['remove']}</a>
      </span>
      <?php endif; ?>
      <br />
      <?php endforeach; ?>

      <br class="DoNotPrint" />

      <?php if (count($deps) || count($blocks)): ?>
      <a class="DoNotPrint" href="{$fs->CreateURL('depends', $id)}">{$details_text['depgraph']}</a>
      <br />
      <br />
      <?php endif; ?>

      <?php if ($user->can_edit_task($task_details)): ?>
      <form action="{$baseurl}" method="post">
        <div>
          <input type="hidden" name="do" value="modify" />
          <input type="hidden" name="action" value="newdep" />
          <input type="hidden" name="task_id" value="{Get::val('id')}" />
          <input class="admintext" type="text" name="dep_task_id" size="5" maxlength="10" />
          <input class="adminbutton" type="submit" name="submit" value="{$details_text['addnew']}" />
        </div>
      </form>
      <?php endif; ?>
    </div>

    <div id="taskblocks">
      <b>{$details_text['taskblocks']}</b>
      <br />
      <?php foreach ($blocks as $block): ?>
      <?php if ($block['is_closed']): ?>
      <a class="closedtasklink" href="{$fs->CreateURL('details', $block['task_id'])}">
        FS#{$block['task_id']} - {$block['item_summary']}</a>
      <?php else: ?>
      <a href="{$fs->CreateURL('details', $block['task_id'])}">
        FS#{$block['task_id']} - {$block['item_summary']}</a>
      <?php endif; ?>
      <br />
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($task_details['is_closed']): ?>
  {$details_text['closedby']}&nbsp;&nbsp;{!tpl_userlink($task_details['closed_by'])}<br />
  {$fs->formatDate($task_details['date_closed'], true)}<br />
  {$details_text['reasonforclosing']}&nbsp;&nbsp;{$task_details['resolution_name']}<br />
  <?php if ($task_details['closure_comment']): ?>
  {$details_text['closurecomment']}&nbsp;&nbsp;{!tpl_FormatText($task_details['closure_comment'])}
  <?php endif; ?>
  <?php endif; ?>

  <?php if (count($penreqs)): ?>
  <span id="pendingreq">{$details_text['taskpendingreq']}</span>
  <?php endif; ?>

  <div id="actionbuttons">
    <?php if ($task_details['is_closed']): ?>

    <?php if ($user->can_close_task($task_details)): ?>
    <a href="{$baseurl}?do=modify&amp;action=reopen&amp;task_id={Get::val('id')}">
      {$details_text['reopenthistask']}</a>
    <?php elseif (!$fs->adminRequestCheck(2, $task_details['task_id']) && !$user->isAnon()): ?>
    <a href="#close" id="reqclose" class="button" onclick="showhidestuff('closeform');">
      {$details_text['reopenrequest']}</a>
    <div id="closeform">
      <form name="form3" action="{$baseurl}" method="post" id="formclosetask">
        <div>
          <input type="hidden" name="do" value="modify" />
          <input type="hidden" name="action" value="requestreopen" />
          <input type="hidden" name="task_id" value="{Get::val('id')}" />
          <label for="reason">{$details_text['givereason']}</label>
          <textarea id="reason" name="reason_given"></textarea><br />
          <input class="adminbutton" type="submit" value="{$details_text['submitreq']}" />
        </div>
      </form>
    </div>
    <?php endif; ?>

    <?php else: ?>

    <?php if ($user->can_close_task($task_details) && !$d_open): ?>
    <a href="#close" id="closetask" class="button" onclick="showhidestuff('closeform');">
      {$details_text['closetask']}</a><div id="closeform">
      <form action="{$baseurl}" method="post" id="formclosetask">
        <div>
          <input type="hidden" name="do" value="modify" />
          <input type="hidden" name="action" value="close" />
          <input type="hidden" name="assigned_to" value="{$task_details['assigned_to']}" />
          <input type="hidden" name="task_id" value="{Get::val('id')}" />
          <select class="adminlist" name="resolution_reason">
            <option value="0">{$details_text['selectareason']}</option>
            {!tpl_options($proj->listResolutions())}
          </select>
          <input class="adminbutton" type="submit" name="buSubmit" value="{$details_text['closetask']}" />
          {$details_text['closurecomment']}
          <textarea class="admintext" name="closure_comment" rows="3" cols="30"></textarea>
          <input type="checkbox" name="mark100" value="1" checked="checked" />&nbsp;&nbsp;{$details_text['mark100']}
        </div>
      </form>
    </div>
    <?php elseif (!$d_open && $task_details['assigned_to'] == $user->id
    && !$fs->AdminRequestCheck(1, $task_details['task_id'])): ?>
    <a href="#close" id="reqclose" class="button" onclick="showhidestuff('closeform');">
      {$details_text['requestclose']}</a>
    <div id="closeform">
      <form name="form3" action="{$baseurl}" method="post" id="formclosetask">
        <div>
          <input type="hidden" name="do" value="modify" />
          <input type="hidden" name="action" value="requestclose" />
          <input type="hidden" name="task_id" value="{Get::val('id')}" />
          <label for="reason">{$details_text['givereason']}</label>
          <textarea id="reason" name="reason_given"></textarea><br />
          <input class="adminbutton" type="submit" value="{$details_text['submitreq']}" />
        </div>
      </form>
    </div>
    <?php endif; ?>

    <?php if ($user->can_take_ownership($task_details)): ?>
    <a id="own" class="button"
      href="{$baseurl}?do=modify&amp;action=takeownership&amp;ids={Get::val('id')}">
      {$details_text['assigntome']}</a>
    <?php endif; ?>

    <?php if ($user->can_edit_task($task_details)): ?>
    <a id="edittask" class="button" href="{$fs->CreateURL('edittask', Get::val('id'))}">
      {$details_text['edittask']}</a>
    <?php endif; ?>

    <?php if ($user->can_mark_public($task_details)): ?>
    <a id="public" class="button"
      href="{$baseurl}?do=modify&amp;action=makepublic&amp;id={Get::val('id')}">
      {$details_text['makepublic']}</a>
    <?php elseif ($user->can_mark_private($task_details)): ?>
    <a id="private" class="button"
      href="{$baseurl}?do=modify&amp;action=makeprivate&amp;id={Get::val('id')}">
      {$details_text['makeprivate']}</a>
    <?php endif; ?>

    <?php if (!$user->isAnon()): ?>
    <?php if ($watched): ?>
    <a id="addnotif" class="button"
      href="{$baseurl}?do=modify&amp;action=add_notification&amp;ids={Get::val('id')}&amp;user_id={$user->id}">
      {$details_text['watchtask']}</a>
    <?php else: ?>
    <a id="removenotif" class="button"
      href="{$baseurl}?do=modify&amp;action=remove_notification&amp;ids={Get::val('id')}&amp;user_id={$user->id}">
      {$details_text['stopwatching']}</a>
    <?php endif; ?>
    <?php endif; ?>

    <?php endif; ?>
  </div>
</div>
