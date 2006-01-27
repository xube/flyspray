<div id="related" class="tab">
  <p><em>{$details_text['thesearerelated']}</em></p>
  <?php
  foreach ($related as $row):
  if ($user->can_edit_task($task_details) && !$task_details['is_closed']):
  ?>
  <div class="modifycomment">
    <form action="{$baseurl}" method="post">
      <p>
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="remove_related" />
        <input type="hidden" name="id" value="{Get::val('id')}" />
        <input type="hidden" name="related_id" value="{$row['related_id']}" />
        <input type="hidden" name="related_task" value="{$row['related_task']}" />
        <button type="submit">{$details_text['remove']}</button>
      </p>
    </form>
  </div>
  <?php endif; ?>
  <p>{!tpl_tasklink($row)}</p>
  <?php endforeach; ?>

  <?php if ($user->can_edit_task($task_details) && !$task_details['is_closed']): ?>
  <form action="{$baseurl}" method="post" id="formaddrelatedtask">
    <div class="admin">
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="add_related" />
      <input type="hidden" name="this_task" value="{Get::val('id')}" />
      <label>{$details_text['addnewrelated']}
        <input name="related_task" type="text" class="text" size="10" maxlength="10" /></label>
      <button type="submit">{$details_text['add']}</button>
    </div>
  </form>
  <?php endif; ?>

  <p><em>{$details_text['otherrelated']}</em></p>
  <?php foreach ($related_to as $row): ?>
  <p>{!tpl_tasklink($row)}</p>
  <?php endforeach; ?>
</div>
