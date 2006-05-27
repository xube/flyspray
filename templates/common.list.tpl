<p>{L('listnote')}</p>
<form action="{$baseurl}" method="post">
  <table class="list">
   <thead>
     <tr>
       <th>{L('name')}</th>
       <th>{L('order')}</th>
       <th>{L('show')}</th>
       <?php if ($list_type == 'version'): ?><th>{L('tense')}</th><?php endif; ?>
       <th>{L('delete')}</th>
     </tr>
   </thead>
    <?php
    $countlines = -1;
    foreach ($rows as $row):
    $countlines++; ?>
    <tr>
      <td>
        <input type="hidden" name="id[]" value="{$row[$list_type.'_id']}" />
        <input id="listname{$countlines}" class="text" type="text" size="15" maxlength="40" name="list_name[]"
          value="{$row[$list_type.'_name']}" />
      </td>
      <td title="The order these items will appear in the list">
        <input id="listposition{$countlines}" class="text" type="text" size="3" maxlength="3" name="list_position[]" value="{$row['list_position']}" />
      </td>
      <td title="Show this item in the list">
        {!tpl_checkbox('show_in_list['.$countlines.']', $row['show_in_list'], 'showinlist'.$countlines)}
      </td>
      <?php if ($list_type == 'version'): ?>
      <td title="{L('listtensetip')}">
        <select id="tense{$countlines}" name="{$list_type}_tense[]">
          {!tpl_options(array(1=>L('past'), 2=>L('present'), 3=>L('future')), $row[$list_type.'_tense'])}
        </select>
      </td>
      <?php endif; ?>
      <td title="Delete this item from the list">
        <input id="delete{$row[$list_type.'_id']}" type="checkbox"
        <?php if ($row['used_in_tasks'] || ($list_type == 'status' && $row[$list_type.'_id'] < 7) || ($list_type == 'resolution' && $row[$list_type.'_id'] == 6)): ?>
        disabled="disabled"
        <?php endif; ?>
        name="delete[{$row[$list_type.'_id']}]" value="1" />
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if(count($rows)): ?>
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
        <button type="submit">{L('update')}</button>
      </td>
    </tr>
    <?php endif; ?>
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
        <input id="listnamenew" class="text" type="text" size="15" maxlength="40" name="list_name" />
      </td>
      <td>
        <input id="listpositionnew" class="text" type="text" size="3" maxlength="3" name="list_position" />
      </td>
      <td>
        <input id="showinlistnew" type="checkbox" name="show_in_list" checked="checked" disabled="disabled" />
      </td>
      <?php if ($list_type == 'version'): ?>
      <td title="{L('listtensetip')}">
        <select id="tensenew" name="{$list_type}_tense">
          {!tpl_options(array(1=>L('past'), 2=>L('present'), 3=>L('future')), 2)}
        </select>
      </td>
      <?php endif; ?>
      <td class="buttons">
        <button type="submit">{L('addnew')}</button>
      </td>
    </tr>
  </table>
</form>
