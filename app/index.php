<?php
function distance($lat1, $lon1, $lat2, $lon2, $unit) {

  $theta = $lon1 - $lon2;
  $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
  $dist = acos($dist);
  $dist = rad2deg($dist);
  $miles = $dist * 60 * 1.1515;
  $unit = strtoupper($unit);

  if ($unit == "K") {
      return ($miles * 1.609344);
  } else if ($unit == "N") {
      return ($miles * 0.8684);
  } else {
      return $miles;
  }
}
function getGeojsonLineStringLength($json_file) {
  $string = file_get_contents($json_file);
  $json_a = json_decode($string, true);
  $distance = 0;
  
  foreach($json_a['features'] as $feature) {
    $i = 0;
    do {
      $lat0 = $feature['geometry']['coordinates'][$i][0];
      $lon0 = $feature['geometry']['coordinates'][$i][1];
      $j = $i + 1;
      $lat1 = $feature['geometry']['coordinates'][$j][0];
      $lon1 = $feature['geometry']['coordinates'][$j][1];
  
      $distance = $distance + distance($lat0,$lon0,$lat1,$lon1,'K');
      $i++;
    } while (count($feature['geometry']['coordinates']) > ($j + 1));
  }
  return $distance;
}
$total_todo = getGeojsonLineStringLength('./geojson/todo.geojson');
$total_done = getGeojsonLineStringLength('./geojson/done.geojson');
$total_unsat = getGeojsonLineStringLength('./geojson/nosatisfied.geojson');
$ratio_done = round($total_done / $total_todo * 100);
$ratio_unsat = round($total_unsat / $total_todo * 100);

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Vélocité - Observatoire du plan Vélo de Montpellier</title>
        <!-- Favicon-->
        <link rel="icon" type="image/x-icon" href="assets/favicon.ico" />
        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="css/styles.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.8.0/dist/leaflet.css" integrity="sha512-hoalWLoI8r4UszCkZ5kL8vayOGVae1oxXe/2A4AO6J9+580uKHDO3JdHb7NzwwzK5xr/Fs0W40kiNHxM9vyTtQ==" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.8.0/dist/leaflet.js" integrity="sha512-BB3hKbKWOc9Ez/TAwyWxNXeoV9c1v6FIeYiBieIWkpLjauysF18NzgR1MBNBXf8/KABdlkX68nAhlwcDFLGPCQ==" crossorigin=""></script>
    <script src="js/leaflet-ajax.js"></script>

  <style>
  html, body {
    height: 100%;
    margin: 0;
  }
  .leaflet-container {
      height: 600px;
      width: 800px;
      max-width: 100%;
      max-height: 100%;
    }
  </style>

    </head>
    <body>
        <!-- Responsive navbar-->
        <nav class="navbar navbar-expand-lg navbar-dark bg-yellow">
            <div class="container">
                <a class="navbar-brand" href="#">Observatoire plan vélo Montpellier</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Link</a></li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Dropdown</a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="#">Action</a></li>
                                <li><a class="dropdown-item" href="#">Another action</a></li>
                                <li><hr class="dropdown-divider" /></li>
                                <li><a class="dropdown-item" href="#">Something else here</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <!-- Page content-->
        <div class="container">
            <div class="text-center mt-5">
    <div style="margin: auto" id="map"></div>

    <script>
      var map = L.map('map').setView([43.60833089648225, 3.875926861270588], 12);

      function style_todo(feature) {
        return {
          fillColor: 'grey',
          weight: 5,
          opacity: 1,
          color: 'grey',  //Outline color
          fillOpacity: 0.7
        };
      }
      function style_done(feature) {
        return {
          fillColor: 'green',
          weight: 5,
          opacity: 1,
          color: 'green',  //Outline color
          fillOpacity: 0.7
        };
      }
      function style_unsat(feature) {
        return {
          fillColor: 'orange',
          weight: 5,
          opacity: 1,
          color: 'orange',  //Outline color
          fillOpacity: 0.7
        };
      }


      L.tileLayer('https://{s}.tile-cyclosm.openstreetmap.fr/cyclosm/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '<a href="http://www.velocite-montpellier.fr/" title="Vélocité Grand Montpellier">Vélocité Grand Montpellier</a> | <a href="https://github.com/cyclosm/cyclosm-cartocss-style/releases" title="CyclOSM - Open Bicycle render">CyclOSM</a> | Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(map);
      var geojsonLayer = new L.GeoJSON.AJAX("./geojson/todo.geojson",{style: style_todo});       
      geojsonLayer.addTo(map);
      var geojsonLayer = new L.GeoJSON.AJAX("./geojson/done.geojson",{style: style_done});       
      geojsonLayer.addTo(map);
      var geojsonLayer = new L.GeoJSON.AJAX("./geojson/nosatisfied.geojson",{style: style_unsat});       
      geojsonLayer.addTo(map);

    </script>
    <div style="margin-top: 10px" class="card">
      <div class="card-body">
        Etat d'avancement
        <div class="progress">
          <div class="progress-bar-striped bg-success" role="progressbar" style="width: <?= $ratio_done ?>%" aria-valuenow="<?= $ratio_done ?>" aria-valuemin="0" aria-valuemax="100"><?= $ratio_done ?>% terminé</div>
          <div class="progress-bar-striped bg-warning" role="progressbar" style="width: <?= $ratio_unsat ?>%" aria-valuenow="<?= $ratio_unsat ?>" aria-valuemin="0" aria-valuemax="100"><?= $ratio_unsat ?>% non satisfaisant</div>
        </div>
     </div>
   </div>
            </div>
        </div>
        <!-- Bootstrap core JS-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Core theme JS-->
        <script src="js/scripts.js"></script>
    </body>
</html>

