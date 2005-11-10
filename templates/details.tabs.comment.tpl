<div id="comments" class="tab">
  <?php foreach($comments as $row): ?>
  <em>
    <a name="comment{$row['comment_id']}" id="comment{$row['comment_id']}"
      href="{$fs->CreateURL('details', $task_details['task_id'])}#comment{$row['comment_id']}">
      <img src="{$baseurl}themes/{$proj->prefs['theme_style']}/menu/comment.png"
        title="{$details_text['commentlink']}" alt="" />
    </a>
    {$details_text['commentby']} {!tpl_userlink($row['user_id'])} -
    {$fs->formatDate($row['date_added'], true)}
  </em>

  <span class="DoNotPrint">
    <?php if ($user->perms['edit_comments']): ?>
    &mdash;
    <a href="{$baseurl}?do=editcomment&amp;task_id={Get::val('id')}&amp;id={$row['comment_id']}">
      {$details_text['edit']}</a>
    <?php endif; ?>

    <?php if ($user->perms['delete_comments']): ?>
    &mdash;
    <a href="{$baseurl}?do=modify&amp;action=deletecomment&amp;task_id={Get::val('id')}&amp;comment_id={$row['comment_id']}"
      onclick="return confirm('{$details_text['confirmdeletecomment']}');">
      {$details_text['delete']}</a>
    <?php endif ?>
  </span>
  <p class="comment">{!tpl_formatText($row['comment_text'])}</p>

  <?php
  $attachments = $proj->listAttachments($row['comment_id']);
  if ($user->perms['view_attachments'] || $proj->prefs['others_view']):
  foreach ($attachments as $attachment):
  ?>
  <span class="attachments">
    <a href="{$baseurl}?getfile={$attachment['attachment_id']}" title="{$attachment['file_type']}">
      <?php
      // Let's strip the mimetype to get the icon image name
      list($main) = explode('/', $attachment['file_type']);
      $imgpath = "{$baseurl}themes/{$proj->prefs['theme_style']}/mime/";
      if (file_exists($imgpath.$attachment['file_type'].".png")):
      ?>
      <img src="{$imgpath}{$attachment['file_type']}.png" alt="({$attachment['file_type']})" title="{$attachment['file_type']}" />
      <?php else: ?>
      <img src="{$imgpath}{$main}.png" alt="" title="{$attachment['file_type']}" />
      <?php endif; ?>
      &nbsp;&nbsp;{$attachment['orig_name']}</a>

    <?php if ($user->perms['delete_attachments']): ?>
    &mdash;
    <a href="{$baseurl}?do=modify&amp;action=deleteattachment&amp;id={$attachment['attachment_id']}"
      onclick="return confirm('{$details_text['confirmdeleteattach']}');">
      {$details_text['delete']}</a>
    <?php endif; ?>
  </span>
  <?php endforeach; ?>
  <br />
  <?php elseif (count($attachments)): ?>
  <span class="attachments">{$details_text['attachnoperms']}</span>
  <br />
  <?php endif; ?>
  <?php endforeach; ?>

  <?php if ($user->perms['add_comments'] && !$task_details['is_closed']): ?>
  <form enctype="multipart/form-data" action="{$baseurl}" method="post">
    <div class="admin">
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="addcomment" />
      <input type="hidden" name="task_id" value="{Get::val('id')}" />
      {$details_text['addcomment']}
      <textarea id="comment_text" name="comment_text" cols="72" rows="10"></textarea>

      <?php if ($user->perms['create_attachments']): ?>
      <div id="uploadfilebox">
        {$details_text['uploadafile']}
        <input type="file" size="55" name="userfile[]" /><br />
      </div>
      <input class="adminbutton" type="button" onclick="addUploadFields()"
        value="{$details_text['selectmorefiles']}" />
      <?php endif; ?>

      <input class="adminbutton" type="submit" value="{$details_text['addcomment']}" />
      <?php if (!$watched): ?>
      {!tpl_checkbox('notifyme')} {$newtask_text['notifyme']}
      <?php endif; ?>
    </div>
  </form>
  <?php endif; ?>
</div>
