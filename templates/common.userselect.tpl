<input class="users text singleuser" {!join_attrs($attrs)} type="text" name="{$name}" <?php if ($id): ?>id="{$id}"<?php endif; ?> value="{$value}" />
<?php if ($user->can_view_userlist()): ?>
<a href="#" onclick="userspopup('{$this->url('userselect')}', '{$id}')">
  <img src="{$this->get_image('kuser-small')}" width="16" height="16" alt="{L('selectuser')}" />
</a>
<?php endif; ?>
<script type="text/javascript">
    var options = {
	script: "{$this->relativeUrl($baseurl)}javascript/callbacks/usersearch.php?",
	varname: "user",
    delay:50,
    timeout:5000,
    minchars:2,
    noresults:'{#L('noresultsshort')}'
};
var as = new bsn.AutoSuggest('{$id}', options);
</script>
