<fieldset class="admin">
  <legend>{$admin_text['categories']}</legend>
  <p>{$admin_text['listnote']}</p>
  <div class="admin">
    <form action="{$baseurl}" method="post">
      <table class="list">
        <?php
        $countlines = -1;

        foreach ($proj->listCatsIn($is_admin) as $row):
            $countlines++;
            $subrows = $proj->listCatsIn($is_admin, $row['category_id']);
        ?>
        <tr>
          <td>
            <input type="hidden" name="id[]" value="{$row['category_id']}" />
            <label for="categoryname{$countlines}">{$admin_text['name']}</label>
            <input id="categoryname{$countlines}" type="text" size="15" maxlength="40" name="list_name[]" 
              value="{$row['category_name']}" />
          </td>
          <td title="{$admin_text['listordertip']}">
            <label for="listposition{$countlines}">{$admin_text['order']}</label>
            <input id="listposition{$countlines}" type="text" size="3" maxlength="3" name="list_position[]" value="{$row['list_position']}" />
          </td>
          <td title="{$admin_text['listshowtip']}">
            <label for="showinlist{$countlines}">{$admin_text['show']}</label>
            {!tpl_checkbox('show_in_list['.$countlines.']', $row['show_in_list'], 'showinlist'.$countlines)}
          </td>
          <td title="{$admin_text['categoryownertip']}">
            <label for="categoryowner{$countlines}">{$admin_text['owner']}</label>
            <select id="categoryowner{$countlines}" name="category_owner[]">
              <option value="">{$admin_text['selectowner']}</option>
              {!tpl_options($proj->UserList(), $row['category_owner'])}
            </select>
          </td>
          <td title="{$admin_text['listdeletetip']}">
            <?php if (!$row['used_in_tasks'] && !count($subrows)): ?>
            <label for="delete{$row['category_id']}">{$admin_text['delete']}</label>
            <input id="delete{$row['category_id']}" type="checkbox" name="delete[{$row['category_id']}]" value="1" />
            <?php endif; ?>
          </td>
        </tr>
        <?php foreach ($subrows as $subrow):
                $countlines++; ?>
        <tr>
          <td>
            <input type="hidden" name="id[]" value="{$subrow['category_id']}" />
            &rarr;
            <label for="categoryname{$countlines}">{$admin_text['name']}</label>
            <input id="categoryname{$countlines}" type="text" size="15" maxlength="40" name="list_name[]" value="{$subrow['category_name']}" />
          </td>
          <td title="{$admin_text['listordertip']}">
            <label for="listposition{$countlines}">{$admin_text['order']}</label>
            <input id="listposition{$countlines}" type="text" size="3" maxlength="3" name="list_position[]" value="{$subrow['list_position']}" />
          </td>
          <td title="{$admin_text['listshowtip']}">
            <label for="showinlist{$countlines}">{$admin_text['show']}</label>
            {!tpl_checkbox('show_in_list['.$countlines.']', $row['show_in_list'], 'showinlist'.$countlines)}
          </td>
          <td title="{$admin_text['categoryownertip']}">
            <label for="categoryowner{$countlines}">{$admin_text['owner']}</label>
            <select id="categoryowner{$countlines}" name="category_owner[]">
              <option value="">{$admin_text['selectowner']}</option>
              {!tpl_options($proj->UserList(), $row['category_owner'])}
            </select>
          </td>
          <td title="{$admin_text['listdeletetip']}">
            <?php if (!$subrow['used_in_tasks']): ?>
            <label for="delete{$subrow['category_id']}">{$admin_text['delete']}</label>
            <input id="delete{$subrow['category_id']}" type="checkbox" name="delete[{$subrow['category_id']}]" value="1" />
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; endforeach; ?>
        <tr>
          <td colspan="4"></td>
          <td class="buttons">
            <input type="hidden" name="do" value="modify" />
            <input type="hidden" name="action" value="update_category" />
            <input type="hidden" name="list_type" value="category" />
            <input type="hidden" name="project_id"
                   value="<?php if($is_admin): ?>0<?php else: ?>{$proj->id}<?php endif; ?>" />
            <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
            <input class="adminbutton" type="submit" value="{$admin_text['update']}" />
          </td>
        </tr>
      </table>
    </form>

    <hr />

    <!-- Form to add a new category to the list -->
    <form action="{$baseurl}" method="post">
      <table class="list">
        <tr>
          <td>
            <label for="listnamenew">{$admin_text['name']}</label>
            <input id="listnamenew" type="text" size="15" maxlength="30" name="list_name" />
          </td>
          <td title="{$admin_text['listordertip']}">
            <label for="listpositionnew">{$admin_text['order']}</label>
            <input id="listpositionnew" type="text" size="3" maxlength="3" name="list_position" />
          </td>
          <td title="{$admin_text['categoryownertip']}" colspan="2">
            <label for="categoryownernew" >{$admin_text['owner']}</label>
            <select id="categoryownernew" name="category_owner">
              <option value="">{$admin_text['selectowner']}</option>
              {!tpl_options($proj->UserList())}
            </select>
          </td>
          <td colspan="2" title="{$admin_text['categoryparenttip']}">
            <label for="parent_id">{$admin_text['subcategoryof']}</label>
            <select id="parent_id" name="parent_id">
              <option value="">{$admin_text['notsubcategory']}</option>
              <?php $cat_opts = array_map(
              create_function('$x', 'return array($x["category_id"], $x["category_name"]);'),
              $proj->listCatsIn($is_admin));
              ?>
              {!tpl_options($cat_opts, Get::val('cat'))}
            </select>
          </td>
          <td class="buttons">
            <input type="hidden" name="do" value="modify" />
            <input type="hidden" name="action" value="add_category" />
            <?php if (!$is_admin): ?>
            <input type="hidden" name="project_id" value="{$proj->id}" />
            <?php endif; ?>
            <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
            <input class="adminbutton" type="submit" value="{$admin_text['addnew']}" />
          </td>
        </tr>
      </table>
    </form>
  </div>
</fieldset>
