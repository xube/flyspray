<div id="comments" class="tab">
  <?php foreach($comments as $row): ?>
  <em>
    <a name="comment{$row['comment_id']}" id="comment{$row['comment_id']}"
      href="{CreateURL('details', $task_details['task_id'])}#comment{$row['comment_id']}">
      <img src="{$baseurl}themes/{$proj->prefs['theme_style']}/menu/comment.png"
        title="{L('commentlink')}" alt="" />
    </a>
    {L('commentby')} {!tpl_userlink($row['user_id'])} -
    {formatDate($row['date_added'], true)}
  </em>

  <span class="DoNotPrint">
    <?php if ($user->perms['edit_comments'] || ($user->perms['edit_own_comments'] && $row['user_id'] == $user->id)): ?>
    &mdash;
    <a href="{$baseurl}?do=editcomment&amp;task_id={Get::val('id')}&amp;id={$row['comment_id']}">
      {L('edit')}</a>
    <?php endif; ?>

    <?php if ($user->perms['delete_comments']): ?>
    &mdash;
    <a href="{$baseurl}?do=modify&amp;action=deletecomment&amp;task_id={Get::val('id')}&amp;comment_id={$row['comment_id']}"
      onclick="return confirm('{L('confirmdeletecomment')}');">
      {L('delete')}</a>
    <?php endif ?>
  </span>
  <div class="comment">
  <?php if(isset($comment_changes[$row['date_added']])): ?>
  <ul class="comment_changes">
  <?php foreach($comment_changes[$row['date_added']] as $change): ?>
    <li>{!event_description($change)}</li>
  <?php endforeach; ?>
  </ul>
  <?php endif; ?>
  <div class="commenttext">{!tpl_formatText($row['comment_text'], false, 'comm', $row['comment_id'], $row['content'])}</div></div>

  <?php $attachments = $proj->listAttachments($row['comment_id']);
        $this->display('common.attachments.tpl', 'attachments', $attachments); ?>

  <?php endforeach; ?>

  <?php if ($user->perms['add_comments'] && (!$task_details['is_closed'] || $proj->prefs['comment_closed'])): ?>
  <fieldset><legend>{L('addcomment')}</legend>
  <form enctype="multipart/form-data" action="{$baseurl}" method="post">
    <div>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="addcomment" />
      <input type="hidden" name="task_id" value="{$task_details['task_id']}" />
      <?php if ($user->perms['create_attachments']): ?>
      <div id="uploadfilebox">
        <span style="display: none;"><?php // this span is shown/copied in javascript when adding files ?>
          <input tabindex="5" class="file" type="file" size="55" name="userfile[]" />
            <a href="javascript://" tabindex="6" onclick="removeUploadField(this);">{L('remove')}</a><br />
        </span>    
      </div>
      <button id="uploadfilebox_attachafile" tabindex="7" type="button" onclick="addUploadFields()">
        {L('uploadafile')}
      </button>
      <button id="uploadfilebox_attachanotherfile" tabindex="7" style="display: none" type="button" onclick="addUploadFields()">
         {L('attachanotherfile')}
      </button>
      <?php endif; ?>
      <textarea accesskey="r" tabindex="8" id="comment_text" name="comment_text" cols="72" rows="10"></textarea>


      <button tabindex="9" type="submit">{L('addcomment')}</button>
      <?php if (!$watched): ?>
      {!tpl_checkbox('notifyme', true, 'notifyme')} <label class="left" for="notifyme">{L('notifyme')}</label>
      <?php endif; ?>
    </div>
  </form>
  </fieldset>
  <?php endif; ?>
</div>
