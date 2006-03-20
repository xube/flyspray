<h3>{L('editcomment')}</h3>

<form action="index.php" method="post">
  <div class="admin">
    <p>{L('commentby')} {$comment['real_name']} - {formatDate($comment['date_added'], true)}</p>
    <textarea cols="72" rows="10" name="comment_text">{$comment['comment_text']}</textarea>
    <p class="buttons">
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="editcomment" />
      <input type="hidden" name="task_id" value="{$comment['task_id']}" />
      <input type="hidden" name="comment_id" value="{$comment['comment_id']}" />
      <input type="hidden" name="previous_text" value="{$comment['comment_text']}" />
      <button type="submit">{L('saveeditedcomment')}</button>
    </p>
  </div>
</form>
