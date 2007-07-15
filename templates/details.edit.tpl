<div id="taskdetails">
  <form action="{CreateUrl(array('details', 'task' . $task['task_id']))}" id="taskeditform" enctype="multipart/form-data" method="post">
	 <div>
		<h2 class="summary task colorfield{Post::val('f' . $fs->prefs['color_field'], $task['field' . $fs->prefs['color_field']])}">
		  <a href="{CreateUrl(array('details', 'task' . $task['task_id']))}">{$task['project_prefix']}#{$task['prefix_id']}</a> -
		  <input class="text task colorfield{Post::val('f' . $fs->prefs['color_field'], $task['field' . $fs->prefs['color_field']])}" type="text"
			name="item_summary" size="80" maxlength="100"
			value="{Post::val('item_summary', $task['item_summary'])}" />
		</h2>
		<input type="hidden" name="action" value="edit_task" />
        <input type="hidden" name="edit" value="1" />
		<input type="hidden" name="task_id" value="{$task['task_id']}" />
		<input type="hidden" name="edit_start_time" value="{Post::val('edit_start_time', time())}" />

		<div id="fineprint">
		  {L('attachedtoproject')}:
		  <select name="project_id">
			{!tpl_options($fs->projects, Post::val('project_id', $proj->id))}
		  </select>
		  <br />
		  {L('openedby')} {!tpl_userlink($task['opened_by'])}
		  - {!formatDate($task['date_opened'], true)}
		  <?php if ($task['last_edited_by']): ?>
		  <br />
		  {L('editedby')}  {!tpl_userlink($task['last_edited_by'])}
		  - {formatDate($task['last_edited_time'], true)}
		  <?php endif; ?>
		</div>

        <table><tr><td id="taskfieldscell"><?php // small layout table ?>

		<div id="taskfields">
		  <table class="taskdetails">
            <?php foreach ($proj->fields as $field): ?>
            <tr>
              <th id="f{$field->id}">{$field->prefs['field_name']}</th>
              <td headers="f{$field->id}">
                {!$field->edit(!USE_DEFAULT, LOCK_FIELD, $task)}
              </td>
            </tr>
            <?php endforeach; ?>
			<tr>
			 <td><label for="assigned_to">{L('assignedto')}</label></td>
			 <td>
                <?php if ($user->perms('edit_assignments')): ?>

				<input type="hidden" name="old_assigned" value="{$old_assigned}" />
                <?php $this->display('common.multiuserselect.tpl'); ?>
                <?php else: ?>
                    <?php if (empty($assigned_users)): ?>
                     {L('noone')}
                     <?php else:
                     foreach ($assigned_users as $userid):
                     ?>
                     {!tpl_userlink($userid)}<br />
                     <?php endforeach;
                     endif; ?>
                <?php endif; ?>
			 </td>
			</tr>
			<tr>
			 <td><label for="percent">{L('percentcomplete')}</label></td>
			 <td>
				<select id="percent" name="percent_complete">
				 <?php $arr = array(); for ($i = 0; $i<=100; $i+=10) $arr[$i] = $i.'%'; ?>
				 {!tpl_options($arr, Post::val('percent_complete', $task['percent_complete']))}
				</select>
			 </td>
			</tr>
            <?php if ($user->can_change_private($task)): ?>
            <tr>
              <td><label for="private">{L('private')}</label></td>
              <td>
                {!tpl_checkbox('mark_private', Post::val('mark_private', $task['mark_private']), 'private')}
              </td>
            </tr>
            <?php endif; ?>
		  </table>
		</div>

        </td><td style="width:100%">

		<div id="taskdetailsfull">
          <h3 class="taskdesc">{L('details')}</h3>
        <?php $attachments = $proj->listTaskAttachments($task['task_id']);
          $this->display('common.editattachments.tpl', 'attachments', $attachments); ?>

          <?php if ($user->perms('create_attachments')): ?>
          <div id="uploadfilebox">
            <span style="display: none"><?php // this span is shown/copied in javascript when adding files ?>
              <input tabindex="5" class="file" type="file" size="55" name="usertaskfile[]" />
                <a href="javascript://" tabindex="6" onclick="removeUploadField(this);">{L('remove')}</a><br />
            </span>
            <noscript>
                <span>
                  <input tabindex="5" class="file" type="file" size="55" name="usertaskfile[]" />
                    <a href="javascript://" tabindex="6" onclick="removeUploadField(this);">{L('remove')}</a><br />
                </span>
            </noscript>
          </div>
          <button id="uploadfilebox_attachafile" tabindex="7" type="button" onclick="addUploadFields()">
            {L('uploadafile')} ({L('max')} {$fs->max_file_size} {L('MiB')})
          </button>
          <button id="uploadfilebox_attachanotherfile" tabindex="7" style="display: none" type="button" onclick="addUploadFields()">
             {L('attachanotherfile')} ({L('max')} {$fs->max_file_size} {L('MiB')})
          </button>
          <?php endif; ?>
          <?php if (defined('FLYSPRAY_HAS_PREVIEW')): ?>
          <div class="hide preview" id="preview"></div>
          <?php endif; ?>
          {!TextFormatter::textarea('detailed_desc', 15, 80, array('id' => 'details'), Post::val('detailed_desc', $task['detailed_desc']))}
          <br />
          <?php if ($user->perms('add_comments') && (!$task['is_closed'] || $proj->prefs['comment_closed'])): ?>
              <button type="button" onclick="showstuff('edit_add_comment');this.style.display='none';">{L('addcomment')}</button>
              <div id="edit_add_comment" class="hide">
              <label for="comment_text">{L('comment')}</label>

              <?php if ($user->perms('create_attachments')): ?>
              <div id="uploadfilebox_c">
                <span style="display: none"><?php // this span is shown/copied in javascript when adding files ?>
                  <input tabindex="5" class="file" type="file" size="55" name="userfile[]" />
                    <a href="javascript://" tabindex="6" onclick="removeUploadField(this, 'uploadfilebox_c');">{L('remove')}</a><br />
                </span>
              </div>
              <button id="uploadfilebox_c_attachafile" tabindex="7" type="button" onclick="addUploadFields('uploadfilebox_c')">
                {L('uploadafile')} ({L('max')} {$fs->max_file_size} {L('MiB')})
              </button>
              <button id="uploadfilebox_c_attachanotherfile" tabindex="7" style="display: none" type="button" onclick="addUploadFields('uploadfilebox_c')">
                 {L('attachanotherfile')} ({L('max')} {$fs->max_file_size} {L('MiB')})
              </button>
              <?php endif; ?>

              <textarea tabindex="8" id="comment_text" name="comment_text" cols="50" rows="10"></textarea>
              </div>
          <?php endif; ?>
		  <p class="buttons">
              <button type="submit" accesskey="s" onclick="return checkok('{$baseurl}javascript/callbacks/checksave.php?time={time()}&amp;taskid={$task['task_id']}', '{#L('alreadyedited')}', 'taskeditform')">{L('savedetails')}</button>
              <?php if (defined('FLYSPRAY_HAS_PREVIEW')): ?>
              <button tabindex="9" type="button" onclick="showPreview('details', 'preview')">{L('preview')}</button>
              <?php endif; ?>
              <button type="reset">{L('reset')}</button>
          </p>
		</div>

        </td></tr></table>

	 </div>
     <div class="clear"></div>
  </form>
</div>
