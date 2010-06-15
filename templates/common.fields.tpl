<form action="{$this->url(array($do, 'proj' . $proj->id, 'fields'))}" method="post">
  <table class="list" id="listTable">
   <thead>
     <tr>
       <th>{L('name')}</th>
       <th>{L('type')}</th>
       <th>{L('list')}</th>
       <th>{L('defaultvalue')}</th>
       <th title="{L('forcedefaulttip')}">{L('forcedefault')}</th>
       <th>{L('required')}</th>
       <th>{L('delete')}</th>
     </tr>
   </thead>
   <tbody>
    <?php foreach ($proj->fields as $field): ?>
    <?php if ($proj->id && $field->prefs['project_id'] != $proj->id) continue; ?>
    <tr>
      <td class="first">
        <input type="hidden" name="id[]" value="{$field->id}" />
        <input class="text" type="text" size="15" maxlength="40" name="field_name[{$field->id}]"
          value="{$field->prefs['field_name']}" />
      </td>
      <td>
        <select name="field_type[{$field->id}]">
          {!tpl_options(array(FIELD_LIST => L('list'), FIELD_DATE => L('date'), FIELD_TEXT => L('text'), FIELD_USER => L('user')),
                        $field->prefs['field_type'])}
        </select>
      </td>
      <td>
        <?php if ($field->prefs['field_type'] == FIELD_LIST): ?>
        <select name="list_id[{$field->id}]">
          {!tpl_options($lists, $field->prefs['list_id'], false, null, null, 'project_id')}
        </select>
        <?php if ($field->prefs['list_type'] == LIST_VERSION): ?>
        <br />&#8627;<select name="version_tense[{$field->id}]" style="width:8em;">
          {!tpl_options(array(0 => L('any'), 1 => L('past'), 4 => L('pastpresent'),
                              2 => L('present'), 5 => L('presentfuture'), 3 => L('future'), 6 => L('futurepast')),
                              $field->prefs['version_tense'])}
        </select>
        <?php endif; ?>
        <?php endif; ?>
      </td>
      <td>
        {!$field->edit(USE_DEFAULT)}
      </td>
      <td>
        {!tpl_checkbox('force_default[' . $field->id . ']', $field->prefs['force_default'])}
      </td>
      <td>
        {!tpl_checkbox('value_required[' . $field->id . ']', $field->prefs['value_required'])}
      </td>
      <td title="{L('deletetip')}">
        <input type="checkbox" name="delete[{$field->id}]" value="1" />
      </td>
    </tr>
    <?php endforeach; ?>
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
    </tbody>
  </table>
</form>
<hr />
<form action="{$this->url(array($do, 'proj' . $proj->id, 'fields'))}" method="post">
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
          {!tpl_options(array(FIELD_LIST => L('list'), FIELD_DATE => L('date'), FIELD_TEXT => L('text'),  FIELD_USER => L('user')),
                        Post::val('field_type'))}
        </select>
      </td>
      <td>
        <select name="list_id">
          {!tpl_options($lists, Post::val('list_id', 0), false, null, null, 'project_id')}
        </select>
      </td>
      <td class="buttons">
        <input type="hidden" name="project" value="{$proj->id}" />
        <button type="submit">{L('addnew')}</button>
      </td>
    </tr>
  </table>
</form>