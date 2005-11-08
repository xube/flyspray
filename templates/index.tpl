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
            <?php foreach ($visible as $col): ?>
            {!tpl_list_heading($col, "<th%s>%s</th>")}
            <?php endforeach; ?>
          </tr>
        </thead>
        <?php foreach ($tasks as $task_details): ?>
        <tr id="task{!$task_id}" class="severity{$task_details['task_severity']}">
          <?php if (!$user->isAnon()): ?>
          <td class="ttcolumn">
            <input class="ticktask" type="checkbox" name="ids[{!$task_id}]" value="1" />
          </td>
          <?php endif; ?>
          <?php foreach ($visible as $col): ?>
          {!tpl_draw_cell($task_details, $col)}
          <?php endforeach; ?>
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
