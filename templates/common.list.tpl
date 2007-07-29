<p>{L('listnote')}</p>
<?php if (count($rows)): ?>
<?php endif; ?>
<form action="{$this->url(array($do, 'proj' . $proj->id, 'list'), array('list_id' => Req::val('list_id')))}" method="post">
  <table class="list" id="listTable">
   <thead>
     <tr>
       <th>{L('name')}</th>
       <th>{L('order')}</th>
       <th>{L('show')}</th>
       <?php if ($list_type == LIST_VERSION): ?><th>{L('tense')}</th><?php endif; ?>
       <th>{L('delete')}</th>
     </tr>
   </thead>
   <tbody>
    <?php
    $countlines = -1;
    foreach ($rows as $row):
    $countlines++; ?>
    <tr>
      <td class="first">
        <input type="hidden" name="id[]" value="{$row['list_item_id']}" />
        <img onclick="TableControl.up('listTable');" src="{$this->get_image('up')}" class="hide" alt="{L('moveup')}" width="16" height="16" />
        <img onclick="TableControl.down('listTable');" src="{$this->get_image('down')}" class="hide" alt="{L('moveup')}" width="16" height="16" />
        <input id="listname{$countlines}" class="text" type="text" size="15" maxlength="40" name="list_name[]"
          value="{$row['item_name']}" />
      </td>
      <td title="{L('ordertip')}">
        <input id="listposition{$countlines}" class="text" type="text" size="3" maxlength="3" name="list_position[]" value="{$row['list_position']}" />
      </td>
      <td title="{L('showtip')}">
        {!tpl_checkbox('show_in_list['.$countlines.']', $row['show_in_list'], 'showinlist'.$countlines)}
      </td>
      <?php if ($list_type == LIST_VERSION): ?>
      <td title="{L('listtensetip')}">
        <select id="tense{$countlines}" name="version_tense[]">
          {!tpl_options(array(1=>L('past'), 2=>L('present'), 3=>L('future')), $row['version_tense'])}
        </select>
      </td>
      <?php endif; ?>
      <td title="{L('deletetip')}">
        <input id="delete{$row['list_item_id']}" type="checkbox"
        <?php if ($row['used_in_tasks']): ?>
        disabled="disabled"
        <?php endif; ?>
        name="delete[{$row['list_item_id']}]" value="1" />
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <?php if (count($rows)): ?>
    <tfoot>
    <tr>
      <td colspan="3"></td>
      <td class="buttons">
        <input type="hidden" name="action" value="update_list" />
        <input type="hidden" name="project" value="{$proj->id}" />
        <button type="submit">{L('update')}</button>
      </td>
    </tr>
    </tfoot>
    <?php endif; ?>
  </table>
  <?php if (count($rows)): ?>
  <script type="text/javascript">
        <?php
            echo 'TableControl.create("listTable",{
                controlBox: "controlBox",
                tree: false
            });';
        ?>
  </script>
  <?php endif; ?>
</form>
<hr />
<form action="{$this->url(array($do, 'proj' . $proj->id, 'list'), array('list_id' => Req::val('list_id')))}" method="post">
  <table class="list">
    <tr>
      <td>
        <input type="hidden" name="action" value="add_to_list" />
        <input type="hidden" name="project_id" value="{$proj->id}" />
        <input type="hidden" name="area" value="list" />
        <input type="hidden" name="do" value="{$do}" />
        <input type="hidden" name="list_id" value="{Req::val('list_id')}" />
        <input id="listnamenew" class="text" type="text" size="15" maxlength="40" value="{Post::val('item_name')}" name="item_name" />
      </td>
      <td>
        <input id="listpositionnew" class="text" type="text" size="3" maxlength="3" value="{Post::val('list_position')}" name="list_position" />
      </td>
      <td>
        <input id="showinlistnew" type="checkbox" name="show_in_list" checked="checked" disabled="disabled" />
      </td>
      <?php if ($list_type == LIST_VERSION): ?>
      <td title="{L('listtensetip')}">
        <select id="tensenew" name="version_tense">
          {!tpl_options(array(1=>L('past'), 2=>L('present'), 3=>L('future')), 2)}
        </select>
      </td>
      <?php endif; ?>
      <td class="buttons">
        <input type="hidden" name="project" value="{$proj->id}" />
        <button type="submit">{L('addnew')}</button>
      </td>
    </tr>
  </table>
</form>
