<div id="toolbox">
  <h3>{L('admintoolboxlong')} :: {L('userlist')}</h3>
  <fieldset><legend>{L('userlist')}</legend>
  
  <p>
    <img src="{$this->get_image('personal')}" alt="" class="middle" /> <a href="{CreateURL('admin', 'newuser', $proj->id)}">{L('newuser')}</a>
  </p>
  
  <div id="search">
    <form action="{$_SERVER['SCRIPT_NAME']}" method="get">
    <label for="user_name">{L('username')}</label><input type="text" class="text" id="user_name" name="user_name" value="{Get::val('user_name')}" />
    <label for="real_name">{L('realname')}</label><input type="text" class="text" id="real_name" name="real_name" value="{Get::val('real_name')}" />
    <label for="email_address">{L('email')}</label><input type="text" class="text" id="email_address" name="email_address" value="{Get::val('email_address')}" />
    <label for="jabber_id">{L('jabber')}</label><input type="text" class="text" id="jabber_id" name="jabber_id" value="{Get::val('jabber_id')}" />
    <label for="group_id">{L('group')}</label>
    <select name="group_id" id="group_id">
      {!tpl_options(array('0' => L('any')), Get::val('group_id'))}
      <?php foreach ($all_groups as $project => $project_groups): ?>
      <optgroup label="{$project}">
      {!tpl_options($project_groups, Get::val('group_id'))}
      </optgroup>
      <?php endforeach; ?>
    </select>
    <button type="submit">{L('search')}</button>
    
    <input type="hidden" name="do" value="admin" />
    <input type="hidden" name="area" value="users" />
    </form>
  </div>
  
  <table id="full_user_list" class="userlist">
  <colgroup>
    <col width="15" />
    <col width="*" />
    <col width="*" />
    <col width="*" />
    <col width="*" />
    <col style="width:6em;" />
  </colgroup>
  <thead>
    <tr>
      <th>
        <a href="javascript:ToggleSelected('full_user_list')">
          <img title="{L('toggleselected')}" alt="{L('toggleselected')}" src="{$this->get_image('kaboodleloop')}" width="16" height="16" />
        </a>
      </th>
      {!tpl_list_heading('username')}
      {!tpl_list_heading('realname')}
      {!tpl_list_heading('email')}
      {!tpl_list_heading('jabber')}
      {!tpl_list_heading('status')}
    </tr>
  </thead>
  <?php
  $count = 0;
  foreach($user_list as $usr):
      if ($count >= 50) {
        break;
      }
      $count += 1;
  ?>
  <tr>
    <td class="ttcolumn">{!tpl_checkbox('users['.$usr['user_id'].']')}</td>
    <td><a href="{CreateURL('edituser', $usr['user_id'])}">{$usr['user_name']}</a></td>
    <td>{$usr['real_name']}</td>
    <td><a href="mailto:{$usr['email_address']}">{$usr['email_address']}</a></td>
    <td><a href="mailto:{$usr['jabber_id']}">{$usr['jabber_id']}</a></td>
    <?php if ($usr['account_enabled']) : ?>
    <td class="imgcol"><img src="{$this->get_image('button_ok')}" alt="{L('yes')}" /></td>
    <?php else: ?>
    <td class="imgcol"><img src="{$this->get_image('button_cancel')}" alt="{L('no')}" /></td>
    <?php endif; ?>
  </tr>
  <?php endforeach; ?>
  <tr>
    <td colspan="6">
      <table id="pagenumbers">
        <tr>
        <td>
          <form>
            <div>
            <button type="submit">{L('moveuserstogroup')} (not yet working)</button>
            <select class="adminlist" name="switch_to_group">
            <?php if ($proj->id): ?>
            <option value="0">{L('nogroup')}</option>
            <?php endif; ?>
            {!tpl_options(Flyspray::listGroups($proj->id), null, false, null)}
            </select>
            <input type="hidden" name="project_id" value="{$proj->id}" />
            <input type="hidden" name="action" value="movetogroup" />
            </div>
          </form>
        </td>
        <td id="numbers">{!pagenums(Get::num('pagenum'), 50, $user_count, 'admin', 'users')}</td>
        </tr>
      </table>
    </td>
  </tr>
  </table>
</div>
