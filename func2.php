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

function aantalKnopen(){
  global $mysqli;
  $knopen=array();
	$sql = 'select id from cities';
	$query = $mysqli->query($sql);
	if ($query->num_rows > 0) {
        return $query;
      
	} else {
	    echo "ERROR : 0 results at Allemaal".PHP_EOL;
	}
		return NULL;
}
function allemaal(){
    global $mysqli;
    $knopen=array();
      $sql = 'select count (*) from cities';
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

  function linken(){
      global $mysqli;
      $links=array();
      $sql='select node_id,neighbour_id, distance from city_connections';
      $query=$mysqli->query($sql);
      if($query->num_rows>0){
        for($x=0;$x<$query->num_rows;$x++){
            $row = $query->fetch_assoc();
            $links[$row['node_id']][$row['neighbour_id']]=$row['distance'];
          }
          return $links;
      }
      else{
          echo "ERROR linken".PHP_EOL;
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
    $S=array();
    $Q=array();
    $links=linken();
    //echo "klaar linken{count($links)}".PHP_EOL;
    foreach(array_keys($links)as $val) $Q[$val]=INF;//indexen van de nodes allemaal gelijk stellen aan inf
    $Q[$from_node]=0;// index van start op nul
    while(!empty($Q)){
        $min = array_search(min($Q), $Q);//the most min weight
        if($min == $to_node) break;
        foreach($links[$min] as $key=>$val) if(!empty($Q[$key]) && $Q[$min] + $val < $Q[$key]) {
            $Q[$key] = $Q[$min] + $val;
            $S[$key] = array($min, $Q[$key]);
        }
        unset($Q[$min]);
    }    
    //list the path
    $path = array();
    $pos = $to_node;
    while($pos != $from_node){
        $path[] = $pos;
        $pos = $S[$pos][0];
    }
    $path[] = $from_node;
    $path = array_reverse($path);
    return array($S[$to_node][1],$path);

    

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
