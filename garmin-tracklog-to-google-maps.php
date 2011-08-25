<?php
// convert decimal minute coordinates to decimal format for google
function convert_coord($coord)
{
  // match N46 39.559 to [N][46][39.559]
  preg_match('/^([N|E|S|W])+([0-9]+) (.*)$/',$coord,$match);

  // if matches not found, return passed token
  if(!count($match)) return $coord;

  // assign results
  list($coord,$hem,$deg,$min) = $match;

  // calculate decimal format, flip negative for W/S points
  $coord = ($deg+($min/60))*($hem=="W"||$hem=="S"?-1:1);

  // round by precision of 4 and return
  return round($coord,4);
}

// convert a mapsource text file to usable php array
function parse_tracklog($file)
{
  // fail gracefully if file is not read
  if(!$track = file_get_contents($file))
  {
    print "Unable to read tracklog [$file].";
    return false;
  }

   // explode by newline and remove header lines
  $lines = array_slice(explode("\n",trim($track)),9);

  // determine number of entries
  $count = count($lines);

  // initialize array to save track data
  $data = array();

  // loop over track data
  for($i=0;$i<$count;$i++)
  {
    // get data for current line
    $coord = explode("\t",$lines[$i]);

    // skip lines that don't have the right amount of columns
    if(count($coord) != 10) continue;

    // store converted lat/lng (could also get altitude, speed etc)
    $row = array
    (
      "lat" => convert_coord(substr($coord[1],0,10)),
      "lng" => convert_coord(substr($coord[1],11))
    );

    // append parsed row to data array
    $data[] = $row;
  }

  // return the array of points
  return $data;
}

// parse tracklog and print javascript call to draw polyline
function display_track($file)
{
  // parse data
  if($points = parse_tracklog($file))
  {
    // get middle point for centering on line
    $midpoint = $points[(count($points)/2)];

    // loop over coordinates and build javascript calls
    $coords = array();
    foreach($points as $point) $coords[] = "coord($point[lat],$point[lng])";
    $coords = implode(",",$coords);

    // display polyline
    print "polyline(map,[$coords]);";

    // center map on middle point
    print "map.setCenter(coord({$midpoint['lat']},{$midpoint['lng']}));";
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8"/>
  <title>Google Maps Polyline: Garmin Tracklog</title>
  <script src="code.jquery.com/jquery-1.6.2.min.js"></script>
  <script src="http://maps.google.com/maps/api/js?sensor=true"></script>
  <style type="text/css">*{margin:0px;padding:0px}</style>
</head>
<body>
  <div id="gmap"></div>
<script>
var width,height; // save browser height/width

// initialize map
var map = new google.maps.Map(document.getElementById('gmap'),
{
  zoom: 7, // set zoom level to 7
  scrollwheel: false, // disable scrollwheel zooming
  mapTypeId: google.maps.MapTypeId.ROADMAP, // show roadmap format by default
  navigationControl: true, // enable nav controls
  navigationControlOptions: {style: google.maps.NavigationControlStyle.SMALL}, // small nav
  scaleControl: true, // enable map scale
});

// return gmaps lat/lng object from coords
function coord(lat,lng) { return new google.maps.LatLng(lat,lng); };

// construct a polyline and return object
// m = map object
// coords = array of gmap latlng objects
// color = color of line, defaults to red
// opacity = opacity of line, defaults to .5
// weight = thickness of line, defaults to 6
function polyline(m,coords,color,opacity,weight)
{
  if(!color) color = '#ff0000';
  if(!opacity) opacity = 0.5;
  if(!weight) weight = 6;
  return new google.maps.Polyline(
  {
    path: coords,
    strokeColor: color,
    strokeOpacity: opacity,
    strokeWeight: weight
  }).setMap(m);
};

// function to resize map to fill browser window
function sizemap()
{
  // get current height
  var h = $(window).height();
  
  // get current width
  var w = $(window).width();
  
  // if they don't match the last time it was checked, reset
  if(h != height || w != width)
  {
    $('#gmap').css('height',h);
    $('#gmap').css('width',w);
    height = h;
    width = w;
  }
};
$(document).ready(function(){$(window).resize(sizemap);sizemap();});
<?php display_track("tracklog.txt"); ?>
</script>
</body>
</html>
