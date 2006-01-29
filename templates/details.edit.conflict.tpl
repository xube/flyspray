{$language['alreadyedited']}
<br /><br />
<span>
  <form name="form1" action="index.php" method="post">
    <input type="hidden" name="do" value="modify" />
    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="task_id" value="{Post::val('task_id')}" />
    <input type="hidden" name="edit_start_time" value="999999999999" />
    <input type="hidden" name="attached_to_project" value="{Post::val('attached_to_project')}" />
    <input type="hidden" name="task_type" value="{Post::val('task_type')}" />
    <input type="hidden" name="item_summary" value="{Post::val('item_summary')}" />
    <input type="hidden" name="detailed_desc" value="{Post::val('detailed_desc')}" />
    <input type="hidden" name="item_status" value="{Post::val('item_status')}" />
    <input type="hidden" name="assigned_to" value="{Post::val('assigned_to')}" />
    <input type="hidden" name="product_category" value="{Post::val('product_category')}" />
    <input type="hidden" name="closedby_version" value="{Post::val('closedby_version')}" />
    <input type="hidden" name="due_date" value="{Post::val('due_date')}" />
    <input type="hidden" name="operating_system" value="{Post::val('operating_system')}" />
    <input type="hidden" name="task_severity" value="{Post::val('task_severity')}" />
    <input type="hidden" name="task_priority" value="{Post::val('task_priority')}" />
    <input type="hidden" name="percent_complete" value="{Post::val('percent_complete')}" />
    <button type="submit">{$language['saveanyway']}</button>
  </form>
</span>
&nbsp;&nbsp;&nbsp;
<span>
  <form action="index.php" method="get">
    <input type="hidden" name="do" value="details" />
    <input type="hidden" name="id" value="{Post::val('task_id')}" />
    <button type="submit">{$language['cancel']}</button>
  </form>
</span>
