<fieldset class="admin">
  <legend>{L('categories')}</legend>
  <p>{L('listnote')}</p>
    <form action="{$baseurl}" method="post">
      <table class="list">
         <thead>
         <tr>
           <th>{L('name')}</th>
           <th>{L('owner')}</th>
           <th>{L('show')}</th>
           <th>{L('delete')}</th>
         </tr>
       </thead>
        <?php
        $countlines = -1;
        $categories = $proj->listCategories($proj->id, false, false);
        $root = $categories[0];
        unset($categories[0]);
        
        foreach ($categories as $row):
            $countlines++;
        ?>
        <tr>
          <td>
            <input type="hidden" name="id[]" value="{$row['category_id']}" />
            {!str_repeat('&rarr;', $row['depth'])}
            <input id="categoryname{$countlines}" class="text" type="text" size="15" maxlength="40" name="list_name[]" 
              value="{$row['category_name']}" />
          </td>
          <td title="{L('categoryownertip')}">
            <select id="categoryowner{$countlines}" name="category_owner[]">
              <option value="">{L('selectowner')}</option>
              {!tpl_options($proj->UserList(), $row['category_owner'])}
            </select>
          </td>
          <td title="{L('listshowtip')}">
            {!tpl_checkbox('show_in_list['.$countlines.']', $row['show_in_list'], 'showinlist'.$countlines)}
          </td>
          <td title="{L('listdeletetip')}">
            <input id="delete{$row['category_id']}" type="checkbox"
            <?php if ($row['used_in_tasks']): ?>disabled="disabled"<?php endif; ?>
            name="delete[{$row['category_id']}]" value="1" />
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if($countlines > -1): ?>
        <tr>
          <td colspan="4"></td>
          <td class="buttons">
            <input type="hidden" name="do" value="modify" />
            <input type="hidden" name="action" value="update_category" />
            <input type="hidden" name="list_type" value="category" />
            <input type="hidden" name="project_id" value="{$proj->id}" />
            <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
            <button type="submit">{L('update')}</button>
          </td>
        </tr>
        <?php endif; ?>
      </table>
    </form>

    <hr />

    <!-- Form to add a new category to the list -->
    <form action="{$baseurl}" method="post">
      <table class="list">
        <tr>
          <td>
            <input id="listnamenew" class="text" type="text" size="15" maxlength="40" name="list_name" />
          </td>
          <td title="{L('categoryownertip')}">
            <select id="categoryownernew" name="category_owner">
              <option value="">{L('selectowner')}</option>
              {!tpl_options($proj->UserList())}
            </select>
          </td>
          <td title="{L('categoryparenttip')}">
            <label for="parent_id">Parent</label>
            <select id="parent_id" name="parent_id">
              <option value="{$root['category_id']}">{L('notsubcategory')}</option>
              <?php $cat_opts = array_map(
              create_function('$x', 'return array($x["category_id"], $x["category_name"]);'),
              $categories);
              ?>
              {!tpl_options($cat_opts)}
            </select>
          </td>
          <td class="buttons">
            <input type="hidden" name="do" value="modify" />
            <input type="hidden" name="action" value="add_category" />
            <?php if ($proj->id): ?>
            <input type="hidden" name="project_id" value="{$proj->id}" />
            <?php endif; ?>
            <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
            <button type="submit">{L('addnew')}</button>
          </td>
        </tr>
      </table>
    </form>
</fieldset>
