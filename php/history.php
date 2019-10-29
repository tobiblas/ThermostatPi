
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
google.charts.load('current', {'packages':['corechart']});
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

  här behvöer vi fortsätta. KOlla hur vi kan få in datan som ligger i tabellen pricedata
  så den visas som ett linjediagram. Vissa punkter kommer vara röda. Vissa orange och andra gröna.

  Sen ska vi visa termostat på och av ovanpå detta men denna datan vet jag inte om den finns sparad.
  jo den ligger i status tabellen.


    var data = new google.visualization.DataTable();
    data.addColumn('string', 'x');
    data.addColumn({type: 'string', role: 'annotation'});
    <?php
    include("db.php");
    $period = $_GET['period'];

    $location = "";
    $locations = array();?>
    //$locationsQuery = "select distinct timestamp, price from pricedata";

    /*date_default_timezone_set('GMT');
    $dataPoints = array();

    $dataPointQuery = "";

    if ($fromDate != null && $toDate != null && $fromDate < $toDate) {
        $dataPointQuery = "select timestamp,temp,label from temperature where timestamp > " . $fromDate . " and timestamp < " . $toDate . " and location = '";
        $locationsQuery = $locationsQuery . " where timestamp > " . $fromDate . " and timestamp < " . $toDate;
    } else {
        $fromTime = 0;
        if ($period != null) {
          $fromTime = time() - ($period * 24 * 60 * 60);
        } else {
          $fromTime = time() - (30 * 24 * 60 * 60); #show 1 month as default
        }
        $dataPointQuery = "select timestamp,temp,label from temperature where timestamp > " . $fromTime . " and location = '";
        $locationsQuery = $locationsQuery . " where timestamp > " . $fromTime;
    }

    foreach ($dbh->query($locationsQuery) as $row) {
        echo "data.addColumn('number', '" . $row[0] . "');\n";
        array_push($locations, $row[0]);
    }

    foreach ($locations as &$value) {
        $q = $dataPointQuery . $value . "'";

        foreach ($dbh->query($q) as $row) {
            $epoch = $row[0];
            $dt = new DateTime("@$epoch");
            if ($row[2] == null) {
                $dataPoints[ $dt->format('Y-m-d H:i') ][$value] = $row[1];
            } else {
                $dataPoints[ $dt->format('Y-m-d H:i') ][$row[2]] = $row[1];
                $dataPoints[ $dt->format('Y-m-d H:i') ][$value] = $row[1];
            }
        }
    }
    ksort($dataPoints);

    #print_r($dataPoints);
    #print_r($locations);
    # k är datum, v är en ARRAY name -> temp, där name kan vara en annotation
    foreach ($dataPoints as $k => $datapoint) {
        echo 'data.addRow(["' . $k . '"';
        $orderOfTemps = array();
        array_push($orderOfTemps, null);
        for ($x = 0; $x < sizeof($locations); $x++) {
          array_push($orderOfTemps, null);
        }
        foreach ($datapoint as $locationOrLabel => $temp) {
            if (in_array($locationOrLabel, $locations)) {
              $indexInArray = array_search("$locationOrLabel",$locations);
              $orderOfTemps[$indexInArray+1] = $temp;
            } else {
              $orderOfTemps[0] = $locationOrLabel;
            }
        }

        $label = $orderOfTemps[0];
        if ($label == null) {
          echo ',null';
        } else {
          echo ',"' . $label . '"';
        }

        for ($x = 1; $x < sizeof($orderOfTemps); $x++) {
          if ($orderOfTemps[$x] == null) {
            echo ',null';
          } else {
            echo ',' . $orderOfTemps[$x];
          }
        }

        echo "]";
        echo ");\n";
    }

    ?>

    var options = {
    curveType: 'function',
    chartArea:{top:40},
    legend: { position: 'bottom' },
    annotations: {
            style: 'line'
        }
    };

    var chart = new google.visualization.LineChart(document.getElementById('curve_chart'));

    chart.draw(data, options);*/
}

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
  <div id="curve_chart"></div>
    <div class="filters">
      <input type="button" value="<<" onclick="executeFilter(-1);"></button>
        <div class="date">
          <input id="dateInput" class="dateInput" type="date" value="new Date().toDateInputValue()">
        </div>
        <input type="button" value=">>" onclick="executeFilter(1);"></button>
    </div>



</div>
