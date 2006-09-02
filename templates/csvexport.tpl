<?php foreach ($visible as $column): ?>
{$column};<?php
endforeach; ?>

<?php foreach ($tasks as $task): ?>
<?php foreach ($visible as $column): ?>
{!tpl_csv_cell($task, $column)};<?php
endforeach; ?>

<?php endforeach; ?>