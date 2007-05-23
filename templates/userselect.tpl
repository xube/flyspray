<script type="text/javascript">
String.prototype.trim = function() {
	return this.replace(/^\s+|\s+$/g,"");
}
Array.prototype.indexOf = function( v, b, s ) {
 for( var i = +b || 0, l = this.length; i < l; i++ ) {
  if( this[i].toUpperCase()===v.toUpperCase() || s && this[i].toUpperCase()==v.toUpperCase() ) { return i; }
 }
 return -1;
};

function adduser(username)
{
    var el = opener.document.getElementById('assigned_to');
    // check if user is already added, then remove him
    el.value = el.value.replace(',', ';');
    var users = el.value.split(';');
    // filter empty elements
    var a = new Array();
    for (i = 0; i < users.length; i++) { if (users[i].trim() != '') a.push(users[i].trim()) }
    users = a;

    // delete user or add it?
    var index = users.indexOf(username);
    if (index == -1) {
        users.push(username);
    } else {
        users.splice(users.indexOf(username), 1);
    }

    el.value = users.join('; ');
}
</script><div id="content">

<table class="userlist">
  <thead>
  <tr>
    <th>{L('globalgroups')}</th><th>{$proj->prefs['project_title']}</th>
  </tr>
  </thead>

  <tr>
    <td>
      <?php foreach ($globalgroups as $group): ?>
        <a href="{CreateUrl('userselect', array('group_id' => $group['group_id']))}">{$group['group_name']} ({$group['num_users']})</a><br />
      <?php endforeach;?>
    </td>
    <td>
      <?php foreach ($groups as $group): ?>
        <a href="{CreateUrl('userselect', array('group_id' => $group['group_id']))}">{$group['group_name']} ({$group['num_users']})</a><br />
      <?php endforeach;?>
      <br /><i><strong><a href="{CreateUrl('userselect', array('group_id' => -1))}">{L('defaultusers')}</a></i>
    </td>
  </tr>
</table>

<form method="post" action="{CreateUrl('userselect', array('group_id' => Get::val('group_id')))}">
<div class="box" style="margin:0 1em 0.5em 0;">
<label>{L('username')} <input name="user_name" type="text" class="text" value="{Post::val('user_name')}" /></label>
<label>{L('realname')} <input name="real_name" type="text" class="text" value="{Post::val('real_name')}" /></label>
<button type="submit">{L('go')}</button>
</div>
</form>

<table class="userlist">
  <thead>
    <tr>
      {!tpl_list_heading('username', L('username'))}
      {!tpl_list_heading('realname', L('realname'))}
      {!tpl_list_heading('email', L('email'))}
    </tr>
  </thead>

    <?php
    $count = 0;
    foreach ($users as $usr):
    if ($count >= 20) {
        break;
    }
    $count += 1;
    ?>
    <tr>
      <td><a href="javascript:adduser('{$usr['user_name']}')">{$usr['user_name']}</a></td>
      <td>{$usr['real_name']}</td>
      <td><a href="mailto:{$usr['email_address']}">{$usr['email_address']}</a></td>
    </tr>
    <?php endforeach; ?>

    <td id="numbers" colspan="3">{!pagenums(Get::num('pagenum', 1), 20, $usercount, $do, 'users')}</td>

</table>
</div>