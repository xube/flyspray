<fieldset class="box"><legend>{L('notes')}</legend>

<?php if (count($saved_notes)): ?>
<table class="userlist">
<thead><tr><th colspan="3">{L('savednotes')} (<a href="{CreateUrl(array('myprofile', 'notes'))}">{L('addnew')}</a>)</th></tr></thead>
<?php foreach ($saved_notes as $note): ?>
<tr>
  <td>{formatDate($note['last_updated'])}</td>
  <td>
    <a href="{CreateUrl(array('myprofile', 'notes'), array('note_id' => $note['note_id']))}">
      <?php if ($note['message_subject'] == ''): ?>
      {L('nosubject')}
      <?php else: ?>
      {$note['message_subject']}
      <?php endif; ?>
    </a>
  </td>
  <td><a href="{CreateUrl(array('myprofile', 'notes'), array('note_id' => $note['note_id']))}">View</a>
      <a href="{CreateUrl(array('myprofile', 'notes'), array('note_id' => $note['note_id'], 'edit' => 1))}">Edit</a>
      <a href="{CreateUrl(array('myprofile', 'notes'), array('note_id' => $note['note_id'], 'action' => 'deletenote'))}"
         onclick="return confirm('{L('confirmdeletenote')}');">{L('delete')}</a></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<?php if (isset($show_note) && !Get::val('edit')): ?>
<div id="mynote">
<div id="note_subject">
  <h3>
    <?php if ($show_note['message_subject'] == ''): ?>
    {L('nosubject')}
    <?php else: ?>
    {$show_note['message_subject']}
    <?php endif; ?>
  </h3>
  <div class="fade" id="note_updated">{formatDate($show_note['last_updated'], true)}</div>
</div>
{!$this->text->render($show_note['message_body'], false, 'note', $show_note['note_id'], $show_note['content'])}
</div>
<?php else: ?>
<form method="post" action="{CreateUrl(array('myprofile', 'notes'))}">
<div>
  <label for="message_subject">{L('notesubject')}</label>
  <input id="message_subject" size="50" type="text" name="message_subject" class="text" value="{(isset($show_note) ? $show_note['message_subject'] : Post::val('message_subject'))}" />

  {!$this->text->textarea('message_body', 10, 70, array(), (isset($show_note) ? $show_note['message_body'] : Post::val('message_body')))}
  <button type="submit">{(isset($show_note) ? L('updatenote') : L('addnote'))}</button>

  <?php if (isset($show_note)): ?>
  <input type="hidden" name="action" value="updatenote" />
  <input type="hidden" name="note_id" value="{$show_note['note_id']}" />
  <?php else: ?>
  <input type="hidden" name="action" value="addnote" />
  <?php endif; ?>
</div>
</form>
<?php endif; ?>

</fieldset>
