<div id="taskdetails">
<span id="navigation">
  <a href="{CreateURL(array('details', 'task' . $task['task_id']))}">
    <small>{L('linktotask')}</small>
  </a> |
  <a href="#" onclick="return mailtask('{#L('mailtask')}', '{rawurlencode($task['item_summary'])}', '{rawurlencode($task['detailed_desc'])}');">
    <small>{L('emailtask')}</small>
  </a> |
  <?php if ($prev_id): ?>
  {!tpl_tasklink($prev_id, L('previoustask'), false, array('id'=>'prev', 'accesskey' => 'p'))}
  <?php endif; ?>
  <?php if ($prev_id): ?> | <?php endif; ?>
  <?php $params = $_GET; unset($params['do'], $params['action'], $params['task_id'], $params['switch'], $params['project']); ?>
  <a href="{CreateUrl(array('index', 'proj' . $proj->id), array('do' => 'index') + $params)}">{L('tasklist')}</a>
  <?php if ($next_id): ?> | <?php endif; ?>
  <?php if ($next_id): ?>
  {!tpl_tasklink($next_id, L('nexttask'), false, array('id'=>'next', 'accesskey' => 'n'))}
  <?php endif; ?>
  <span class="hide" id="task_id" title="{$task['task_id']}"></span>
</span>

  <h2 class="summary task colorfield{$task['field' . $fs->prefs['color_field']]}">
	 {$task['project_prefix']}#{$task['prefix_id']} - {$task['item_summary']}
  </h2>

  <div id="fineprint">
	 {L('attachedtoproject')}:
	 <a href="{$_SERVER['SCRIPT_NAME']}?project={$task['project_id']}">{$proj->prefs['project_title']}</a>
	 <br />
	 {L('openedby')} {!tpl_userlink($task['opened_by'])}
     <?php if ($task['anon_email'] && $user->perms('view_tasks')): ?>
     ({$task['anon_email']})
     <?php endif; ?>
	 - {formatDate($task['date_opened'], true)}
	 <?php if ($task['last_edited_by']): ?>
	 <br />
	 {L('editedby')}  {!tpl_userlink($task['last_edited_by'])}
	 - {formatDate($task['last_edited_time'], true)}
	 <?php endif; ?>
  </div>

  <table><tr><td id="taskfieldscell"><?php // small layout table ?>

  <div id="taskfields">
	 <table>
        <?php foreach ($proj->fields as $field): ?>
        <tr>
		  <th id="f{$field->id}">{$field->prefs['field_name']}</th>
		  <td class="task_field{$field->id}" headers="f{$field->id}">
            {!$field->view($task, $parents)}
          </td>
		</tr>
        <?php endforeach; ?>
		<tr>
		  <th id="state">{L('state')}</th>
		  <td headers="state">
			 <?php if ($task['is_closed']): ?>
			 {L('closed')}
			 <?php elseif ($task['closed_by'] && !$task['close_after']): ?>
             <strong class="reopened">{L('reopened')}</strong>
             <?php else: ?>
             {L('open')}
			 <?php endif; ?>
		  </td>
		</tr>
		<tr>
		  <th id="assignedto">{L('assignedto')}</th>
		  <td class="task_assignedto" headers="assignedto">
			 <?php if (empty($assigned_users)): ?>
			 {L('noone')}
			 <?php else:
			 foreach ($assigned_users as $userid):
			 ?>
			 {!tpl_userlink($userid)}<br />
			 <?php endforeach;
			 endif; ?>
		  </td>
		</tr>
		<tr>
		  <th id="percent">{L('percentcomplete')}</th>
		  <td headers="percent" class="middle task_progress">
            <div class="taskpercent"><div style="width:{$task['percent_complete']}%"> </div></div>
		  </td>
		</tr>
        <tr class="votes">
		  <th id="votes">{L('votes')}</th>
		  <td headers="votes">
          <?php if (count($votes)): ?>
          <a href="javascript:showhidestuff('showvotes')">{count($votes)} </a>
          <div id="showvotes" class="hide">
              <ul class="reports">
              <?php foreach ($votes as $vote): ?>
                <li>{!tpl_userlink($vote)} ({formatDate($vote['date_time'])})</li>
              <?php endforeach; ?>
              </ul>
          </div>
          <?php else: ?>
          0
          <?php endif; ?>
          <?php if ($user->can_vote($task) > 0): ?>
          <a href="{$_SERVER['SCRIPT_NAME']}?do=details&amp;action=addvote&amp;task_id={$task['task_id']}">
            ({L('addvote')})</a>
          <?php elseif ($user->can_vote($task) == -2): ?>
          ({L('alreadyvotedthistask')})
          <?php elseif ($user->can_vote($task) == -3): ?>
          ({L('alreadyvotedthisday')})
          <?php endif; ?>
          </td>
        </tr>
        <tr>
		  <th id="private">{L('private')}</th>
		  <td headers="private">
            <?php if ($task['mark_private']): ?>
            {L('yes')}
            <?php else: ?>
            {L('no')}
            <?php endif; ?>

            <?php if ($user->can_change_private($task) && $task['mark_private']): ?>
            <a href="{$_SERVER['SCRIPT_NAME']}?do=details&amp;action=makepublic&amp;task_id={$task['task_id']}">
            ({L('makepublic')})</a>
            <?php elseif ($user->can_change_private($task) && !$task['mark_private']): ?>
            <a href="{$_SERVER['SCRIPT_NAME']}?do=details&amp;action=makeprivate&amp;task_id={$task['task_id']}">
               ({L('makeprivate')})</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php if (!$user->isAnon()): ?>
        <tr>
		  <th id="watching">{L('watching')}</th>
		  <td headers="watching">
              <?php if ($watched): ?>
              {L('yes')}
              <?php else: ?>
              {L('no')}
              <?php endif; ?>

              <?php if (!$watched): ?>
              <a accesskey="w"
              href="{$_SERVER['SCRIPT_NAME']}?do=details&amp;task_id={$task['task_id']}&amp;action=add_notification&amp;ids={$task['task_id']}&amp;user_id={$user->id}">
              ({L('watchtask')})</a>
              <?php else: ?>
              <a accesskey="w"
              href="{$_SERVER['SCRIPT_NAME']}?do=details&amp;task_id={$task['task_id']}&amp;action=remove_notification&amp;ids={$task['task_id']}&amp;user_id={$user->id}">
              ({L('stopwatching')})</a>
              <?php endif; ?>
          </td>
        </tr>
        <?php endif; ?>
	 </table>
  </div>

  </td><td>

  <div id="taskdetailsfull">
	 <h3 class="taskdesc">{L('details')}</h3>
     <div id="taskdetailstext">{!$task_text}</div>

     <?php $attachments = $proj->listTaskAttachments($task['task_id']);
           $this->display('common.attachments.tpl', 'attachments', $attachments); ?>
  </div>

  </td></tr></table>

  <div id="taskinfo">
	 <div id="taskdeps">
		<b>{L('taskdependson')}</b>
		<br />
		<?php foreach ($deps as $dependency): ?>
		<?php $link = tpl_tasklink($dependency, null, true);
			  if (!$link) continue;
		?>
		{!$link}
		<?php if ($user->can_edit_task($task)): ?>
		<span class="DoNotPrint"> -
		  <a class="removedeplink"
			 href="{$_SERVER['SCRIPT_NAME']}?do=details&amp;action=removedep&amp;depend_id={$dependency['depend_id']}&amp;task_id={$task['task_id']}">
			 {L('remove')}</a>
		</span>
		<?php endif; ?>
		<br />
		<?php endforeach; ?>

		<br class="DoNotPrint" />

		<?php if ( (count($deps) || count($blocks)) && (!Flyspray::function_disabled('shell_exec') || array_get($conf['general'], 'dot_public'))): ?>
		<a class="DoNotPrint" href="{CreateURL(array('depends', 'task' . $task['task_id']))}">{L('depgraph')}</a>
		<br />
		<br />
		<?php endif; ?>

		<?php if ($user->can_edit_task($task)): ?>
		<form action="{CreateUrl(array('details', 'task' . $task['task_id']))}" method="post">
		  <div>
			 <input type="hidden" name="action" value="newdep" />
			 <input type="hidden" name="task_id" value="{$task['task_id']}" />
			 <input class="text" type="text" value="" name="dep_task_id" size="5" maxlength="10" />
			 <button type="submit" name="submit">{L('addnew')}</button>
		  </div>
		</form>
		<?php endif; ?>
	 </div>

	 <div id="taskblocks">
        <?php if ($blocks): ?>
		<b>{L('taskblocks')}</b>
		<br />
        <?php endif; ?>
		<?php foreach ($blocks as $block): ?>
		<?php $link = tpl_tasklink($block, null, true);
				if(!$link) continue;
		?>
		{!$link}
		<br />
		<?php endforeach; ?>
	 </div>
  </div>

  <?php if ($task['is_closed']): ?>
  <div id="taskclosed">
      {L('closedby')}&nbsp;&nbsp;{!tpl_userlink($task['closed_by'])}<br />
      {formatDate($task['date_closed'], true)}<br />
      <strong>{L('reasonforclosing')}</strong> &nbsp;{$task['resolution_name']}<br />
      <?php if ($task['closure_comment']): ?>
      <strong>{L('closurecomment')}</strong> &nbsp;{!$this->text->render($task['closure_comment'], true)}
      <?php endif; ?>
  </div>
  <?php endif; ?>

  <div id="actionbuttons">
	 <?php if ($task['is_closed']): ?>

	 <?php if ($user->can_close_task($task)): ?>
	 <a class="button" href="{$_SERVER['SCRIPT_NAME']}?do=details&amp;action=reopen&amp;task_id={$task['task_id']}">
		{L('reopenthistask')}</a>
	 <?php elseif (!$user->isAnon() && !Flyspray::adminRequestCheck(2, $task['task_id'])): ?>
	 <a href="#close" id="reqclose" class="button" onclick="showhidestuff('closeform');">
		{L('reopenrequest')}</a>
	 <div id="closeform" class="popup hide">
		<form name="form3" action="{CreateUrl(array('details', 'task' . $task['task_id']))}" method="post" id="formclosetask">
		  <div>
			 <input type="hidden" name="action" value="requestreopen" />
			 <input type="hidden" name="task_id" value="{$task['task_id']}" />
			 <label for="reason">{L('reasonforreq')}</label>
			 <textarea id="reason" name="reason_given"></textarea><br />
			 <button type="submit">{L('submitreq')}</button>
		  </div>
		</form>
	 </div>
	 <?php endif; ?>

	 <?php else: ?>

	 <?php if ($user->can_close_task($task) && !$d_open && !$task['close_after']): ?>
	 <a href="{CreateUrl(array('details', 'task' . $task['task_id']), array('showclose' => !Get::val('showclose')))}" id="closetask" class="button" accesskey="y" onclick="showhidestuff('closeform');return false;">
		{L('closetask')}</a>
     <div id="closeform" class="<?php if (Req::val('action') != 'close' && !Get::val('showclose')): ?>hide <?php endif; ?>popup">
		<form action="{CreateUrl(array('details', 'task' . $task['task_id']))}" method="post" id="formclosetask">
		  <div>
			 <input type="hidden" name="action" value="close" />
			 <input type="hidden" name="task_id" value="{$task['task_id']}" />
			 <select class="adminlist" name="resolution_reason" onmouseup="Event.stop(event);">
				<option value="0">{L('selectareason')}</option>
				{!tpl_options($proj->get_list(array('list_id' => $fs->prefs['resolution_list'])), Post::val('resolution_reason'))}
			 </select>
			 <button type="submit">{L('closetask')}</button>
			 <label class="default text" for="closure_comment">{L('closurecomment')}</label>
			 <textarea class="text" id="closure_comment" name="closure_comment" rows="3" cols="25">{Post::val('closure_comment')}</textarea>
             <label>{L('inactivityclose')} <input type="text" class="text" size="5" name="close_after_num" /></label>
             <select class="adminlist" name="close_after_type" onmouseup="Event.stop(event);">
               {!tpl_options(array(3600 => L('hours'), 86400 => L('days'), 604800 => L('weeks')), Post::val('close_after_type'))}
             </select>
			 <?php if ($task['percent_complete'] != '100'): ?>
             <label>{!tpl_checkbox('mark100', Post::val('mark100', !(Req::val('action') == 'close')))}&nbsp;&nbsp;{L('mark100')}</label>
             <?php endif; ?>
		  </div>
		</form>
	 </div>
	 <?php elseif (!$d_open && !$user->isAnon() && !$task['close_after'] && !Flyspray::AdminRequestCheck(1, $task['task_id'])): ?>
	 <a href="#close" id="reqclose" class="button" onclick="showhidestuff('closeform');">
		{L('requestclose')}</a>
	 <div id="closeform" class="popup hide">
		<form name="form3" action="{CreateUrl(array('details', 'task' . $task['task_id']))}" method="post" id="formclosetask">
		  <div>
			 <input type="hidden" name="action" value="requestclose" />
			 <input type="hidden" name="task_id" value="{$task['task_id']}" />
			 <label for="reason">{L('reasonforreq')}</label>
			 <textarea id="reason" name="reason_given"></textarea><br />
			 <button type="submit">{L('submitreq')}</button>
		  </div>
		</form>
	 </div>
	 <?php endif; ?>
     <?php if ($user->can_close_task($task) && $task['close_after']): ?>
     <a href="{CreateUrl(array('details', 'task' . $task['task_id']), array('action' => 'stop_close'))}" class="button" accesskey="y">
		{L('stopautoclose')} ({formatDate(max($lastcommentdate, $task['last_edited_time']) + $task['close_after'])})</a>
     <?php endif; ?>

	 <?php if ($user->can_take_ownership($task)): ?>
	 <a id="own" class="button"
		href="{$_SERVER['SCRIPT_NAME']}?do=details&amp;task_id={$task['task_id']}&amp;action=takeownership&amp;ids={$task['task_id']}">
		{L('assigntome')}</a>
	 <?php endif; ?>

	 <?php if ($user->can_add_to_assignees($task) && !empty($task['assigned_to'])): ?>
	 <a id="own_add" class="button"
		href="{$_SERVER['SCRIPT_NAME']}?do=details&amp;task_id={$task['task_id']}&amp;action=addtoassignees&amp;ids={$task['task_id']}">
		{L('addmetoassignees')}</a>
	 <?php endif; ?>

	 <?php if ($user->can_edit_task($task) || $user->can_correct_task($task)): ?>
	 <a id="edittask" class="button" href="{CreateURL(array('details', 'task' . $task['task_id']), array('edit' => 1))}">
		{L('edittask')}</a>
	 <?php endif; ?>

	 <?php endif; ?>
	 <?php if (count($penreqs)): ?>
     <div class="pendingreq"><strong>{formatDate($penreqs[0]['time_submitted'])}: {L('request'.$penreqs[0]['request_type'])}</strong>
     <?php if ($penreqs[0]['reason_given']): ?>
     {L('reasonforreq')}: {$penreqs[0]['reason_given']}
     <?php endif; ?>
     </div>
     <?php endif; ?>
  </div>
</div>
