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
        <input class="mainbutton" type="submit" value="{$index_text['searchthisproject']}" />
        <input id="searchtext" name="string" type="text" size="20"
        maxlength="100" value="{Get::val('string')}" accesskey="q" />
        
        <span onclick="toggleSearchBox();" style="cursor:pointer">
        <span id="advancedsearchstate" class="showstate">
          <?php echo (Cookie::val('advancedsearch')) ? '-' : '+'; ?>
        </span>{$index_text['advanced']}
        </span>
        
        <div id="sc2" class="switchcontent" <? if (!Cookie::val('advancedsearch')):?>style="display:none;"<?php endif; ?> >
        <fieldset><legend>{$index_text['miscellaneous']}</legend>
        {!tpl_checkbox('search_in_comments', Get::has('search_in_comments'), 'sic')}
        <label class="default" for="sic">{$index_text['searchcomments']}</label>
        
        {!tpl_checkbox('search_for_all', Get::has('search_for_all'), 'sfa')}
        <label class="default" for="sfa">{$index_text['searchforall']}</label>

        <?php
        if ($due_date = Get::val('date')) {
            $show_date = $index_text['due'] . ' ' . $due_date;
        } else {
            $due_date  = '0';
            $show_date = $index_text['selectduedate'];
        }
        ?>
        <input id="duedatehidden" type="hidden" name="date" value="{$due_date}" />
        <span id="duedateview" title="{$index_text['due']}">{$show_date}</span> <small>|</small>
        <a href="#" onclick="document.getElementById('duedatehidden').value = '0';document.getElementById('duedateview').innerHTML = '{$index_text['selectduedate']}'">X</a>
        <script type="text/javascript">
           Calendar.setup({
              inputField  : "duedatehidden",  // ID of the input field
              displayArea : "duedateview",    // The display field
              button      : "duedateview"     // ID of the button
           });
        </script>

        <?php
        if ($since_date = Get::val('changedsince')) {
            $show_date = $index_text['changedsince'] . ' ' . $since_date;
        } else {
            $since_date  = '0';
            $show_date = $index_text['selectsincedate'];
        }
        ?>
        <input id="changedsincehidden" type="hidden" name="changedsince" value="{$since_date}" />
        <span id="changedsinceview" title="{$index_text['changedsince']}">{$show_date}</span> <small>|</small>
        <a href="#" onclick="document.getElementById('changedsincehidden').value = '0';document.getElementById('changedsinceview').innerHTML = '{$index_text['selectsincedate']}'">X</a>
        <script type="text/javascript">
           Calendar.setup({
              inputField  : "changedsincehidden",  // ID of the input field
              displayArea : "changedsinceview",    // The display field
              button      : "changedsinceview"     // ID of the button
           });
        </script>
        </fieldset>

        <fieldset><legend>{$index_text['taskproperties']}</legend>
        <label class="default multisel" for="type">{$index_text['tasktype']}</label>
        <select name="type[]" id="type" multiple="multiple" size="5">
          {!tpl_options(array('' => $index_text['alltasktypes']) + $proj->listTaskTypes(), Get::val('type', ''))}
        </select>
        
        <label class="default multisel" for="sev">{$index_text['severity']}</label>
        <select name="sev[]" id="sev" multiple="multiple" size="5">
          {!tpl_options(array('' => $index_text['allseverities']) + $severity_list, Get::val('sev', ''))}
        </select>
        
        <label class="default multisel" for="due">{$index_text['dueversion']}</label>
        <select name="due[]" id="due" {!tpl_disableif(Get::val('project') === '0')} multiple="multiple" size="5">
          {!tpl_options(array('' => $index_text['dueanyversion']) + $proj->listVersions(false, 3), Get::val('due', ''))}
        </select>
        
        <label class="default multisel" for="cat">{$index_text['category']}</label>
        <select name="cat[]" id="cat" {!tpl_disableif(Get::val('project') === '0')} multiple="multiple" size="5">
          {!tpl_options(array('' => $index_text['allcategories']) + $proj->listCatsIn(), Get::val('cat', ''))}
        </select>

        <label class="default multisel" for="status">{$index_text['status']}</label>
        <select name="status[]" id="status" multiple="multiple" size="5">
          {!tpl_options(array('' => $index_text['allstatuses']) +
                        array('open' => $index_text['allopentasks']) +
                        $proj->listTaskStatuses(), Get::val('status', 'open'))}
        </select>
        </fieldset>

        <fieldset><legend>{$index_text['users']}</legend>
        <label class="default multisel" for="dev">{$index_text['assignedto']}</label>
        <select name="dev[]" id="dev" multiple="multiple" size="5">
          {!tpl_options(array('' => $index_text['alldevelopers']) +
                        array('notassigned' => $index_text['notyetassigned']) +
                        array($user->id => $index_text['assignedtome']) +
                        $proj->UserList(), Get::val('dev', ''))}
        </select>
        
        <label class="default multisel" for="opened">{$index_text['openedby']}</label>
        <select name="opened[]" id="opened" multiple="multiple" size="5">
          {!tpl_options(array('' => $index_text['alldevelopers']) +
                        array('-1' => $index_text['anonusers']) +
                        $proj->UserList(array(), true), Get::val('opened', ''))}
        </select>
        </fieldset>

       </div>
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
            <th class="caret">
            </th>
            <?php if (!$user->isAnon()): ?>
            <th class="ttcolumn"></th>
            <?php endif; ?>
            <?php foreach ($visible as $col): ?>
            {!tpl_list_heading($col, "<th%s>%s</th>")}
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($tasks as $task_details):?>
        <tr id="task{!$task_details['task_id']}" class="severity{$task_details['task_severity']}">
          <td class="caret">
          </td>
          <?php if (!$user->isAnon()): ?>
          <td class="ttcolumn">
            <input class="ticktask" type="checkbox" name="ids[{!$task_details['task_id']}]" value="1" />
          </td>
          <?php endif; ?>
          <?php foreach ($visible as $col): ?>
          {!tpl_draw_cell($task_details, $col)}
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
        </tbody>
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
            {!$fs->pagenums($pagenum, $perpage, $total, $get . '&amp;order=' . Get::val('order') . '&amp;tasks=' . Get::val('tasks'))}
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
