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

$GeoJsonURL = "https://raw.githubusercontent.com/jesuisundesdeux/observatoire-carte/main/geojson/";
$total_todo = getGeojsonLineStringLength($GeoJsonURL . '/target_2026.geojson');
$total_done = getGeojsonLineStringLength($GeoJsonURL . '/wip_ok.geojson');
$total_unsat = getGeojsonLineStringLength($GeoJsonURL . '/wip_nok.geojson');
$total_pdone = getGeojsonLineStringLength($GeoJsonURL . '/before_ok.geojson');
$total_punsat = getGeojsonLineStringLength($GeoJsonURL . '/before_not_ok.geojson');
$ratio_done = round($total_done / $total_todo * 100);
$ratio_unsat = round($total_unsat / $total_todo * 100);
$ratio_pdone = round($total_pdone / $total_todo * 100);
$ratio_punsat = round($total_punsat / $total_todo * 100);

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
<style>.info { padding: 6px 8px; font: 14px/16px Arial, Helvetica, sans-serif; background: white; background: rgba(255,255,255,0.8); box-shadow: 0 0 15px rgba(0,0,0,0.2); border-radius: 5px; } .info h4 { margin: 0 0 5px; color: #777; }
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
      <button id="mapbtn_BeforeNotOK" type="button" class="btn" style="font-size: x-small; background-color: #FFB600; color: rgb(255, 255, 255)">Avant Mandat (non satisfaisant)</button>
      <button id="mapbtn_BeforeOK" type="button" class="btn" style="font-size: x-small; background-color: #00FF51; color: rgb(255, 255, 255)">Avant Mandat (satisfaisant)</button>
      <button id="mapbtn_WipNotOK" type="button" class="btn" style="font-size: x-small; background-color: #FF0000 ; color: rgb(255, 255, 255)">En cours (Non satisfaisant)</button>
      <button id="mapbtn_WipOK" type="button" class="btn" style="font-size: x-small; background-color: #1F753A; color: rgb(255, 255, 255)">En cours (Satisfaisant)</button>
      <button id="mapbtn_Target2026" type="button" class="btn" style="font-size: x-small; background-color: #94B8FF; color: rgb(255, 255, 255)">Annoncé en 2026</button>
      <br />
      <br />
      <ul class="list-group list-group-flush">
        <li class="list-group-item">
          <h6 class="card-subtitle mb-2 text-muted">Etat d'avancement</h6>
          <div class="progress" style="height: 20px">

            <div class="progress-bar progress-bar-striped" role="progressbar" style="background-color: #FFB600; width: <?= $ratio_punsat ?>%" aria-valuenow="<?= $ratio_punsat ?>" aria-valuemin="0" aria-valuemax="100">
              <?= $ratio_punsat ?>%
            </div>
            <div class="progress-bar progress-bar-striped" role="progressbar" style="background-color: #00FF51; width: <?= $ratio_pdone ?>%" aria-valuenow="<?= $ratio_pdone ?>" aria-valuemin="0" aria-valuemax="100">
              <?= $ratio_pdone ?>%
            </div>
            <div class="progress-bar progress-bar-striped" role="progressbar" style="background-color: #FF0000; width: <?= $ratio_unsat ?>%" aria-valuenow="<?= $ratio_unsat ?>" aria-valuemin="0" aria-valuemax="100">
              <?= $ratio_unsat ?>% 
            </div>

            <div class="progress-bar progress-bar-striped" role="progressbar" style="background-color: #1F753A; width: <?= $ratio_done ?>%" aria-valuenow="<?= $ratio_done ?>" aria-valuemin="0" aria-valuemax="100">
              <?= $ratio_done ?>%
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
/* https://leafletjs.com/examples/choropleth/example.html */
      function style_Target2026(feature) {
        return {
          fillColor: '#94B8FF',
          weight: 5,
          opacity: 1,
          color: '#94B8FF',  //Outline color
          fillOpacity: 0.5
        };
      }
      function style_BeforeNotOK(feature) {
        return {
          fillColor: '#FFB600',
          weight: 5,
          opacity: 1,
          color: '#FFB600',  //Outline color
          fillOpacity: 0.7
        };
      }
      function style_BeforeOK(feature) {
        return {
          fillColor: '#00FF51',
          weight: 5,
          opacity: 1,
          color: '#00FF51',  //Outline color
          fillOpacity: 0.7
        };
      }
      function style_WipNotOK(feature) {
        return {
          fillColor: '#FF0000',
          weight: 5,
          opacity: 1,
          color: '#FF0000',  //Outline color
          fillOpacity: 0.7
        };
      }
      function style_WipOK(feature) {
        return {
          fillColor: '#1F753A',
          weight: 5,
          opacity: 1,
          color: '#1F753A',  //Outline color
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
      var map = L.map('map').setView([43.60833089648225, 3.875926861270588], 12);
      L.tileLayer('https://cartodb-basemaps-{s}.global.ssl.fastly.net/light_all/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '<a href="http://www.velocite-montpellier.fr/" title="Vélocité Grand Montpellier">Vélocité Grand Montpellier</a> | &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(map);

      var GeoJSONURL = "https://raw.githubusercontent.com/jesuisundesdeux/observatoire-carte/main/geojson/";
      const geojsonLayerBeforeNotOK = new L.GeoJSON.AJAX(GeoJSONURL + "/before_not_ok.geojson",{style: style_BeforeNotOK,onEachFeature}).addTo(map);   
      const geojsonLayerBeforeOK = new L.GeoJSON.AJAX(GeoJSONURL + "/before_ok.geojson",{style: style_BeforeOK,onEachFeature}).addTo(map);
      const geojsonLayerWipNotOK = new L.GeoJSON.AJAX(GeoJSONURL + "/wip_nok.geojson",{style: style_WipNotOK,onEachFeature}).addTo(map);
      const geojsonLayerWipOK = new L.GeoJSON.AJAX(GeoJSONURL + "/wip_ok.geojson",{style: style_WipOK,onEachFeature}).addTo(map);
      const geojsonLayerTarget2026 = new L.GeoJSON.AJAX(GeoJSONURL + "/target_2026.geojson",{style: style_Target2026,onEachFeature}).addTo(map);
      var layers = [  geojsonLayerTarget2026,geojsonLayerBeforeNotOK,geojsonLayerBeforeOK,geojsonLayerWipNotOK,geojsonLayerWipOK ]
      var mouseLatLng = {lat: null, lng: null}; 

      map.on("layeradd", function (event) {
        geojsonLayerTarget2026.bringToBack();
      });


      // control that shows state info on hover
      const info = L.control();

      info.onAdd = function (map) {
        this._div = L.DomUtil.create('div', 'info');
	  this.update();
	  return this._div;
      };

      info.update = function (props,latlng) {
        $("#infostreet").html('');
        if(latlng) {
          $.get('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='+latlng['lat']+'&lon='+latlng['lng'], function(data){
            if(data.address.road != undefined) {
              $("#infostreet").html('Rue: ' + data.address.road + '<br />');
            }
          });
        }
        const contents = props ? `<b>${props.od}</b><br />Veloligne ${props.nom_itiner}<br /><span id="infostreet"></span>Longueur: ${props.length_km}km` : 'Selectionner un tronçon';
	this._div.innerHTML = `${contents}`;
      };
    
      info.addTo(map);

      function highlightFeature(e) {
        const layer = e.target;
        mouseLatLng = e.latlng;

        layer.setStyle({
	  weight: 7,
	});

	layer.bringToFront();
        info.update(layer.feature.properties,mouseLatLng);
      }
      function resetHighlight(e) {
        const layer = e.target;
        layer.setStyle({
          weight: 5,
        });
	info.update();
      }
      function onEachFeature(feature, layer) {
          layer.on({
            mouseover: highlightFeature,
            mouseout: resetHighlight,
/*			click: zoomToFeature*/
	});
      }
      $( "#mapbtn_BeforeNotOK").on( "click", function() {
        hideDisplayLayer(geojsonLayerBeforeNotOK);
      });
      $( "#mapbtn_BeforeOK").on( "click", function() {
        hideDisplayLayer(geojsonLayerBeforeOK);
      });
      $( "#mapbtn_WipNotOK").on( "click", function() {
        hideDisplayLayer(geojsonLayerWipNotOK);
      });
      $( "#mapbtn_WipOK").on( "click", function() {
         hideDisplayLayer(geojsonLayerWipOK);
      });
      $( "#mapbtn_Target2026").on( "click", function() {
        hideDisplayLayer(geojsonLayerTarget2026);
      });

    </script>

    </body>
</html>

