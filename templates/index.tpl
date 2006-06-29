<?php if(isset($update_error)): ?>
<div id="updatemsg">
	<span class="bad"> {L('updatewrong')}</span>
	<a href="?hideupdatemsg=yep">{L('hidemessage')}</a>
</div>
<?php endif; ?>

<?php if(isset($updatemsg)): ?>
<div id="updatemsg">
    <a href="http://flyspray.rocks.cc/">{L('updatefs')}</a> {L('currentversion')}
    <span class="bad">{$fs->version}</span> {L('latestversion')} <span class="good">{$_SESSION['latest_version']}</span>.
    <a href="?hideupdatemsg=yep">{L('hidemessage')}</a>
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
        <span class="save_search"><label for="save_search" id="lblsaveas">{L('saveas')}</label>
        <input class="text" type="text" value="{Get::val('search_name')}" id="save_search" name="search_name" size="15" />
        <button onclick="savesearch('{$_SERVER['QUERY_STRING']}', '{$baseurl}', '{L('saving')}')" type="button">{L('OK')}</button></span>
        <?php endif; ?>
        
        <button type="submit">{L('searchthisproject')}</button>
        <input class="text" id="searchtext" name="string" type="text" size="20"
        maxlength="100" value="{Get::val('string')}" accesskey="q" />
        
        <span id="searchstate" onclick="toggleSearchBox('{$this->themeUrl()}');" style="cursor:pointer">
        <span id="advancedsearchstate" class="showstate">
        <img id="advancedsearchstateimg" src="<?php echo (Cookie::val('advancedsearch')) ? $this->get_image('edit_remove') : $this->get_image('edit_add'); ?>"
             alt="<?php echo (Cookie::val('advancedsearch')) ? '-' : '+'; ?>" width="16" height="16" />
        </span>{L('advanced')}
        </span>
        
        <div id="sc2" class="switchcontent" <?php if (!Cookie::val('advancedsearch')):?>style="display:none;"<?php endif; ?> >
        <fieldset><legend>{L('miscellaneous')}</legend>
        {!tpl_checkbox('search_in_comments', Get::has('search_in_comments'), 'sic')}
        <label class="left" for="sic">{L('searchcomments')}</label>
        
        {!tpl_checkbox('search_for_all', Get::has('search_for_all'), 'sfa')}
        <label class="left" for="sfa">{L('searchforall')}</label>

        {!tpl_checkbox('only_watched', Get::has('only_watched'), 'only_watched')}
        <label class="left" for="only_watched">{L('taskswatched')}</label>
        
        {!tpl_checkbox('only_primary', Get::has('only_primary'), 'only_primary')}
        <label class="left" for="only_primary">{L('onlyprimary')}</label>
        
        {!tpl_datepicker('', L('selectduedate'), L('due'))}
        {!tpl_datepicker('changedsince', L('selectsincedate'), L('changedsince'))}

        </fieldset>

        <fieldset><legend>{L('taskproperties')}</legend>
        <label class="default multisel" for="type">{L('tasktype')}</label>
        <select name="type[]" id="type" multiple="multiple" size="5">
          {!tpl_options(array('' => L('alltasktypes')) + $proj->listTaskTypes(), Get::val('type', ''))}
        </select>
        
        <label class="default multisel" for="sev">{L('severity')}</label>
        <select name="sev[]" id="sev" multiple="multiple" size="5">
          {!tpl_options(array('' => L('allseverities')) + $severity_list, Get::val('sev', ''))}
        </select>
        
        <label class="default multisel" for="due">{L('dueversion')}</label>
        <select name="due[]" id="due" {!tpl_disableif(Get::val('project') === '0')} multiple="multiple" size="5">
          {!tpl_options(array('' => L('dueanyversion'), 0 => L('unassigned')) + $proj->listVersions(false), Get::val('due', ''))}
        </select>
        
        <label class="default multisel" for="reported">{L('reportedversion')}</label>
        <select name="reported[]" id="reported" {!tpl_disableif(Get::val('project') === '0')} multiple="multiple" size="5">
          {!tpl_options(array('' => L('anyversion')) + $proj->listVersions(false), Get::val('reported', ''))}
        </select>
        
        <label class="default multisel" for="cat">{L('category')}</label>
        <select name="cat[]" id="cat" {!tpl_disableif(Get::val('project') === '0')} multiple="multiple" size="5">
          {!tpl_options(array('' => L('allcategories')) + $proj->listCategories(), Get::val('cat', ''))}
        </select>

        <label class="default multisel" for="status">{L('status')}</label>
        <select name="status[]" id="status" multiple="multiple" size="5">
          {!tpl_options(array('' => L('allstatuses')) +
                        array('open' => L('allopentasks')) +
                        array('closed' => L('allclosedtasks')) +
                        $proj->listTaskStatuses(), Get::val('status', 'open'))}
        </select>
        
        <label class="default multisel" for="cat">{L('percentcomplete')}</label>
        <select name="percent[]" id="percent" {!tpl_disableif(Get::val('project') === '0')} multiple="multiple" size="5">
          <?php $percentages = array(); for ($i = 0; $i <= 100; $i += 10) $percentages[$i] = $i; ?>
          {!tpl_options(array('' => L('anyprogress')) + $percentages, Get::val('percent', ''))}
        </select>
        </fieldset>

        <fieldset><legend>{L('users')}</legend>
        <label class="default multisel" for="opened">{L('openedby')}</label>
        <input class="users text" size="30" type="text" name="opened" id="opened" value="{Get::val('opened')}" />
        <div class="autocomplete" id="opened_complete"></div>
        <script type="text/javascript">
            new Ajax.Autocompleter('opened', 'opened_complete', 'javascript/callbacks/usersearch.php', {})
        </script>

        <label class="default multisel" for="dev">{L('assignedto')}</label>
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
            <th class="ttcolumn">
                <?php if (!$user->isAnon() && $total): ?>
                <a href="javascript:ToggleSelected('massops')">
                  <img alt="{L('toggleselected')}" title="{L('toggleselected')}" src="{$this->get_image('kaboodleloop')}" width="16" height="16" />
                </a>
                <?php endif; ?>
            </th>
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
            {!sprintf(L('taskrange'), $offset + 1,
              ($offset + $perpage > $total ? $total : $offset + $perpage), $total)}
          </td>
          <td id="numbers">
            {!pagenums($pagenum, $perpage, $total, $get . '&amp;order=' . Get::val('order') . '&amp;sort=' . Get::val('sort') . '&amp;tasks=' . Get::val('tasks') . '&amp;order2=' . Get::val('order2') . '&amp;sort2=' . Get::val('sort2'))}
          </td>
          <?php else: ?>
          <td id="taskrange"><strong>{L('noresults')}</strong></td>
          <?php endif; ?>
        </tr>
      </table>
      <?php if (!$user->isAnon() && $total): ?>
      <div id="massopsactions">
        <select name="action">
          <option value="add_notification">{L('watchtasks')}</option>
          <option value="remove_notification">{L('stopwatchingtasks')}</option>
          <option value="takeownership">{L('assigntaskstome')}</option>
        </select>
        <button type="submit">{L('takeaction')}</button>
      </div>
      <?php endif ?>
    </div>
  </form>
</div>
