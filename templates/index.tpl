<?php if(isset($updatemsg)): ?>
<div id="updatemsg">
    <a href="http://flyspray.rocks.cc/">{$language['updatefs']}</a> {$language['currentversion']}
    <span class="bad">{$fs->version}</span> {$language['latestversion']} <span class="good">{$_SESSION['latest_version']}</span>.
    <a href="?hideupdatemsg=yep">{$language['hidemessage']}</a>
</div>
<?php endif; ?>

<div id="search">
  <map id="projectsearchform" name="projectsearchform">
    <form action="index.php" method="get">
      <div>
        <?php if(Get::val('project') == '0'): ?>
        <input type="hidden" name="project" value="0" />
        <?php else: ?>
        <input type="hidden" name="project" value="{$proj->id}" />
        <?php endif; ?>
        <?php if(!$user->isAnon()): ?>
        <span class="save_search"><label for="save_search" id="lblsaveas">{$language['saveas']}</label>
        <input class="text" type="text" value="{Get::val('search_name')}" id="save_search" name="search_name" size="15" />
        <button onclick="savesearch('{$_SERVER['QUERY_STRING']}', '{$baseurl}', '{$language['saved']}')" type="button">{$language['OK']}</button></span>
        <?php endif; ?>
        
        <button type="submit">{$language['searchthisproject']}</button>
        <input class="text" id="searchtext" name="string" type="text" size="20"
        maxlength="100" value="{Get::val('string')}" accesskey="q" />
        
        <span id="searchstate" onclick="toggleSearchBox('{$this->themeUrl()}');" style="cursor:pointer">
        <span id="advancedsearchstate" class="showstate">
        <img id="advancedsearchstateimg" src="<?php echo (Cookie::val('advancedsearch')) ? $this->themeUrl() . 'edit_remove.png' : $this->themeUrl() . 'edit_add.png'; ?>"
             alt="<?php echo (Cookie::val('advancedsearch')) ? '-' : '+'; ?>" width="16" height="16" />
        </span>{$language['advanced']}
        </span>
        
        <div id="sc2" class="switchcontent" <?php if (!Cookie::val('advancedsearch')):?>style="display:none;"<?php endif; ?> >
        <fieldset><legend>{$language['miscellaneous']}</legend>
        {!tpl_checkbox('search_in_comments', Get::has('search_in_comments'), 'sic')}
        <label class="left" for="sic">{$language['searchcomments']}</label>
        
        {!tpl_checkbox('search_for_all', Get::has('search_for_all'), 'sfa')}
        <label class="left" for="sfa">{$language['searchforall']}</label>
        
        {!tpl_datepicker('', $language['selectduedate'], $language['due'])}
        {!tpl_datepicker('changedsince', $language['selectsincedate'], $language['changedsince'])}

        </fieldset>

        <fieldset><legend>{$language['taskproperties']}</legend>
        <label class="default multisel" for="type">{$language['tasktype']}</label>
        <select name="type[]" id="type" multiple="multiple" size="5">
          {!tpl_options(array('' => $language['alltasktypes']) + $proj->listTaskTypes(), Get::val('type', ''))}
        </select>
        
        <label class="default multisel" for="sev">{$language['severity']}</label>
        <select name="sev[]" id="sev" multiple="multiple" size="5">
          {!tpl_options(array('' => $language['allseverities']) + $severity_list, Get::val('sev', ''))}
        </select>
        
        <label class="default multisel" for="due">{$language['dueversion']}</label>
        <select name="due[]" id="due" {!tpl_disableif(Get::val('project') === '0')} multiple="multiple" size="5">
          {!tpl_options(array('' => $language['dueanyversion']) + $proj->listVersions(false, 3), Get::val('due', ''))}
        </select>
        
        <label class="default multisel" for="reported">{$language['reportedversion']}</label>
        <select name="reported[]" id="reported" {!tpl_disableif(Get::val('project') === '0')} multiple="multiple" size="5">
          {!tpl_options(array('' => $language['anyversion']) + $proj->listVersions(false), Get::val('reported', ''))}
        </select>
        
        <label class="default multisel" for="cat">{$language['category']}</label>
        <select name="cat[]" id="cat" {!tpl_disableif(Get::val('project') === '0')} multiple="multiple" size="5">
          {!tpl_options(array('' => $language['allcategories']) + $proj->listCatsIn(), Get::val('cat', ''))}
        </select>

        <label class="default multisel" for="status">{$language['status']}</label>
        <select name="status[]" id="status" multiple="multiple" size="5">
          {!tpl_options(array('' => $language['allstatuses']) +
                        array('open' => $language['allopentasks']) +
                        array('closed' => $language['allclosedtasks']) +
                        $proj->listTaskStatuses(), Get::val('status', 'open'))}
        </select>
        </fieldset>

        <fieldset><legend>{$language['users']}</legend>
        <label class="default multisel" for="opened">{$language['openedby']}</label>
        <input class="users text" size="30" type="text" name="opened" id="opened" value="{Get::val('opened')}" />
        <div class="autocomplete" id="opened_complete"></div>
        <script type="text/javascript">
            new Ajax.Autocompleter('opened', 'opened_complete', 'javascript/callbacks/usersearch.php', {})
        </script>

        <label class="default multisel" for="dev">{$language['assignedto']}</label>
        <input class="users text" size="30" type="text" name="dev" id="dev" value="{Get::val('dev')}" /> 
        <div class="autocomplete" id="dev_complete"></div>
        <script type="text/javascript">
            new Ajax.Autocompleter('dev', 'dev_complete', 'javascript/callbacks/usersearch.php', {})
        </script>

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
            {!sprintf($language['taskrange'], $offset + 1,
              ($offset + $perpage > $total ? $total : $offset + $perpage), $total)}
            <?php if (!$user->isAnon() && $total): ?>
            &nbsp;&nbsp;<a href="javascript://;" onclick="ToggleSelectedTasks()">
              {$language['toggleselected']}</a>
            <?php endif; ?>
          </td>
          <td id="numbers">
            {!pagenums($pagenum, $perpage, $total, $get . '&amp;order=' . Get::val('order') . '&amp;tasks=' . Get::val('tasks'))}
          </td>
          <?php else: ?>
          <td id="taskrange"><strong>{$language['noresults']}</strong></td>
          <?php endif; ?>
        </tr>
      </table>
      <?php if (!$user->isAnon() && $total): ?>
      <div id="massopsactions">
        <select name="action">
          <option value="add_notification">{$language['watchtasks']}</option>
          <option value="remove_notification">{$language['stopwatchingtasks']}</option>
          <option value="takeownership">{$language['assigntaskstome']}</option>
        </select>
        <button type="submit">{$language['takeaction']}</button>
      </div>
      <?php endif ?>
    </div>
  </form>
</div>
