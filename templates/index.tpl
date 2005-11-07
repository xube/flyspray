<!-- Query line {{{ -->
<div id="search">
  <map id="projectsearchform" name="projectsearchform">
    <form action="index.php" method="get">
      <div>
        <input type="hidden" name="tasks" value="{Get::val('tasks')}" />
        <?php if(Get::val('project') == '0'): ?>
        <input type="hidden" name="project" value="0" />
        <?php else: ?>
        <input type="hidden" name="project" value="{$proj->id}" />
        <?php endif; ?>
        <em>{$index_text['searchthisproject']}:</em>
        <input id="searchtext" name="string" type="text" size="20"
        maxlength="100" value="{Get::val('string')}" accesskey="q" />

        <select name="type">
          <option value="">{$index_text['alltasktypes']}</option>
          {!tpl_options($proj->listTaskTypes(), Get::val('type'))}
        </select>
        <select name="sev">
          <option value="">{$index_text['allseverities']}</option>
          {!tpl_options($severity_list, Get::val('sev'))}
        </select>
        <select name="due" {!tpl_disableif(Get::val('project') === '0')}>
          <option value="">{$index_text['dueanyversion']}</option>
          {!tpl_options($proj->listVersions(false, 3), Get::val('due'))}
        </select>
        <select name="dev">
          <option value="">{$index_text['alldevelopers']}</option>
          <option value="notassigned" <?php
            if (Get::val('dev') == "notassigned") echo 'selected="selected"';
            ?>>{$index_text['notyetassigned']}</option>
          <?php $fs->ListUsers($proj->id, Get::val('dev')); ?>
        </select>

        <select name="cat" {!tpl_disableif(Get::val('project') === '0')}>
          <option value="">{$index_text['allcategories']}</option>
          {!tpl_options($proj->listCatsIn(), Get::val('cat'))}
        </select>

        <select name="status">
          <option value="all">{$index_text['allstatuses']}</option>
          <option value="" <?php
            if (!Get::val('status')) echo 'selected="selected"';
            ?>>{$index_text['allopentasks']}</option>
          {!tpl_options($status_list, Get::val('status'))}
          <option value="closed" <?php
            if (Get::val('status') == "closed") echo 'selected="selected"';
            ?>>{$index_text['closed']}</option>
        </select>
        <?php
        if ($due_date = Get::val('date')) {
            $show_date = $index_text['due'] . ' ' . $due_date;
        } else {
            $due_date  = '0';
            $show_date = $index_text['selectduedate'];
        }
        ?>
        <input id="duedatehidden" type="hidden" name="date" value="{$due_date}" />
        <span id="duedateview">{$show_date}</span> <small>|</small>
        <a href="#" onclick="document.getElementById('duedatehidden').value = '0';document.getElementById('duedateview').innerHTML = '{$index_text['selectduedate']}'">X</a>
        <script type="text/javascript">
           Calendar.setup({
              inputField  : "duedatehidden",  // ID of the input field
              ifFormat    : "%d-%b-%Y",       // the date format
              displayArea : "duedateview",    // The display field
              daFormat    : "%d-%b-%Y",
              button      : "duedateview"     // ID of the button
           });
        </script>
       
        <input class="mainbutton" type="submit" value="{$index_text['search']}" />
      </div>
    </form>
  </map>
</div>
<!-- }}} -->

<div id="tasklist">
  <form action="index.php" id="massops" method="post">
    <div>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
      <table id="tasklist_table">
        <thead>
          <tr>
            <?php if (!$user->isAnon()): ?>
            <th class="ttcolumn"></th>
            <?php endif; ?>
            {!list_heading('id',          'id')}
            {!list_heading('project',     'proj', 'asc')}
            {!list_heading('tasktype',    'type', 'asc')}
            {!list_heading('category',    'cat', 'asc')}
            {!list_heading('severity',    'sev')}
            {!list_heading('priority',    'pri')}
            {!list_heading('summary',     '')}
            {!list_heading('dateopened',  'date')}
            {!list_heading('status',      'status')}
            {!list_heading('openedby',    'openedby', 'asc')}
            {!list_heading('assignedto',  'assignedto', 'asc')}
            {!list_heading('lastedit',    'lastedit')}
            {!list_heading('reportedin',  'reportedin')}
            {!list_heading('dueversion',  'due')}
            {!list_heading('duedate',     'duedate')}
            {!list_heading('comments',    '', '', "themes/".$proj->prefs['theme_style']."/comment.png")}
            {!list_heading('attachments', '', '', "themes/".$proj->prefs['theme_style']."/attachment.png")}
            {!list_heading('progress',    'prog')}
          </tr>
        </thead>
        <?php foreach ($tasks as $task_details):
        $task_id = intval($task_details['task_id']);
        $last_edited_time = '';
        if ($task_details['last_edited_time'] > 0) {
        $last_edited_time = $fs->formatDate($task_details['last_edited_time'], false);
        }
        ?>
        <tr id="task{!$task_id}" class="severity{$task_details['task_severity']}">
          <?php if (!$user->isAnon()): ?>
          <td class="ttcolumn">
            <input class="ticktask" type="checkbox" name="ids[{!$task_id}]" value="1" />
          </td>
          <?php endif; ?>

          {!list_cell($task_id, 'id',          $task_id, 1, $fs->CreateURL('details', $task_id))}
          {!list_cell($task_id, 'project',     $task_details['project_title'], 1)}
          {!list_cell($task_id, 'tasktype',    $task_details['task_type'], 1)}
          {!list_cell($task_id, 'category',    $task_details['product_category'], 1)}
          {!list_cell($task_id, 'severity',
            $severity_list[$task_details['task_severity']], 1)}
          {!list_cell($task_id, 'priority',
            $priority_list[$task_details['task_priority']], 1)}
          {!list_cell($task_id, 'summary',     $task_details['item_summary'], 0,
            $fs->CreateURL('details', $task_id))}
          {!list_cell($task_id, 'dateopened',
            $fs->formatDate($task_details['date_opened'], false))}
          <?php if ($task_details['is_closed']): ?>
          {!list_cell($task_id, 'status',      $index_text['closed'], 1)}
          <?php else: ?>
          {!list_cell($task_id, 'status',
            $status_list[$task_details['item_status']], 1)}
          <?php endif; ?>
          {!list_cell($task_id, 'openedby',    $task_details['opened_by'], 0)}
          <?php if ($task_details['assigned_to']): ?>
          {!list_cell($task_id, 'assignedto',  $task_details['assigned_to'], 0)}
          <?php else: ?>
          {!list_cell($task_id, 'assignedto',  $details_text['noone'], 0)}
          <?php endif; ?>
          {!list_cell($task_id, 'lastedit',    $last_edited_time)}
          {!list_cell($task_id, 'reportedin',  $task_details['product_version'])}
          {!list_cell($task_id, 'dueversion',  $task_details['closedby_version'], 1)}
          {!list_cell($task_id, 'duedate',     $task_details['due_date'], 1)}
          {!list_cell($task_id, 'comments',    $task_details['num_comments'])}
          {!list_cell($task_id, 'attachments', $task_details['num_attachments'])}
          {!list_cell($task_id, 'progress',
            tpl_img("themes/".$proj->prefs['theme_style']
            ."/percent-".$task_details['percent_complete'].".png",
            $task_details['percent_complete'] . '% ' .  $index_text['complete']))}

        </tr>
        <?php endforeach; ?>
      </table>
      <table id="pagenumbers">
        <tr>
          <?php if ($total): ?>
          <td id="taskrange">
            {!sprintf($index_text['taskrange'], $offset + 1,
              ($offset + $perpage > $total ? $total : $offset + $perpage), $total)}
            <?php if (!$user->isAnon() && $total): ?>
            &nbsp;&nbsp;<a href="javascript://;" onclick="ToggleSelectedTasks()">
              {$index_text['toggleselected']}</a>
            <?php endif; ?>
          </td>
          <td id="numbers">
            {!$fs->pagenums($pagenum, $perpage, $total, $get . '&amp;order=' . Get::val('order'))}
          </td>
          <?php else: ?>
          <td id="taskrange"><strong>{$index_text['noresults']}</strong></td>
          <?php endif; ?>
        </tr>
      </table>
      <?php if (!$user->isAnon() && $total): ?>
      <div id="massopsactions">
        <select name="action">
          <option value="add_notification">{$index_text['watchtasks']}</option>
          <option value="remove_notification">{$index_text['stopwatching']}</option>
          <option value="takeownership">{$index_text['assigntome']}</option>
        </select>
        <input class="mainbutton" type="submit" value="{$index_text['takeaction']}" />
      </div>
      <?php endif ?>
    </div>
  </form>
</div>

