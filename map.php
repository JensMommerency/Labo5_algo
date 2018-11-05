<?php
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

	$data = json_dijkstra($_GET['from_lat'], $_GET['from_lon'], $_GET['to_lat'], $_GET['to_lon'], $_GET['transport']);
}
catch(Exception $e){
	echo "<p>".$e->getMessage()."</p>";
	exit();
}

$dijkstra = json_decode($data);

$pathlength = count($dijkstra->path);
$lats = array();
$lons = array();

function getLonLat($node_id){
  global $mysqli;
  $sql = "SELECT lat, lon
          FROM `osm_nodes`
          WHERE `id` = '$node_id'";
  $retval = $mysqli->query($sql);
  $lonlat = $retval->fetch_assoc();
  return $lonlat;
}

for ($i = 0; $i < $pathlength; $i++) {
    $lonlat = getLonLat($dijkstra->path[$i]);
    array_push($lats, $lonlat['lat']);
    array_push($lons, $lonlat['lon']);
}

?>


<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
    <meta charset="utf-8">
    <title>Dijkstra</title>
    <style>
      html, body, #map-canvas {
        height: 100%;
        margin: 0px;
        padding: 0px
      }
    </style>

<?php
$middle_lat = ($lats[0] + $lats[count($lats)-1])/2;
$middle_lon = ($lons[0] + $lons[count($lons)-1])/2;
?>

    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false"></script>
    <script>

function initialize() {


  var mapOptions = {
    zoom: 15,
    center: new google.maps.LatLng(<?php echo $middle_lat; ?>, <?php echo $middle_lon; ?>),
    mapTypeId: google.maps.MapTypeId.TERRAIN
  };

  var map = new google.maps.Map(document.getElementById('map-canvas'),
      mapOptions);


  var PathCoordinates = [
  ];



  <?php
    for ($i = 0; $i < $pathlength; $i++) {


      echo "PathCoordinates.push(new google.maps.LatLng(";
      echo $lats[$i];
      echo ",";
      echo $lons[$i];
      echo "));";
    }
  ?>


  var ShortestPath = new google.maps.Polyline({
    path: PathCoordinates,
    geodesic: true,
    strokeColor: '#FF0000',
    strokeOpacity: 1.0,
    strokeWeight: 2
  });

  ShortestPath.setMap(map);
}

google.maps.event.addDomListener(window, 'load', initialize);

    </script>
  </head>
  <body>
    <div id="map-canvas"></div>
  </body>
</html>
