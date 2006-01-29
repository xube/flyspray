<div class="redirectmessage">
  <p><em>{$language['relatedproject']}</em></p>
  <form action="index.php" method="post">
    <input type="hidden" name="do" value="modify">
    <input type="hidden" name="action" value="add_related">
    <input type="hidden" name="this_task" value="{Post::val('this_task')}">
    <input type="hidden" name="related_task" value="{Post::val('related_task')}">
    <input type="hidden" name="allprojects" value="1">
    <button type="submit">{$language['addanyway']}</button>
  </form>
  <form action="index.php" method="get">
    <input type="hidden" name="do" value="details">
    <input type="hidden" name="id" value="{Post::val('this_task')}">
    <input type="hidden" name="area" value="related">
    <button type="submit">{$language['cancel']}</button>
  </form>
</div>

