<div class="box"><p><b>Pruning Level: </b>{!implode(" &nbsp;|&nbsp; \n", $strlist)}</p>
<h2>FS#{!$task_id}: {L('dependencygraph')}</h2>
<div>{!$map}</div>
<img src="{$image}" alt="Dependencies for task {$task_id}" class="depimage" usemap="#{$graphname}" />
<p>{sprintf(L('pagegenerated'), $time)}<p>
</div>