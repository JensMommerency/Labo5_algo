<?php
header('Content-Type: application/json');

require_once 'config.php';
require_once 'func.php';

$mysqli = initialize_mysql_connection();

try{
  if(isset($_GET['from_lat'])) $from_lat = $_GET['from_lat']; else throw new Exception("Input Error: from_lat not set", 1);
  if(isset($_GET['from_lon'])) $from_lon = $_GET['from_lon']; else throw new Exception("Input Error: from_lon not set", 2);
  if(isset($_GET['to_lat'])) $to_lat = $_GET['to_lat']; else throw new Exception("Input Error: to_lat not set", 3);
  if(isset($_GET['to_lon'])) $to_lon = $_GET['to_lon']; else throw new Exception("Input Error: to_lon not set", 4);
  if(isset($_GET['transport'])) $transport = $_GET['transport']; else throw new Exception("Input Error: transport not set", 5);

  checkLonLat($from_lat, $from_lon, $to_lat, $to_lon); // check for out of bound

  echo json_dijkstra($from_lat, $from_lon, $to_lat, $to_lon, $transport);
}
catch(Exception $e){
  $error = array("error" => $e->getMessage());
  echo json_encode($error);
}
close_mysql_connection();
?>
