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
function getDistanceOfLineString($linestring) {
  $distance = 0;
  $i = 0;
  do {
    $lat0 = $linestring[$i][0];
    $lon0 = $linestring[$i][1];
    $j = $i + 1;
    $lat1 = $linestring[$j][0];
    $lon1 = $linestring[$j][1];
    $distance = $distance + distance($lat0,$lon0,$lat1,$lon1,'K');
    $i++;
  } while (count($linestring) > ($j + 1)); 
  return $distance;
}

function getGeojsonLineStringLength($json_file) {
  $string = file_get_contents($json_file);
  $json_a = json_decode($string, true);
  $distance = 0;
 
  foreach($json_a['features'] as $feature) {
    if($feature['geometry']['type'] == 'MultiLineString') {
      foreach($feature['geometry']['coordinates'] as $linestring) {
        $distance = $distance + getDistanceOfLineString($linestring);
      }
    }
    elseif ($feature['geometry']['type'] == 'LineString') {
      $distance = $distance + getDistanceOfLineString($feature['geometry']['coordinates']);
    }
  }
  return $distance;
}

$total_todo = getGeojsonLineStringLength('./geojson/todo.geojson');
$total_done = getGeojsonLineStringLength('./geojson/done.geojson');
$total_unsat = getGeojsonLineStringLength('./geojson/nosatisfied.geojson');
$ratio_done = round($total_done / $total_todo * 100);
$ratio_unsat = round($total_unsat / $total_todo * 100);

$fin_mandat = mktime('0','0','0','01','01','2026');
$debut_mandat = mktime('0','0','0','07','01','2020');
$total_temps = $fin_mandat - $debut_mandat;
$temps_passe = time() - $debut_mandat;
$ratio_mandat = round($temps_passe / $total_temps * 100);

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <!-- Bootstrap core JS-->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Core theme JS-->
        <script src="js/scripts.js"></script>


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
                <a class="navbar-brand" href="#">Observatoire du vélo Montpellier</a>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                  <p class="ms-auto"><a href="https://www.velocite-montpellier.fr/"><img src='assets/logo_VGM.png' /></a></p>
                </div>
            </div>
        </nav>
        <!-- Page content-->
<div class="container">
<div class="text-center mt-5">
  <div style="margin-top: 10px" class="card">
    <div class="card-body">
      <h5 class="card-title">Plan Vélo</h5>
      <div style="margin: auto; margin-bottom: 20px" id="map"></div>
      <button id="mapbtn_amns" type="button" class="btn" style="font-size: x-small; background-color: grey; color: rgb(255, 255, 255)">Avant Mandat (satisfaisant)</button>
      <button id="mapbtn_ams" type="button" class="btn" style="font-size: x-small; background-color: grey; color: rgb(255, 255, 255)">Avant Mandat (non satisfaisant)</button>
      <button id="mapbtn_ns" type="button" class="btn" style="font-size: x-small; background-color: grey; color: rgb(255, 255, 255)">Non satisfaisant</button>
      <button id="mapbtn_s" type="button" class="btn" style="font-size: x-small; background-color: grey; color: rgb(255, 255, 255)">Satisfaisant</button>
      <button id="mapbtn_todo" type="button" class="btn" style="font-size: x-small; background-color: grey; color: rgb(255, 255, 255)">Annoncé</button>
      <br />
      <br />
      <ul class="list-group list-group-flush">
        <li class="list-group-item">
          <h6 class="card-subtitle mb-2 text-muted">Etat d'avancement</h6>
          <div class="progress" style="height: 20px">
            <div class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width: <?= $ratio_done ?>%" aria-valuenow="<?= $ratio_done ?>" aria-valuemin="0" aria-valuemax="100">
              <?= $ratio_done ?>% terminé
            </div>
            <div class="progress-bar progress-bar-striped bg-warning" role="progressbar" style="width: <?= $ratio_unsat ?>%" aria-valuenow="<?= $ratio_unsat ?>" aria-valuemin="0" aria-valuemax="100">
              <?= $ratio_unsat ?>% non satisfaisant
            </div>
          </div>
        </li>
        <li class="list-group-item">
          <h6 class="card-subtitle mb-2 text-muted">Temps écoulé depuis le début du mandat :</h6>
          <div class="progress" style="height: 20px">
            <div class="progress-bar progress-bar-striped bg-primary" role="progressbar" style="width: <?= $ratio_mandat ?>%" aria-valuenow="<?= $ratio_mandat ?>" aria-valuemin="0" aria-valuemax="100">
              <?= $ratio_mandat ?>% passés
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
  <div style="margin-top: 10px" class="card">
    <div class="card-body">
      <h5 class="card-title">Autres indicateurs</h5>
        <ul class="list-group list-group-flush">

<?php
  $engagements_progress_file = file_get_contents('./json/engagements_progress.json');
  $engagements_progress = json_decode($engagements_progress_file, true);
  $i = 0;
  foreach($engagements_progress as $engagement_progress) {
    $ratio = round($engagement_progress['status'] / $engagement_progress['target'] * 100);
    if($ratio < 50) {
      $progresscolor = 'bg-danger';
    }
    elseif($ratio < 90) {
      $progresscolor = 'bg-warning';
    }
    else {
       $progresscolor = 'bg-success';
    }
?>
<li class="list-group-item">
      <h6 class="card-subtitle mb-2 text-muted">
        <a class="btn" data-bs-toggle="collapse" href="#progress<?=$i ?>"><?= $engagement_progress['title'] ?></a>
      </h6>
      <div class="progress" style="height: 20px">
        <div class="progress-bar progress-bar-striped <?= $progresscolor ?>" role="progressbar" style="width: <?=$ratio ?>%" aria-valuenow="<?=$ratio ?>" aria-valuemin="0" aria-valuemax="100"><?= $ratio ?>%</div>
      </div>
      <div class="collapse" id="progress<?=$i ?>" style="margin-top: 10px">
        <div class="card card-body">
          <?=$engagement_progress['description'] ?>
        </div>
      </div>

</li>
<?php 
  $i++;
  } 
  $engagements_bin_file = file_get_contents('./json/engagements_binaires.json');
  $engagements_bin = json_decode($engagements_bin_file, true);
  $i = 0;
  foreach($engagements_bin as $engagement_bin) {
    switch($engagement_bin['status']) {
      case 2: 
        $icon = 'bi-check-square';
        $icon_color = 'green';
        break;
      case 1:
        $icon = 'bi-dash-square';
        $icon_color = 'orange';
        break;
      default:
        $icon = 'bi-x-square';
        $icon_color = 'red';
    }
?>
    <li class="list-group-item">
      <a class="btn" data-bs-toggle="collapse" href="#point<?=$i ?>" ><i class="bi <?=$icon ?>" style="font-size:24px; color:<?= $icon_color ?>"></i> <?= $engagement_bin['title'] ?></a>
      <div class="collapse" id="point<?=$i ?>" style="margin-top: 10px">
        <div class="card card-body">
          <?= $engagement_bin['description'] ?>
        </div>
      </div>
    </li>
<?php
    $i++;
   }
?>
  </ul>
  </div>
  </div>
    <div style="margin-top: 10px" class="card">
    <div class="card-body">
      <h5 class="card-title">Explications</h5>
      <p class="card-text">Some quick example text to build on the card title and make up the </p>
    </div>
  </div>
  <br />
</div>
    <script>
      var map = L.map('map').setView([43.60833089648225, 3.875926861270588], 12);

      function style_todo(feature) {
        return {
          fillColor: 'grey',
          weight: 5,
          opacity: 0.5,
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

      function hideDisplayLayer(layer) {
        if(layers.includes(layer)) {
          var index = layers.indexOf(layer);
          if (index !== -1) {
            layers.splice(index, 1);
          }
          map.removeLayer(layer);
        }
        else {
          map.addLayer(layer);
          layers.push(layer);
        }
      }

      L.tileLayer('https://cartodb-basemaps-{s}.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '<a href="http://www.velocite-montpellier.fr/" title="Vélocité Grand Montpellier">Vélocité Grand Montpellier</a> | &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(map);

      var geojsonLayerTODO = new L.GeoJSON.AJAX("./geojson/todo.geojson",{style: style_todo});       
      var geojsonLayerDone = new L.GeoJSON.AJAX("./geojson/done.geojson",{style: style_done});       
      var geojsonLayerNoSatisfied = new L.GeoJSON.AJAX("./geojson/nosatisfied.geojson",{style: style_unsat});       

      var layers = [  geojsonLayerDone, geojsonLayerNoSatisfied ]
      layers.forEach(function (item) {
        item.addTo(map);
      });

      $( "#mapbtn_amns" ).on( "click", function() {
        hideDisplayLayer(geojsonLayerTODO);
      } );
/* map.removeLayer(layer); 
 map.addLayer(layer); */
    </script>

    </body>
</html>

