<ul id="submenu">
  <li><a href="#events">{$reports_text['events']}</a></li>
  <li><a href="#votes">{$reports_text['votes']}</a></li>
</ul>
<div id="events" class="tab">
  <form action="{$baseurl}index.php" method="get">
    <table id="event1">
      <tr>
        <td>{$reports_text['tasks']}</td>
        <td><label class="inline">{!tpl_checkbox('open', (Req::has('open')))}
            {$reports_text['opened']}</label></td>
        <td><label class="inline">{!tpl_checkbox('close', (Req::has('close')))}
            {$reports_text['closed']}</label></td>
        <td><label class="inline">{!tpl_checkbox('edit', (Req::has('edit')))}
            {$reports_text['edited']}</label></td>
      </tr>
      <tr>
        <td></td>
        <td><label class="inline">{!tpl_checkbox('assign', (Req::has('assign')))}
            {$reports_text['assigned']}</label></td>
        <td><label class="inline">{!tpl_checkbox('comments', (Req::has('comments')))}
            {$reports_text['comments']}</label></td>
        <td><label class="inline">{!tpl_checkbox('attachments', (Req::has('attachments')))}
            {$reports_text['attachments']}</label></td>
      </tr>
      <tr>
        <td></td>
        <td><label class="inline">{!tpl_checkbox('related', (Req::has('related')))}
            {$reports_text['relatedtasks']}</label></td>
        <td><label class="inline">{!tpl_checkbox('notifications', (Req::has('notifications')))}
            {$reports_text['notifications']}</label></td>
        <td><label class="inline">{!tpl_checkbox('reminders', (Req::has('reminders')))}
            {$reports_text['reminders']}</label></td>
      </tr>
    </table>
    
    <table>
      <tr>
        <td>
          <input type="radio" id="datewithin" name="date" value="within" <?php if (Req::val('date') == 'within') echo 'checked="checked"';?> />
          <label class="inline" for="datewithin">{$reports_text['within']}</label>
        </td>
        <td colspan="6">
          <select onclick="getElementById('datewithin').checked=true" name="within">
          {!tpl_options(array('day' => $reports_text['pastday'],
                              'week' => $reports_text['pastweek'],
                              'month' => $reports_text['pastmonth'],
                              'year' => $reports_text['pastyear'],
                              'all' => $reports_text['nolimit']), Req::val('within'))}
          </select>
        </td>
      </tr>
      <tr>
        <td>
          <input type="radio" id="datefrom" name="date" value="from" <?php if (Req::val('date') == 'from') echo 'checked="checked"';?> />
          <label class="inline" for="datefrom">{$reports_text['from']}</label>
        </td>
        <td onclick="getElementById('datefrom').checked=true">
            {!tpl_datepicker('from', $reports_text['selectfromdate'], $reports_text['from'])}
          &mdash;
            {!tpl_datepicker('to', $reports_text['selecttodate'], $reports_text['to'])}
        </td>
      </tr>
      <tr>
        <td>
          <input type="radio" id="dateduein" name="date" value="duein" <?php if (Req::val('date') == 'duein') echo 'checked="checked"';?> />
          <label class="inline" for="dateduein">{$reports_text['duein']}</label>
        </td>
        <td colspan="6">
          <select onclick="getElementById('dateduein').checked=true" name="duein">
            {!tpl_options($proj->listVersions(false, 3), Req::val('duein'))}
          </select>
        </td>
      </tr>
    </table>

    <input type="hidden" name="do" value="reports" />
    <button type="submit" name="submit">{$reports_text['show']}</button>
  </form>
  
  <?php if($histories): ?>
  <div id="tasklist">
  <table id="tasklist_table">
   <thead>
    <tr>
      <th class="taskid">
        <a href="{CreateURL('reports', null, null, array('sort' => (Req::val('order') == 'id' && $sort == 'DESC') ? 'asc' : 'desc', 'order' => 'id') + $_GET)}">
           {$index_text['id']}
        </a>
      </th>
      <th>{$details_text['summary']}</th>
      <th>
        <a href="{CreateURL('reports', null, null, array('sort' => (Req::val('order') == 'date' && $sort == 'DESC') ? 'asc' : 'desc', 'order' => 'date') + $_GET)}">
          {$details_text['eventdate']}
        </a>
      </th>
      <th>
        <a href="{CreateURL('reports', null, null, array('sort' => (Req::val('order') == 'user' && $sort == 'DESC') ? 'asc' : 'desc', 'order' => 'user') + $_GET)}">
          {$details_text['user']}
        </a>
      </th>
      <th>
        <a href="{CreateURL('reports', null, null, array('sort' => (Req::val('order') == 'type' && $sort == 'DESC') ? 'asc' : 'desc', 'order' => 'type') + $_GET)}">
          {$details_text['event']}
        </a>
      </th>
    </tr>
   </thead>
    <?php foreach ($histories as $history): ?>
    <tr class="severity{$history['task_severity']}" onclick="openTask('{CreateURL('details', $history['task_id'])}')">
      <td>{!tpl_tasklink($history, 'FS#' . $history['task_id'])}</td>
      <td>{!tpl_tasklink($history)}</td>
      <td>{formatDate($history['event_date'], true)}</td>
      <td>{!tpl_userlink($history['user_id'])}</td>
      <td>{!event_description($history)}</td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>
  <?php endif; ?>
</div>

<div id="votes" class="tab">
    <?php if (!empty($tasks_voted_for)): ?>
        <table><tr><th></th><th>{!$reports_text['votes']}</th><th>{!$details_text['summary']}</th></tr>
        <?php foreach ($tasks_voted_for AS $key => $val):?>
            <tr>
                <td><img src="{$baseurl}themes/{$proj->prefs['theme_style']}/dropdown.png"
                            title="{$reports_text['moreinfo']}" alt="" />
                </td>
                <td>{$val}</td>
                <td>{!tpl_tasklink($key)}</td>
            </tr>
        <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
