<?php if(isset($update_error)): ?>
<div id="updatemsg">
	<p class="bad"> {L('updatewrong')}</p>
	<a href="?hideupdatemsg=yep">{L('hidemessage')}</a>
</div>
<?php endif; ?>

<?php if (isset($updatemsg)): ?>
<div id="updatemsg">
    <a href="http://flyspray.org/">{L('updatefs')}</a> {L('currentversion')}
    <p class="bad">{$fs->version}</p> {L('latestversion')} <p class="good">{$_SESSION['latest_version']}</p>.
    <a href="?hideupdatemsg=yep">{L('hidemessage')}</a>
</div>
<?php endif; ?>


<form action="{$_SERVER['SCRIPT_NAME']}" id="massops" method="post">
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
					{!tpl_list_heading($col, (isset($proj->fields[$col]) ? $proj->fields[$col]->prefs['field_name'] : L($col)))}
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php
					$i = 0;
					foreach ($tasks as $task):
						$class = 'table-row-' . ($i % 2);
				?>
				<tr id="task{!$task['task_id']}" class="<?php echo $class; ?> severity{$task['task_severity']}">
					<td class="caret">
					</td>
					<?php if (!$user->isAnon()): ?>
					<td class="ttcolumn">
						<input class="ticktask" type="checkbox" name="ids[]" value="{$task['task_id']}" />
					</td>
					<?php endif; ?>
					<?php foreach ($visible as $col): ?>
					{!tpl_draw_cell($task, $col)}
					<?php endforeach; ?>
				</tr>
			<?php $i++; endforeach; ?>
			</tbody>
      </table>

	<table id="pagenumbers">
		<tr>
		  <?php if ($total): ?>
		  <td id="taskrange">
		  <?php if (!$perpage) $perpage = $total; ?>
			{!sprintf(L('taskrange'), $offset + 1,
			  ($offset + $perpage > $total ? $total : $offset + $perpage), $total)}
		  </td>
		  <td id="numbers">
			{!pagenums($pagenum, $perpage, $total)}
		  </td>
		  <?php else: ?>
		  <td id="taskrange"><strong>{L('noresults')}</strong></td>
		  <?php endif; ?>
		</tr>
		<?php if (!$user->isAnon() && $total): ?>
		<tr id="massopsactions">
		  <td>
			<select name="action">
			  <option value="add_notification">{L('watchtasks')}</option>
			  <option value="remove_notification">{L('stopwatchingtasks')}</option>
			  <option value="takeownership">{L('assigntaskstome')}</option>
			  <option value="mass_edit">{L('massedit')}</option>
			</select>
			<input type="hidden" name="user_id" value="{$user->id}" />
			<button type="submit">{L('takeaction')}</button>
		  </td>
		  <td id="export">
			<a href="{$baseurl}?{tpl_query_from_array(array_merge($_GET, array('do' => 'export')))}">
			  <img alt="{L('csvexport')}" title="{L('csvexport')}" src="{$this->get_image('csvexport')}" width="16" height="16" /> {L('csvexport')}
			</a>
		  </td>
		</tr>
		<?php endif ?>
	</table>
</form>