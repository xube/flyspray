<?php if ($do == 'details' || $do == 'editcomment' || $do == 'newtask' || $do == 'myprofile'): ?>
<span style="position:relative;">
  <a href="javascript:showhidestuff('%id_plugins');">{L('options')}</a>
  <span class="hide popup plugins" id="%id_plugins">
    <select id="%id_syntax_plugins" name="%id_syntax_plugins[]" multiple="multiple" size="4">
      {!tpl_options(array_map('get_class', $this->text->classes), $plugins, true)}
    </select>
    <button type="button" onclick="hidestuff('%id_plugins')">{L('OK')}</button>
  </span>
</span>
<?php endif; ?>
</div>