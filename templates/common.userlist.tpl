  <fieldset><legend>{L('userlist')}</legend>

  <p>
    <img src="{$this->get_image('personal')}" alt="" class="middle" /> <a href="{$this->url(array($do, 'proj' . $proj->id, 'newuser'))}">{L('newuser')}</a>
  </p>
  <?php if ($user->perms('is_admin')): ?>
  <form action="{$this->url(array($do, 'proj' . $proj->id, 'users'))}" method="post">
  <p>
    <img src="{$this->get_image('button_ok')}" alt="" class="middle" />
    <a href="{$this->url(array($do, 'proj' . $proj->id, 'newuser'))}">{L('activateuser')}</a>
    <input type="text" class="text" id="user_name" name="user_name" value="" />
    <label>
      {L('password')}
      <input type="password" class="text" id="user_pass" name="user_pass" value="{Post::val('user_pass')}" />
    </label>
    <input type="hidden" name="action" value="activate_user" />
    <button type="submit">{L('OK')}</button>
  </p>
  </form>
  <?php endif; ?>

  <div id="search">
    <form action="{$this->url(array($do, 'users'))}" method="get">
    <table id="search-user-form">
      <tr>
        <td>
          <label class="notable" for="search_user_name">{L('username')}</label><input type="text" class="text" id="search_user_name" name="user_name" value="{Get::val('user_name')}" />
        </td>
        <td>
          <label class="notable" for="real_name">{L('realname')}</label><input type="text" class="text" id="real_name" name="real_name" value="{Get::val('real_name')}" />
        </td>
        <td rowspan="2">
            <label class="notable multisel" for="group_id">{L('group')}</label>
            <select name="group_id[]" multiple="multiple" size="7" id="group_id">
            <?php if ($do == 'admin'): ?>
              {!tpl_options(array(0 => L('any')), Get::val('group_id', 0))}
              <?php foreach ($all_groups as $project => $project_groups): ?>
              <optgroup label="{$project}">
              {!tpl_options($project_groups, Get::val('group_id'))}
              </optgroup>
              <?php endforeach; ?>
            <?php else: ?>
                {!tpl_options(Flyspray::listGroups($proj->id), Get::val('group_id'))}
            <?php endif; ?>
            </select>
        </td>
        <td rowspan="2"><button type="submit">{L('search')}</button></td>
      </tr>
      <tr>
        <td>
          <label class="notable" for="email_address">{L('email')}</label><input type="text" class="text" id="email_address" name="email_address" value="{Get::val('email_address')}" />
        </td><td>
          <label class="notable" for="jabber_id">{L('jabber')}</label><input type="text" class="text" id="jabber_id" name="jabber_id" value="{Get::val('jabber_id')}" />
        </td>
      </tr>
    </table>

    <div>
      <input type="hidden" name="do" value="{$do}" />
      <input type="hidden" name="area" value="users" />
    </div>
    </form>
  </div>

  <form method="post" action="{$this->url(array($do, 'users'))}">
  <table id="full_user_list" class="userlist">
  <colgroup>
    <col width="15" />
    <col width="*" />
    <col width="*" />
    <col width="*" />
    <col width="*" />
    <col width="*" />
    <col width="*" />
    <col style="width:5em;" />
  </colgroup>
  <thead>
    <tr>
      <th>
        <a href="javascript:ToggleSelected('full_user_list')">
          <img title="{L('toggleselected')}" alt="{L('toggleselected')}" src="{$this->get_image('kaboodleloop')}" width="16" height="16" />
        </a>
      </th>
      {!tpl_list_heading('username', L('username'))}
      {!tpl_list_heading('realname', L('realname'))}
      {!tpl_list_heading('email', L('email'))}
      {!tpl_list_heading('jabber', L('jabber'))}
      <th>{L('groups')}</th>
      {!tpl_list_heading('regdate', L('regdate'))}
      {!tpl_list_heading('status', L('status'))}
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
    <td><a href="{$this->url(array('admin', 'user'), array('user_id' => $usr['user_id']))}">{$usr['user_name']}</a></td>
    <td>{$usr['real_name']}</td>
    <td><a href="mailto:{$usr['email_address']}">{$usr['email_address']}</a></td>
    <td>{$usr['jabber_id']}</td>
    <td>
      <?php
      // First let's make sure there are arrays (not necessarily the case)
      settype($user_groups[$usr['user_id']]['group_name'], 'array');
      settype($user_groups[$usr['user_id']]['project_id'], 'array');
      foreach ( (array) array_get($user_groups[$usr['user_id']], 'group_id', array()) as $key => $group): ?>
        <?php
        // go through all projects and only show the groups if user has permission
        // to see the project
        $group_project_id = $user_groups[$usr['user_id']]['project_id'][$key];
        if ($group_project_id == '0'): ?>
        {L('global')}:
        <a href="{$this->url(array('admin', 'proj0', 'editgroup'), array('group_id' => $group))}">
          {$user_groups[$usr['user_id']]['group_name'][$key]}
        </a><br />
        <?php elseif (($title_key = Flyspray::array_find('project_id', $group_project_id, $fs->projects)) !== false): ?>
        {$fs->projects[$title_key]['project_title']}:
        <a href="{$this->url(array('pm', 'proj' . $group_project_id, 'editgroup'), array('group_id' => $group))}">
          {$user_groups[$usr['user_id']]['group_name'][$key]}
        </a><br />
        <?php endif; ?>
      <?php endforeach; ?>
    </td>
    <td>{formatDate($usr['register_date'])}</td>
    <?php if ($usr['account_enabled']) : ?>
    <td class="imgcol"><img src="{$this->get_image('button_ok')}" alt="{L('yes')}" /></td>
    <?php else: ?>
    <td class="imgcol"><img src="{$this->get_image('button_cancel')}" alt="{L('no')}" /></td>
    <?php endif; ?>
  </tr>
  <?php endforeach; ?>
  <tr>
    <td colspan="8">
      <table id="pagenumbers">
        <tr>
        <td>
            <button type="submit">{L('moveuserstogroup')}</button>
            <select class="adminlist" name="user_to_group">
            <?php if ($proj->id): ?>
            <option value="0">{L('nogroup')}</option>
            <?php endif; ?>
            {!tpl_options(Flyspray::listGroups($proj->id), null, false, null)}
            </select>
            <input type="hidden" name="project_id" value="{$proj->id}" />
            <input type="hidden" name="action" value="addusertogroup" />
            <input type="hidden" name="do" value="{$do}" />
        </td>
        <td id="numbers">{!pagenums(Get::num('pagenum', 1), 50, $user_count, $do, 'users')}</td>
        </tr>
      </table>
    </td>
  </tr>
  </table>
  </form>