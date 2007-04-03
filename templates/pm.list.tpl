<div id="toolbox">
  <h3>{L('pmtoolbox')} :: {L('list')}: {$list_name}</h3>

    <fieldset class="box">
    <legend>{L('list')}: {$list_name}</legend>
    <?php if ($list_type == LIST_CATEGORY): ?>
    <?php $this->display('common.cat.tpl'); ?>
    <?php else: ?>
    <?php $this->display('common.list.tpl'); ?>
    <?php endif; ?>
    </fieldset>
</div>
