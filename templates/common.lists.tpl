<?php if (count($lists)): ?>
<form action="{CreateURL($do, 'lists', $proj->id)}" method="post">
  <table class="list" id="listTable">
   <thead>
     <tr>
       <th>{L('name')}</th>
       <th>{L('listtype')}</th>
       <th>{L('delete')}</th>
       <th></th>
     </tr>
   </thead>
   <tbody>
    <?php
    $countlines = -1;
    foreach ($lists as $list):
    $countlines++; ?>
    <tr>
      <td class="first">
        <input type="hidden" name="id[]" value="{$list['list_id']}" />
        <input class="text" type="text" size="15" maxlength="40" name="list_name[{$list['list_id']}]"
          value="{$list['list_name']}" />
      </td>
      <td>
        <select name="list_type[{$list['list_id']}]">
          {!tpl_options(array(LIST_BASIC => L('basic'), LIST_VERSION => L('versions'), LIST_CATEGORY => L('category')), $list['list_type'])}
        </select>
      </td>
      <td title="{L('deletetip')}">
        <input type="checkbox" name="delete[{$list['list_id']}]" value="1" />
      </td>
      <td><a href="{CreateURL($do, 'list', $proj->id, array('list_id' => $list['list_id']))}">{L('edit')}</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tr>
      <td colspan="3"></td>
      <td class="buttons">
        <input type="hidden" name="action" value="update_lists" />
        <input type="hidden" name="project" value="{$proj->id}" />
        <button type="submit">{L('update')}</button>
      </td>
    </tr>
  </table>
</form>
<hr />
<?php endif; ?>
<form action="{CreateURL($do, 'lists', $proj->id)}" method="post">
  <table class="list">
    <tr>
      <td>
        <input type="hidden" name="action" value="add_list" />
        <input type="hidden" name="project_id" value="{$proj->id}" />
        <input type="hidden" name="area" value="{Req::val('area')}" />
        <input type="hidden" name="do" value="{$do}" />
        <input id="listnamenew" class="text" type="text" size="15" maxlength="40" value="{Post::val('list_name')}" name="list_name" />
      </td>
      <td>
        <select name="list_type">
          {!tpl_options(array(LIST_BASIC => L('basic'), LIST_VERSION => L('versions'), LIST_CATEGORY => L('category')), Req::num('list_type'))}
        </select>
      </td>
      <td class="buttons">
        <input type="hidden" name="project" value="{$proj->id}" />
        <button type="submit">{L('addnew')}</button>
      </td>
    </tr>
  </table>
</form>
