<form action="{CreateURL($do, 'fields', $proj->id)}" method="post">
  <table class="list" id="listTable">
   <thead>
     <tr>
       <th>{L('name')}</th>
       <th>{L('type')}</th>
       <th>{L('list')}</th>
       <th>{L('versiontense')}</th>
       <th>{L('defaultvalue')}</th>
       <th title="{L('forcedefaulttip')}">{L('forcedefault')}</th>
       <th>{L('delete')}</th>
     </tr>
   </thead>
   <tbody>
    <?php foreach ($proj->fields as $field): ?>
    <?php if ($proj->id && $field['project_id'] != $proj->id) continue; ?>
    <tr>
      <td class="first">
        <input type="hidden" name="id[]" value="{$field['field_id']}" />
        <input class="text" type="text" size="15" maxlength="40" name="field_name[{$field['field_id']}]"
          value="{$field['field_name']}" />
      </td>
      <td>
        <select name="field_type[{$field['field_id']}]">
          {!tpl_options(array(FIELD_LIST => L('list'), FIELD_DATE => L('date')), $field['field_type'])}
        </select>
      </td>
      <td>
        <?php if ($field['field_type'] == FIELD_LIST): ?>
        <select name="list_id[{$field['field_id']}]">
          {!tpl_options($lists, $field['list_id'], false, null, null, 'project_id')}
        </select>
        <?php endif; ?>
      </td>
      <td>
        <?php if ($field['list_type'] == LIST_VERSION): ?>
        <select name="version_tense[{$field['field_id']}]">
          {!tpl_options(array(0 => L('any'), 1 => L('past'), 2 => L('present'), 3 => L('future')), $field['version_tense'])}
        </select>
        <?php endif; ?>
      </td>
      <td>
        <?php if ($field['list_id'] && $field['field_type'] == FIELD_LIST): ?>
        <select name="default_value[{$field['field_id']}]">
          {!tpl_options($proj->get_list($field['list_id'], $field['list_type']), $field['default_value'])}
        </select>
        <?php elseif ($field['field_type'] == FIELD_DATE): ?>
          {!tpl_datepicker('default_value[' . $field['field_id'] . ']', '', $field['default_value'])}
        <?php endif; ?>
      </td>
      <td>
          {!tpl_checkbox('force_default[' . $field['field_id'] . ']', $field['force_default'])}
      </td>
      <td title="{L('deletetip')}">
        <input type="checkbox" name="delete[{$field['field_id']}]" value="1" />
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <?php if (count($proj->fields)): ?>
    <tr>
      <td colspan="5"></td>
      <td class="buttons">
        <input type="hidden" name="action" value="update_fields" />
        <input type="hidden" name="project" value="{$proj->id}" />
        <button type="submit">{L('update')}</button>
      </td>
    </tr>
    <?php endif; ?>
  </table>
</form>
<hr />
<form action="{CreateURL($do, 'fields', $proj->id)}" method="post">
  <table class="list">
    <tr>
      <td>
        <input type="hidden" name="action" value="add_field" />
        <input type="hidden" name="project_id" value="{$proj->id}" />
        <input type="hidden" name="area" value="fields" />
        <input type="hidden" name="do" value="{$do}" />
        <input class="text" type="text" size="15" maxlength="40" value="{Post::val('field_name')}" name="field_name" />
      </td>
      <td>
        <select name="field_type">
          {!tpl_options(array(FIELD_LIST => L('list'), FIELD_DATE => L('date')), $field['field_type'])}
        </select>
      </td>
      <td>
        <select name="list_id">
          {!tpl_options($lists, 0, false, null, null, 'project_id')}
        </select>
      </td>
      <td class="buttons">
        <input type="hidden" name="project" value="{$proj->id}" />
        <button type="submit">{L('addnew')}</button>
      </td>
    </tr>
  </table>
</form>
