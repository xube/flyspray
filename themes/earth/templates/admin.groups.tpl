 <h1>{L('admintoolboxlong')} | {L('usersandgroups')}</h1>

<form action="{$_SERVER['SCRIPT_NAME']}" method="post">
	<p>
		{L('addormoveusers')} {!tpl_userselect('uid')} {L('to')} <select name="user_to_group">
          <?php foreach ($group_list as $project => $project_groups): ?>
          <optgroup label="{$project}">
          {!tpl_options($project_groups)}
          </optgroup>
          <?php endforeach; ?>
          </select>
       <input type="hidden" name="do" value="admin" />
       <input type="hidden" name="area" value="groups" />
       <input type="hidden" name="action" value="addusertogroup" />
       <button type="submit">OK</button>
    </p>
</form>

<p>
	<img src="{$this->get_image('kuser')}" alt="" class="middle" /> <a href="{CreateURL(array('admin', 'newgroup'))}">{L('newgroup')}</a>
</p>

<table class="table-main">
	<caption>{L('currentglobalgroups')} ({count($groups)})</caption>
		<colgroup>
			<col width="3*" />
			<col width="*" />
			<col style="width:10em;" />
		</colgroup>
	<thead>
		<tr class="table-row-heading">
			<th>{L('groupname')}</th>
			<th>{L('users')}</th>
			<th>{L('groupopen')}</th>
		</tr>
	</thead>
	<?php $i=0; foreach ($groups as $group): ?>
	<tr class="table-row-<?php if ( is_int( $i/2 ) ) { echo 'even'; } else { echo 'odd'; } ?>">
		<td>
			<a href="{CreateUrl(array('admin', 'editgroup'), array('group_id' => $group['group_id']))}">{$group['group_name']}</a>
			<?php if ($group['group_desc'] != ''): ?>
			<br />
				<small>{$group['group_desc']}</small>
			<?php endif; ?>
		</td>
		<td>
			<a href="{CreateURL(array('admin', 'users'), array('group_id[]' => $group['group_id']))}">{$group['num_users']} {L('users')}</a>
		</td>
		<?php if ($group['group_open']) : ?>
			<td class="imgcol"><img src="{$this->get_image('button_ok')}" alt="{L('yes')}" /></td>
		<?php else: ?>
			<td class="imgcol"><img src="{$this->get_image('button_cancel')}" alt="{L('no')}" /></td>
		<?php endif; ?>
	</tr>
	<?php $i++; endforeach; ?>
</table>