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

  //här behvöer vi fortsätta.
  // * vi kan visa datn men den är inte från rätt datum just nu. Kolla queryn. Datum tas inte hänsyn till
  // * Vissa punkter kommer vara röda. Vissa orange och andra gröna. Fixa detta baserat på algoritmen.
  //Använd hitte på algoritm så länge.

  // * Sen ska vi visa termostat på och av ovanpå detta men denna datan vet jag inte om den finns sparad.
  //jo den ligger i status tabellen.
  // * kolla bortkommenterat set cell data nedan hur man skulle kunna visa thermostat datan.

  var data = new google.visualization.DataTable();

  data.addColumn('number', 'time');
  data.addColumn('number', 'price');
  data.addColumn({'type': 'string', 'role': 'style'});
  data.addColumn('number', 'thermometer');

// Add empty rows
data.addRows(24); //24. one for every hour + 1 for every time thermometer changes in the interval.

<?php
include("db.php");
$period = $_GET['date'];
$dataPointQuery = "select price from pricedata"; // where timestamp > " . $fromDate . " and timestamp < " . $toDate;

$i = 0;
foreach ($dbh->query($dataPointQuery) as $row) {
    $price = $row[0];
    $j = 0;
    echo 'data.setCell(' . $i . ', ' . $j . ', ' . $i . ');
    '; //last zero is X axis value.
    echo 'data.setCell(' . $i . ', ' . ($j+1) . ', ' . $price . ');
    ';
    echo 'data.setCell(' . $i . ', ' . ($j+2) . ', "point { fill-color: #a52714; }");
    ';
    echo 'data.setCell(' . $i . ', ' . ($j+3) . ', 0);
    '; //TODO: last zero is thermometer on/off
    $i = $i + 1;
}
?>

// this is how to do to get a sågtandsdiagram for the thermometer value.
//data.setCell(1, 0, 0.001);
//data.setCell(1, 1, 32);
//data.setCell(0, 2, 'point { fill-color: #a52714; }');
//data.setCell(1, 3, 55);

  var options = {
    hAxis: {
      title: 'Time'
    },
    vAxis: {
      title: 'Price'
    },
    series: {
      0: {pointSize: 5}
    },
    legend: { position: 'bottom' }
  };

  var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
  chart.draw(data, options);
}
    //date_default_timezone_set('GMT');
    //ksort($dataPoints);

</script>

<?php
  function setSelected($value) {
    $period = $_GET['period'];
    echo "/*" . $period . " " . $value . "*/";
    if ($period == null && $value == 30) {
      echo "selected";
    } else if ($period == $value) {
      echo "selected";
    }
  }
?>

<div class="tabcontent">
  <div id="chart_div"></div>
    <div class="filters">
      <input type="button" value="<<" onclick="executeFilter(-1);"></button>
        <div class="date">
          <input id="dateInput" class="dateInput" type="date" value="new Date().toDateInputValue()">
        </div>
        <input type="button" value=">>" onclick="executeFilter(1);"></button>
    </div>



</div>
