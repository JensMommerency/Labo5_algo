<?php

/*
	author Maarten Slembrouck <maarten.slembrouck@gmail.com>
*/
//gs,gbngbqns
function initialize_mysql_connection(){
	global $servername;
	global $username;
	global $password;
	global $dbname;

	// Create connection
	$mysqli = new mysqli($servername, $username, $password, $dbname);

	if ($mysqli->connect_errno) {
		echo "Sorry, this website is experiencing problems.";
		echo "Error: Failed to make a MySQL connection, here is why: \n";
		echo "Errno: " . $mysqli->connect_errno . "\n";
		echo "Error: " . $mysqli->connect_error . "\n";
		exit;
	}
        return $mysqli;
}

function close_mysql_connection(){
	global $mysqli;
	$mysqli->close();
}


function getMinMaxLatLon(){
  global $mysqli;
  $sql = "SELECT MIN( lat ) lat_min, MAX( lat ) lat_max, MIN( lon ) lon_min, MAX( lon ) lon_max FROM  `cities`";
  $retval = $mysqli->query($sql);
  if($retval && $row = $retval->fetch_assoc()){
    return array($row['lat_min'], $row['lat_max'], $row['lon_min'], $row['lon_max']);
  }
  else{
    return null;
  }
}

function checkLonLat($from_lat, $from_lon, $to_lat, $to_lon){
  $latlonbounds = getMinMaxLatLon();
  if($from_lat < $latlonbounds[0] || $from_lat > $latlonbounds[1]){
    throw new Exception("Input Error: from_lat out of bound", 6);
  }
  else if($from_lon < $latlonbounds[2] || $from_lon > $latlonbounds[3]){
    throw new Exception("Input Error: from_lon out of bound", 7);
  }
  if($to_lat < $latlonbounds[0] || $to_lat > $latlonbounds[1]){
    throw new Exception("Input Error: to_lat out of bound", 8);
  }
  else if($to_lon < $latlonbounds[2] || $to_lon > $latlonbounds[3]){
    throw new Exception("Input Error: to_lon out of bound", 9);
  }
}


function getAllNeighboursForNodeId($node_id){
  global $mysqli;
  $buren=array();
	$sql = "select neighbour_id from city_connections where node_id = ". $node_id."";
	//echo 'SQL Query for nodes = '.$sql.'<br>';
	$query = $mysqli->query($sql);
	if ($query->num_rows > 0) {
    for($x=0;$x<$query->num_rows;$x++){
      $row = $query->fetch_assoc();
      array_push($buren,$row['neighbour_id']);
    }
			return $buren;
	} else {
	    echo "ERROR : 0 results at getAllNeighboursForNodeId".PHP_EOL;
	}
		return NULL;
}

function getNodeId($from_lat, $from_lon){
  global $mysqli;

	$sql = 'select id from cities 
	order by ((' . $from_lat . '-lat)*(' . $from_lat . '-lat)+(' . $from_lon . '-lon)*(' . $from_lon . '-lon)) limit 1';
	//echo 'SQL Query for nodes = '.$sql.'<br>';
	$result = $mysqli->query($sql);
	$node_id = NULL;
	if ($result->num_rows > 0) {
    	$row = $result->fetch_assoc() ;
			$node_id = $row['id'];
			echo 'Node Found = '.$node_id.'<br>';;
			return $node_id;
	} else {
	    echo "ERROR : 0 results at getNodeId".PHP_EOL;
	}
		return NULL;
}

function allemaal(){
  global $mysqli;
  $knopen=array();
	$sql = 'select id from cities';
	$query = $mysqli->query($sql);
	if ($query->num_rows > 0) {
    for($x=0;$x<$query->num_rows;$x++){
      $row = $query->fetch_assoc();
      array_push($knopen,$row['id']);
    }
      
      return $knopen;
      
	} else {
	    echo "ERROR : 0 results at Allemaal".PHP_EOL;
	}
		return NULL;
}

function weg($q,$buren){
  for($x=0;$x<count($buren);$x++){
    $zoek=array_search($buren[$x],$q);
    if($zoek!==FALSE){
      array_splice($buren,$zoek,1);
    }  
  }
  return $buren;
}


function afstand($node1,$node2){
  global$mysqli;
  $sql="select distance from city_connections where node_id='.$node1.' and neighbour_id=' .$node2.'";
  $result=$mysqli->query($sql);
  if($result->num_rows>0){
    $row= $result->fetch_assoc();
    $distance=$row['distance'];
    return $distance;
  }
  else {
    echo "ERROR afstand ".PHP_EOL;
}
  return NULL;
}

function getShortestPathDijkstra($from_node, $to_node, $transport){
  $knopen=allemaal();
  $q=allemaal();
  $dist=array();
  $prev= array();  
  for($x=0;$x<count($q);$x++){
    array_push($dist,INF);
    array_push($prev,NULL);
    echo "size Q={$q[$x]} \n";
  }
  $indexBron=array_search($from_node,$q);
  $dist[$indexBron]=0;
  $gevonden=FALSE;
  $counter=0;
  while (count($q)>0 and $gevonden===FALSE and $counter<100){
    $var=count($q);
    echo"count=$var \n";
    $min=min($dist);
    $index=array_search($min,$dist);
    $huidigeNode=$q[$index];
    array_splice($q,$index,1);
    array_splice($dist,$index,1);
    if($huidigeNode===$to_node){
      $gevonden=TRUE;
    }
    else{
        echo "next_node = {$huidigeNode} \n".PHP_EOL;
        $buren=getAllNeighboursForNodeId($from_node);
        $buren=weg($q,$buren);
        for($x=0;$x<count($buren);$x++){
          echo "buur = {$buren[$x]} \n".PHP_EOL;
          $alt=$min+afstand($huidigeNode,$buren[$x]);
          $indexBuur=array_search($buren[$x],$q);
          if($alt<$dist[$indexBuur]){
              $dist[$indexBuur]=$alt;
              $prev[$indexBuur]=$huidigeNode;
          }
      }
    }$counter++;
  }
  $seq=array();
  $u=$to_node;
  if(array_search($u,$prev)!==FALSE or $u===$from_node){
    $einde=FALSE;
    while(array_search($u,$prev)!==FALSE and $einde===FALSE ){
      $check=array_search($u,$prev);
        if($prev[$check]===NULL){
          $einde=TRUE;
          array_push($seq,$u);
        }
        else{
          array_push($seq,$u);
          $u=$prev[$check];
        }
    }

  }
  $indexAfstand=array_search($to_node,$knopen);
  return array($dist[$indexAfstand],$seq);
}

function json_dijkstra($from_lat, $from_lon, $to_lat, $to_lon, $transport){
  $from_node = getNodeId($from_lat, $from_lon); // complete implementation in func.php
  $to_node = getNodeId($to_lat, $to_lon);

  // To think about: what if there is no path between from_node and to_node?
  // add a piece of code here (after you have a working Dijkstra implementation)
  // which throws an error if no path could be found -> avoid that your algorithm visits all nodes in the database

  list($distance,$path) = getShortestPathDijkstra($from_node, $to_node, $transport); // complete implementation in func.php

  // throw new Exception("Error: ...");

  $output = array(
      "from_node" => $from_node,
      "to_node" => $to_node,
      "path" => $path,
      "distance" => $distance
  );

  return json_encode($output);
}

?>
