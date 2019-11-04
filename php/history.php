<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
google.charts.load('current', {packages: ['corechart', 'line']});
google.charts.setOnLoadCallback(drawChart);

function onLoadFunction() {
  document.addEventListener('DOMContentLoaded', function() {
    let date = getParameterByName('date');
    if (date) {
      document.getElementById("dateInput").valueAsNumber = (parseInt(date)* 1000);
    } else {
      document.getElementById('dateInput').valueAsDate = new Date();
    }
}, false);

}

document.body.onload=onLoadFunction();

function URL_add_parameter(url, param, value){
    var hash       = {};
    var parser     = document.createElement('a');
    parser.href    = url;
    var parameters = parser.search.split(/\?|&/);
    for(var i=0; i < parameters.length; i++) {
        if(!parameters[i])
            continue;
        var ary      = parameters[i].split('=');
        hash[ary[0]] = ary[1];
    }
    hash[param] = value;
    var list = [];
    Object.keys(hash).forEach(function (key) {
        list.push(key + '=' + hash[key]);
    });
    parser.search = '?' + list.join('&');
    return parser.href;
}

function getParameterByName(name, url) {
    if (!url) url = window.location.href;
    name = name.replace(/[\[\]]/g, "\\$&");
    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, " "));
}

function loadNewDate() {
    location.href = URL_add_parameter(location.href, 'date', new Date(document.getElementById("dateInput").value).getTime()/1000);
}

function executeFilter(diffFromInput) {
    let date = document.getElementById("dateInput").valueAsNumber;
    if (isNaN(date)) {
      //no date selected. Use today.
      location.href = URL_add_parameter(location.href, 'date', document.getElementById("dateInput").value);
    } else {
      //check value of diffFromInput and add to "date"
      location.href = URL_add_parameter(location.href, 'date', date/1000 + (diffFromInput * 3600 * 24));
    }

}

function drawChart() {
  var data = new google.visualization.DataTable();

  data.addColumn('number', 'time');
  data.addColumn('number', 'price');
  data.addColumn({'type': 'string', 'role': 'style'});
  data.addColumn('number', 'thermometer');
  data.addColumn('number', 'average');
  data.addColumn({'type': 'string', 'role': 'style'});

  // Add empty rows
  data.addRows(24);

<?php
include("db.php");
date_default_timezone_set('GMT');
$theDate = $_GET['date'] != null ? $_GET['date'] : null;
if ($theDate == null) {
  $dt = new DateTime();
  $dt = $dt->setTime(0,0); // hour, minutes, seconds, micros
  $theDate = $dt->getTimestamp();
}

$fromDate = $theDate - 1;
$toDate = $theDate + 3600 * 24 -1;
$dataPointQuery = "select price from pricedata where timestamp > " . $fromDate . " and timestamp < " . $toDate;
$averagePrice = 0;
$maxValue = 0;

//This loop is just to get the average price.
$i = 0;
foreach ($dbh->query($dataPointQuery) as $row) {
    $i = $i + 1;
    $averagePrice = $averagePrice + $row[0];
    $maxValue = $row[0] > $maxValue ? $row[0] : $maxValue;
}
if ($i != 0) {
  $averagePrice = $averagePrice / $i;
}

//get previous thermostatValue (toStatus for last thermostat change)
$query = "select toStatus from status where timestamp < " . $fromDate;
$lastThermostatValue = $dbh->query($query)->fetch()[0];
$thermostatValue = $toStatus == -1 ? 0 : $maxValue;
$normalDot = "#109618";
$redDot = "#DC3912";
$yellowDot = "#FF9900";

$i = 0;
foreach ($dbh->query($dataPointQuery) as $row) {
    $price = $row[0];
    $j = 0;
    $fromTime = $fromDate + ($i * 3600);
    $toTime = $fromDate + (($i+1) * 3600);
    if ($i != 0) {
      $query = "select toStatus from status where timestamp >= " . $fromTime . " and timestamp < " . $toTime . " order by timestamp desc limit 1";
      $newThermostatValue = $dbh->query($query)->fetch()[0];
      if ($newThermostatValue != null) {
          $thermostatValue = $newThermostatValue == -1 ? 0 : $maxValue;
      }
    }

    $dotColor = $normalDot;
    if ($price > 1.2 * $averagePrice) {
        $dotColor = $redDot;
    } else if ($price > 1.1 * $averagePrice) {
        $dotColor = $yellowDot;
    }

    echo 'data.setCell(' . $i . ', ' . $j . ', ' . $i . ');
    '; //last zero is X axis value.
    echo 'data.setCell(' . $i . ', ' . ($j+1) . ', ' . $price . ');
    ';
    echo 'data.setCell(' . $i . ', ' . ($j+2) . ', "point { fill-color: ' . $dotColor . '; }");
    ';
    echo 'data.setCell(' . $i . ', ' . ($j+3) . ', ' . $thermostatValue . ');
    ';
    echo 'data.setCell(' . $i . ', ' . ($j+4) . ', ' . $averagePrice . ');
    ';
    $i = $i + 1;
}
?>

  var options = {
    hAxis: {
      title: 'Time',
      minValue: 0,
      maxValue: 24,
      ticks: [5,10,15,20]
    },
    vAxis: {
      title: 'Price'
    },
    series: {
      0: {pointSize: 5}
    },
    legend: { position: 'top' }
  };

  var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
  chart.draw(data, options);
}

</script>

<div class="tabcontent">
  <div class="averagePrice">Average price: <?php echo $averagePrice == 0 ? "No data" : $averagePrice;?></div>
  <div id="chart_div"></div>
  <div class="filters">
    <input type="button" value="<<" onclick="executeFilter(-1);"></button>
      <div class="date">
        <input id="dateInput" class="dateInput" type="date" onchange="loadNewDate()" value="">
      </div>
      <input type="button" value=">>" onclick="executeFilter(1);"></button>
  </div>
</div>
