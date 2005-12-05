<p>{$admin_text['listnote']}</p>
<form action="{$baseurl}" method="post">
  <table class="list">
    <?php
    $countlines = -1;
    foreach ($rows as $row):
    $countlines++; ?>
    <tr>
      <td>
        <input type="hidden" name="id[]" value="{$row[$list_type.'_id']}" />
        <label for="listname{$countlines}">{$admin_text['name']}</label>
        <input id="listname{$countlines}" type="text" size="15" maxlength="40" name="list_name[]"
          value="{$row[$list_type.'_name']}" />
      </td>
      <td title="The order these items will appear in the list">
        <label for="listposition{$countlines}">{$admin_text['order']}</label>
        <input id="listposition{$countlines}" type="text" size="3" maxlength="3" name="list_position[]" value="{$row['list_position']}" />
      </td>
      <td title="Show this item in the list">
        <label for="showinlist{$countlines}">{$admin_text['show']}</label>
        {!tpl_checkbox('show_in_list['.$countlines.']', $row['show_in_list'], 'showinlist'.$countlines)}
      </td>
      <?php if ($list_type == 'version'): ?>
      <td title="{$admin_text['listtensetip']}">
        <label for="tense{$countlines}">{$admin_text['tense']}</label>
        <select id="tense{$countlines}" name="{$list_type}_tense[]">
          {!tpl_options(array(1=>$admin_text['past'], 2=>$admin_text['present'], 3=>$admin_text['future']), $row[$list_type.'_tense'])}
        </select>
      </td>
      <?php endif; ?>
      <td title="Delete this item from the list">
        <?php if (!$row['used_in_tasks']): ?>
        <label for="delete{$row[$list_type.'_id']}">{$admin_text['delete']}</label>
        <input id="delete{$row[$list_type.'_id']}" type="checkbox" name="delete[{$row[$list_type.'_id']}]" value="1" />
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <tr>
      <td colspan="3"></td>
      <td class="buttons">
        <input type="hidden" name="do" value="modify" />
        <?php if ($list_type == 'version'): ?>
        <input type="hidden" name="action" value="update_version_list" />
        <?php else: ?>
        <input type="hidden" name="action" value="update_list" />
        <?php endif; ?>
        <input type="hidden" name="list_type" value="{$list_type}" />
        <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
        <input class="adminbutton" type="submit" value="{$admin_text['update']}" />
      </td>
    </tr>
  </table>
</form>
<hr />
<form action="{$baseurl}" method="post">
  <table class="list">
    <tr>
      <td>
        <input type="hidden" name="do" value="modify" />
        <?php if ($list_type == 'version'): ?>
        <input type="hidden" name="action" value="add_to_version_list" />
        <?php else: ?>
        <input type="hidden" name="action" value="add_to_list" />
        <?php endif; ?>
        <input type="hidden" name="list_type" value="{$list_type}" />
        <?php if ($proj->id): ?>
        <input type="hidden" name="project_id" value="{$proj->id}" />
        <?php endif; ?>
        <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
        <label for="listnamenew">{$admin_text['name']}</label>
        <input id="listnamenew" type="text" size="15" maxlength="40" name="list_name" />
      </td>
      <td>
        <label for="listpositionnew">{$admin_text['order']}</label>
        <input id="listpositionnew" type="text" size="3" maxlength="3" name="list_position" />
      </td>
      <td>
        <label for="showinlistnew">{$admin_text['show']}</label>
        <input id="showinlistnew" type="checkbox" name="show_in_list" checked="checked" disabled="disabled" />
      </td>
      <?php if ($list_type == 'version'): ?>
      <td title="{$admin_text['listtensetip']}">
        <label for="tensenew">{$admin_text['tense']}</label>
        <select id="tensenew" name="{$list_type}_tense">
          {!tpl_options(array(1=>$admin_text['past'], 2=>$admin_text['present'], 3=>$admin_text['future']), 2)}
        </select>
      </td>
      <?php endif; ?>
      <td class="buttons">
        <input class="adminbutton" type="submit" value="{$admin_text['addnew']}" />
      </td>
    </tr>
  </table>
</form>
