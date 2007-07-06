<input class="users text singleuser" {!join_attrs($attrs)} type="text" name="{$name}" <?php if ($id): ?>id="{$id}"<?php endif; ?> value="{$value}" />
<a href="#" onclick="userspopup('{CreateUrl('userselect')}', '{$id}')">
  <img src="{$this->get_image('kuser-small')}" width="16" height="16" />
</a>
<span class="autocomplete hide" id="{$name}_complete"></span>
<script type="text/javascript">
    showstuff('{$name}_complete');
    new Ajax.Autocompleter('{$id}', '{$name}_complete', '{$baseurl}javascript/callbacks/usersearch.php', {})
</script>
