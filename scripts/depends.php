<?php

/*
   This script displays a task dependancy graph.
*/

$fs->get_language_pack($lang, 'details');

// Configuration information:
// [FIXME: in the future, this will come from the initial configuration.]
$path_to_dot = "/usr/local/bin/dot"; // Where's the dot executable?
$path_for_images = "attachments"; // What directory do we use for output?
$fmt = "png"; // Do you need a different image format?

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

// Only load this page if a valid task was actually requested
if (!$fs->GetTaskDetails($_REQUEST['id']))
   $fs->Redirect( $fs->CreateURL('error', null) );

$task_details = $fs->GetTaskDetails($_REQUEST['id']);

// Check if they have permissions to view this task (same checks as details)
if ($task_details['project_is_active'] == '1'
  && ($project_prefs['others_view'] == '1'
      OR @$permissions['view_tasks'] == '1')
  && (($task_details['mark_private'] == '1'
       && $task_details['assigned_to'] == $current_user['user_id'])
           OR @$permissions['manage_project'] == '1'
           OR $task_details['mark_private'] != '1')
   )
{

  if (isset($_REQUEST['prune'])) { $prunemode = $_REQUEST['prune']; }
  else { $prunemode = 0; }

  $selfurl = $fs->CreateURL('depends',$id);
  $pmodes = array(0=>"None",1=>"Prune Closed Links",2=>"Prune Closed Tasks");
  foreach ($pmodes as $mode => $desc) {
    $strlist[] =
      ($mode==$prunemode ? $desc :
       "<a href='$selfurl".($mode!=0 ? "&prune=$mode" : "")."'>$desc</a>\n");
  }
  echo "<p><b>Pruning Level: </b>\n".
    implode(" &nbsp;|&nbsp; \n",$strlist)."</p>\n";


  $starttime = microtime();

  $sql = "SELECT
     t1.task_id AS id1, t1.item_summary AS sum1, t1.percent_complete as
     pct1, t1.is_closed AS clsd1, t1.item_status AS stat1,
     t2.task_id AS id2, t2.item_summary AS sum2, t2.percent_complete as
     pct2, t2.is_closed AS clsd2, t2.item_status AS stat2
     FROM {$dbprefix}dependencies AS d
     JOIN {$dbprefix}tasks AS t1 ON d.task_id=t1.task_id
     JOIN {$dbprefix}tasks AS t2 ON d.dep_task_id=t2.task_id
     WHERE t1.attached_to_project='$project_id'
     ORDER BY d.task_id, d.dep_task_id";
  #echo "<pre>".print_r($sql,1)."</pre>\n";
  $get_edges = $db->Query($sql);

  $edge_list = array();
  $rvrs_list = array();
  $node_list = array();
  while ($row = $db->FetchArray($get_edges))
  {
    extract($row);
    if (isset($edge_list[$id1])) {
      array_push($edge_list[$id1],$id2);
    } else {
      $edge_list[$id1] = array($id2);
    }
    if (isset($rvrs_list[$id2])) {
      array_push($rvrs_list[$id2],$id1);
    } else {
      $rvrs_list[$id2] = array($id1);
    }
    $node_list[$id1] = array("id"=>$id1, "sum"=>$sum1, "pct"=>$pct1,
              "clsd"=>$clsd1, "stat"=>$stat1);
    $node_list[$id2] = array("id"=>$id2, "sum"=>$sum2, "pct"=>$pct2,
              "clsd"=>$clsd2, "stat"=>$stat2);

  }
  #echo "<pre>".print_r($edge_list,1)."</pre>\n";
  #echo "<pre>".print_r($rvrs_list,1)."</pre>\n";
  #echo "<pre>".print_r($node_list,1)."</pre>\n";

  // Now we have our lists of nodes and edges, along with a helper
  // list of reverse edges. Time to do the graph coloring, so we know
  // which ones are in our particular connected graph. We'll set up a
  // list and fill it up as we visit nodes that are connected to our
  // main task.

  $connected  = array();
  $levelsdown = 0;
  $levelsup   = 0;
  function ConnectsTo($id,$down,$up) {
    global $connected, $edge_list, $rvrs_list, $levelsdown, $levelsup;
    global $prunemode, $node_list;
    if (!isset($connected[$id])) { $connected[$id]=1; }
    if ($down > $levelsdown) { $levelsdown = $down; }
    if ($up   > $levelsup  ) { $levelsup   = $up  ; }
    //echo "$id ($down d, $up u) => $levelsdown d $levelsup u<br>\n";
    $selfclosed = $node_list[$id]['clsd'];
    if (isset($edge_list[$id])) {
      foreach ($edge_list[$id] as $neighbor) {
   $neighborclosed = $node_list[$neighbor]['clsd'];
   if (!isset($connected[$neighbor]) &&
       !($prunemode==1 && $selfclosed && $neighborclosed) &&
       !($prunemode==2 && $neighborclosed)) {
     ConnectsTo($neighbor,$down,$up+1);
   }
      }
    }
    if (isset($rvrs_list[$id])) {
      foreach ($rvrs_list[$id] as $neighbor) {
   $neighborclosed = $node_list[$neighbor]['clsd'];
   if (!isset($connected[$neighbor]) &&
       !($prunemode==1 && $selfclosed && $neighborclosed) &&
       !($prunemode==2 && $neighborclosed)) {
     ConnectsTo($neighbor,$down+1,$up);
   }
      }
    }
  }

  ConnectsTo($id,0,0);
  $connected_nodes = array_keys($connected);
  sort($connected_nodes);

  //echo "<pre>".implode(", ",$connected_nodes)."</pre>\n";

  // Now lets get rid of the extra junk in our arrays.
  // In prunemode 0, we know we're only going to have to get rid of
  // whole lists, and not elements in the lists, because if they were
  // in the list, they'd be connected, so we wouldn't be removing them.
  // In prunemode 1 or 2, we may have to remove stuff from the list, because
  // you can have an edge to a node that didn't end up connected.
  foreach (array("edge_list","rvrs_list","node_list") as $l) {
    foreach (${$l} as $n => $list) {
      if (!isset($connected[$n])) {
   unset(${$l}[$n]);
   //echo "rm list $n in $l<br>\n";
      }
      if ($prunemode!=0 && $l!="node_list" && isset(${$l}[$n])) {
   // Only keep entries that appear in the $connected_nodes list
   //echo "${l}[$n] = ".print_r(${$l}[$n],1)."<br>\n";
   ${$l}[$n] = array_intersect(${$l}[$n],$connected_nodes);
      }
    }
  }
  #echo "<pre>".print_r($edge_list,1)."</pre>\n";
  #echo "<pre>".implode(", ",array_keys($edge_list))."</pre>\n";
  #echo "<pre>".print_r($rvrs_list,1)."</pre>\n";
  #echo "<pre>".print_r($node_list,1)."</pre>\n";

  // Now we've got everything we need... let's draw the pretty pictures

  //Open the graph, and print global options
  $lj = "l"; // label justification - l, r, or n (for center)
  $graphname = "task_${id}_dependencies";
  $dotgraph = "digraph $graphname {\n".
    "node [width=1.5,shape=rectangle,style=\"filled\",".
    "fontsize=7.0,pencolor=black,margin=\"0.1,0.0\"];\n";
  // define the nodes
  foreach ($node_list as $n => $r) {
    $col = "";
    if ($r['clsd'] && $n!=$id) { $r['pct'] = 120; }
    // color code: shades of gray for % done, shades of yellow for this task
    $x = dechex(255-($r['pct']*1.5)-($n==$id ? 105 : 0));
    if ($n==$id) { $col = "#ffff$x"; } else {  $col = "#$x$x$x"; }
    // Make sure label terminates in \n!
    $label = "FS#$n - ".
      ($r['clsd'] ? $details_text['closed'] :
       "$r[pct]% ".$details_text['complete']).#" status $r[stat]".
      "\n".wordwrap($r['sum'],20)."\n";
    $dotgraph .= "FS$n [label=\"".str_replace("\n","\\$lj",$label)."\",".
      "href=\"".$fs->CreateURL("details",$n)."\",".
      "tooltip=\"".str_replace("\n"," ",$label)."\",".
      "fillcolor=\"$col\"];\n";
  }
  // Add edges
  foreach ($edge_list as $src => $dstlist) {
    foreach ($dstlist as $dst) {
      $dotgraph .= "FS$src -> FS$dst;\n";
    }
  }
  // all done
  $dotgraph .= "}\n";

  // All done with the graph. Save it to a temp file.
  $tname = tempnam("","fs_depends_dot_");
  $tmp = fopen($tname,"w");
  fwrite($tmp,$dotgraph);
  fclose($tmp);

  // Now run dot on it:
  $out = "$path_for_images/depends_$id".
    ($prunemode!=0 ? "_p$prunemode" : "").".$fmt";
  $cmd = "$path_to_dot -T $fmt -o$out $tname";
  $rv = system($cmd,$stat);
  if ($rv===false) { echo "<pre>error running $cmd:\n'$stat'\n$rv\n</pre>\n"; }

  $cmd = "$path_to_dot -T cmapx $tname";
  $rv = system($cmd,$stat);
  if ($rv===false) { echo "<pre>error running $cmd:\n'$stat'\n$rv\n</pre>\n"; }

  unlink($tname);

  echo "<img src='$out' alt='task $id dependencies' usemap='$graphname'>\n";

  #echo "<pre>$dotgraph</pre>\n";

  $endtime = microtime();

  list($startusec,$startsec) = explode(" ",$starttime);
  list($endusec,$endsec) = explode(" ",$endtime);
  $diff = ($endsec - $startsec) + ($endusec - $startusec);
  echo "\n<p>\nPage and image generated in ".round($diff,2).
    " seconds.\n<p>\n";

} else {

  // They don't have permission to view this task!
  $fs->Redirect( $fs->CreateURL('error', null) );

} // end of "if they have permission"

?>
