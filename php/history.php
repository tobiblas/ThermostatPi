
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
google.charts.load('current', {'packages':['corechart']});
google.charts.setOnLoadCallback(drawChart);

function onLoadFunction() {
  document.addEventListener('DOMContentLoaded', function() {
    let fromDate = getParameterByName('fromDate');
    let toDate = getParameterByName('toDate');
    if (fromDate) {
      document.getElementById("fromDateInput").valueAsNumber = (parseInt(fromDate)* 1000);
    }
    if (toDate) {
      document.getElementById("toDateInput").valueAsNumber = (parseInt(toDate)* 1000);
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

function executeFilter() {
    let fromDate = document.getElementById("fromDateInput").valueAsNumber;
    let toDate = document.getElementById("toDateInput").valueAsNumber;
    if (isNaN(fromDate) || isNaN(toDate)) {
      location.href = URL_add_parameter(location.href, 'period', document.getElementById("periodSelector").value);
    } else{
      let href = URL_add_parameter(location.href, 'fromDate', fromDate/1000);
      location.href = URL_add_parameter(href, 'toDate', toDate/1000);
    }

}

function drawChart() {

    var data = new google.visualization.DataTable();
    data.addColumn('string', 'x');
    data.addColumn({type: 'string', role: 'annotation'});
    <?php
    include("db.php");
    $period = $_GET['period'];
    $fromDate = $_GET['fromDate'];
    $toDate = $_GET['toDate'];
  #  echo $period;
#    echo $fromDate;
#    echo $toDate;

    $location = "";
    $locations = array();
    $locationsQuery = "select distinct location from temperature";

    date_default_timezone_set('GMT');
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
        echo "/*" . $q . "*/\n";
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

    chart.draw(data, options);
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
        <div class="periodfilter">
            <select id="periodSelector">
              <option value="7" <?php echo setSelected(7);?> >1w</option>
              <option value="14" <?php echo setSelected(14);?> >2w</option>
              <option value="30" <?php echo setSelected(30);?> >1m</option>
              <option value="61" <?php echo setSelected(61);?> >2m</option>
              <option value="91" <?php echo setSelected(91);?> >3m</option>
              <option value="183" <?php echo setSelected(183);?> >6m</option>
              <option value="365" <?php echo setSelected(365);?> >1y</option>
              <option value="999" <?php echo setSelected(999);?> >All</option>
            </select>
        </div>
        <div class="fromDate">
          <input id="fromDateInput" class="dateInput" type="date">
        </div>
        -
        <div class="toDate">
          <input id="toDateInput" class="dateInput" type="date">
        </div>
        <input type="button" value="OK" onclick="executeFilter();"></button>
    </div>



</div>
