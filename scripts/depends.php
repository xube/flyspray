<?php

  /********************************************************\
  | Task Dependancy Graph                                  |
  | ~~~~~~~~~~~~~~~~~~~~~                                  |
  \********************************************************/

if(!defined('IN_FS')) {
    die('Do not access this file directly.');
}

if ( !($task_details = Flyspray::GetTaskDetails(Req::num('id')))
        || !$user->can_view_task($task_details))
{
    Flyspray::show_error(9);
}

// Configuration information:
// [FIXME: in the future, this will come from the initial configuration.]
$path_to_dot = @$conf['general']['dot_path']; // Where's the dot executable?
/* March 10 2006 Jason Porter: Removed the $basedir as $path_for_images
 * should be relative, we use this path also in the HTML output.  Saving
 * the file from dot happens later, and that should be the absolute path.
 */
$path_for_images = '/attachments'; // What directory do we use for output?
$fmt = 'png';
$id = Req::num('id');
$page->assign('taskid', 0);

// Minor(?) FIXME: we save the graphs into a directory (attachments), 
// but they never get deleted. Once there, they'll be overwritten but
// never removed, except for manually.

// ASAP Todo items:
// - Hook in WebDot method, so if you don't have system() or dot, 
//   you can still see the pretty pictures
// - Need to get the configuration options put into the installer/configurator
//   (someone who knows them well should probably do it)

// Other Todo items:
// - Put the pruning strings into the details language pack, once they're set

$prunemode = Req::val('prune', 0);
$selfurl   = CreateURL('depends', $id);
$pmodes    = array('None', 'Prune Closed Links', 'Prune Closed Tasks');

foreach ($pmodes as $mode => $desc) {
    if ($mode == $prunemode) {
        $strlist[] = $desc;
    } else {
        $strlist[] = "<a href='". htmlspecialchars($selfurl, ENT_QUOTES, 'utf-8') . 
                      ($mode !=0 ? "&amp;prune=$mode" : "") . "'>$desc</a>\n";
    }
}

$page->uses('strlist');

$starttime = microtime();

$sql= "SELECT t1.task_id AS id1, t1.item_summary AS sum1, 
             t1.percent_complete AS pct1, t1.is_closed AS clsd1, 
             lst1.status_name AS stat1, t1.task_severity AS sev1,
             t1.task_priority AS pri1,
             t1.closure_comment AS com1, u1c.real_name AS clsdby1,
             r1.resolution_name as res1,
             t2.task_id AS id2, t2.item_summary AS sum2, 
             t2.percent_complete AS pct2, t2.is_closed AS clsd2, 
             lst2.status_name AS stat2, t2.task_severity AS sev2,
             t2.task_priority AS pri2,
             t2.closure_comment AS com2, u2c.real_name AS clsdby2,
             r2.resolution_name as res2
       FROM  {dependencies} AS d
       JOIN  {tasks} AS t1 ON d.task_id=t1.task_id
  LEFT JOIN  {users} AS u1c ON t1.closed_by=u1c.user_id
  LEFT JOIN  {list_status} AS lst1 ON t1.item_status = lst1.status_id
  LEFT JOIN  {list_resolution} AS r1 ON t1.resolution_reason=r1.resolution_id
       JOIN  {tasks} AS t2 ON d.dep_task_id=t2.task_id
  LEFT JOIN  {list_status} AS lst2 ON t2.item_status = lst2.status_id
  LEFT JOIN  {users} AS u2c ON t2.closed_by=u2c.user_id
  LEFT JOIN  {list_resolution} AS r2 ON t2.resolution_reason=r2.resolution_id
      WHERE  t1.attached_to_project= ?
   ORDER BY  d.task_id, d.dep_task_id";

$get_edges = $db->Query($sql, array($proj->id));

$edge_list = array();
$rvrs_list = array();
$node_list = array();
while ($row = $db->FetchRow($get_edges)) {
    
    extract($row, EXTR_REFS|EXTR_SKIP);
    $edge_list[$id1][] = $id2;
    $rvrs_list[$id2][] = $id1;
    if (!isset($node_list[$id1])) {
        $node_list[$id1] =
	  array('id'=>$id1, 'sum'=>$sum1, 'pct'=>$pct1, 'clsd'=>$clsd1,
		'status_name'=>$stat1, 'sev'=>$sev1, 'pri'=>$pri1,
		'com'=>$com1, 'clsdby'=>$clsdby1, 'res'=>$res1);
    }
    if (!isset($node_list[$id2])) {
        $node_list[$id2] =
	  array('id'=>$id2, 'sum'=>$sum2, 'pct'=>$pct2, 'clsd'=>$clsd2,
		'status_name'=>$stat2, 'sev'=>$sev2, 'pri'=>$pri2,
		'com'=>$com2, 'clsdby'=>$clsdby2, 'res'=>$res2);
    }
}

// Now we have our lists of nodes and edges, along with a helper
// list of reverse edges. Time to do the graph coloring, so we know
// which ones are in our particular connected graph. We'll set up a
// list and fill it up as we visit nodes that are connected to our
// main task.

$connected  = array();
$levelsdown = 0;
$levelsup   = 0;
function ConnectsTo($id, $down, $up) {
    global $connected, $edge_list, $rvrs_list, $levelsdown, $levelsup;
    global $prunemode, $node_list;
    if (!isset($connected[$id])) { $connected[$id]=1; }
    if ($down > $levelsdown) { $levelsdown = $down; }
    if ($up   > $levelsup  ) { $levelsup   = $up  ; }
#echo "$id ($down d, $up u) => $levelsdown d $levelsup u<br>\n";
    $selfclosed = $node_list[$id]['clsd'];
    if (isset($edge_list[$id])) {
        foreach ($edge_list[$id] as $neighbor) {
            $neighborclosed = $node_list[$neighbor]['clsd'];
            if (!isset($connected[$neighbor]) &&
                    !($prunemode==1 && $selfclosed && $neighborclosed) &&
                    !($prunemode==2 && $neighborclosed)) {
                ConnectsTo($neighbor, $down, $up+1);
            }
        }
    }
    if (isset($rvrs_list[$id])) {
        foreach ($rvrs_list[$id] as $neighbor) {
            $neighborclosed = $node_list[$neighbor]['clsd'];
            if (!isset($connected[$neighbor]) &&
                    !($prunemode==1 && $selfclosed && $neighborclosed) &&
                    !($prunemode==2 && $neighborclosed)) {
                ConnectsTo($neighbor, $down+1, $up);
            }
        }
    }
}

ConnectsTo($id, 0, 0);
$connected_nodes = array_keys($connected);
sort($connected_nodes);

//echo "<pre>".implode(", ", $connected_nodes)."</pre>\n";

// Now lets get rid of the extra junk in our arrays.
// In prunemode 0, we know we're only going to have to get rid of
// whole lists, and not elements in the lists, because if they were
// in the list, they'd be connected, so we wouldn't be removing them.
// In prunemode 1 or 2, we may have to remove stuff from the list, because
// you can have an edge to a node that didn't end up connected.
foreach (array("edge_list", "rvrs_list", "node_list") as $l) {
    foreach (${$l} as $n => $list) {
        if (!isset($connected[$n])) {
            unset(${$l}[$n]);
            //echo "rm list $n in $l<br>\n";
        }
        if ($prunemode!=0 && $l!="node_list" && isset(${$l}[$n])) {
            // Only keep entries that appear in the $connected_nodes list
#echo "${l}[$n] = ".print_r(${$l}[$n], 1)."<br>\n";
            ${$l}[$n] = array_intersect(${$l}[$n], $connected_nodes);
        }
    }
}
#echo "<pre>".print_r($edge_list, 1)."</pre>\n";
#echo "<pre>".implode(", ", array_keys($edge_list))."</pre>\n";
#echo "<pre>".print_r($rvrs_list, 1)."</pre>\n";
#echo "<pre>".print_r($node_list, 1)."</pre>\n";

// Now we've got everything we need... let's draw the pretty pictures

//Open the graph, and print global options
$lj = "l"; // label justification - l, r, or n (for center)
$graphname = "task_${id}_dependencies";
$dotgraph = "digraph $graphname {\n".
    "node [width=1.5, shape=rectangle, style=\"filled\", ".
    "fontsize=7.0, pencolor=black, margin=\"0.1, 0.0\"];\n";
// define the nodes
foreach ($node_list as $n => $r) {
    $col = "";
    if ($r['clsd'] && $n!=$id) { $r['pct'] = 120; }
    // color code: shades of gray for % done, shades of yellow for this task
    $x = dechex(255-($r['pct']*1.5)-($n==$id ? 105 : 0));
    if ($n==$id) { $col = "#ffff$x"; } else {  $col = "#$x$x$x"; }
    // Make sure label terminates in \n!
    $label = "FS#$n - ".
        ($r['clsd'] ? L('closed') :
         "$r[pct]% ".L('complete')).#" status $r[stat]".
         "\n".wordwrap(addslashes($r['sum']), 20)."\n";
    $tooltip =
      ($r['clsd'] ? L('closed') . ": $r[res]".
       (!empty($r['clsdby']) ? " ($r[clsdby])" : "").
       ($r['com']!='' ? " - $r[com]" : "")
       : $fs->severities[$r['sev']]. L('severity') . "/".
       $fs->priorities[$r['pri']]. L('priority') . " - ".
       L('status') . ": ".$r['status_name']);
    $dotgraph .= "FS$n [label=\"".str_replace("\n", "\\$lj", $label)."\", ".
        "href=\"".CreateURL("details", $n)."\", ".
        "tooltip=\"$tooltip\", fillcolor=\"$col\"];\n";
}
// Add edges
foreach ($edge_list as $src => $dstlist) {
    foreach ($dstlist as $dst) {
        $dotgraph .= "FS$src -> FS$dst;\n";
    }
}
// all done
$dotgraph .= "}\n";

if (!Flyspray::function_disabled('system')) {
    // All done with the graph. Save it to a temp file.
    $tname = tempnam('', 'fs_depends_dot_');
    $tmp   = fopen($tname, 'wb');
    fwrite($tmp, $dotgraph);
    fclose($tmp);

    // Now run dot on it:
    $out = "$path_for_images/depends_$id". ($prunemode!=0 ? "_p$prunemode" : "").".$fmt";
    $cmd = "$path_to_dot -T $fmt -o \"" . BASEDIR . $out . "\" $tname";
    $stat = 0;
    $rv  = system($cmd, $stat);
    if ($rv===false) { echo "<pre>error running $cmd:\n'$stat'\n$rv\n</pre>\n"; }

    $cmd = "$path_to_dot -T cmapx $tname";
    $rv = system($cmd, $stat);
    if ($rv===false) { echo "<pre>error running $cmd:\n'$stat'\n$rv\n</pre>\n"; }

    unlink($tname);

/*
    [TC] We cannot have this stuff outputting here, so I put it in a quick template
*/
    $page->assign('image', $out);
    $page->assign('taskid', $id);
    $page->assign('graphname', $graphname);
    
    $endtime = microtime();
    list($startusec, $startsec) = explode(' ', $starttime);
    list($endusec, $endsec) = explode(' ', $endtime);
    $diff = ($endsec - $startsec) + ($endusec - $startusec);
    $page->assign('time', round($diff, 2));
}

#echo "<pre>$dotgraph</pre>\n";

$page->setTitle('FS#' . $id . ': ' . L('dependencygraph'));
$page->pushTpl('depends.tpl');
?>
