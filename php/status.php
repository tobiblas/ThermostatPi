
<script>

function setThermostat(value) {
  var xmlHttp = new XMLHttpRequest();
  xmlHttp.onreadystatechange = function() {
      if (xmlHttp.readyState == 4) {
          if ( xmlHttp.status != 200) {
              alert("Failed to set thermostat");
          }
        //  window.location.href = window.location.href;
      }
  }
  xmlHttp.open("GET", "change_setting.php?key=targetTemp&value=" + value, true); // true for asynchronous
  xmlHttp.send(null);
}

function fetchTempFromRaspberryPi(IP, idOfTempElement, unit) {

    var request = new XMLHttpRequest();
    var requestStr = "http.php?url=" + IP + "/thermometer/current_temp.php";
    request.open('GET', requestStr, true);

    request.onload = function() {
        if (request.status >= 200 && request.status < 400) {
            // Success!
            var temp = parseFloat(request.responseText);
            //alert (temp);
            //Felhantering här
            var theElement = document.getElementById(idOfTempElement);

            tempStr = "";
            if (unit === "kelvin") {
                tempStr = (Math.round(temp * 10) / 10) + " °K"
            } else if (unit === "fahrenheit") {
                var num = (1.8 * (temp - 273.15) + 32);
                tempStr =  (Math.round(num * 10) / 10) + " °F";
            } else {
                var num = temp - 273.15;
                tempStr = "" + (Math.round(num * 10) / 10) + " °C";
            }

            theElement.innerHTML = tempStr;
            if ($('.loader').length === 1) {
              let sum = 0.0;
              let count = 0.0;
              $(".temp").each(function() {
                let value = this.innerHTML;
                var temperature = value.substr(0, value.indexOf(' '));
                var floatTemp = parseFloat(temperature);
                if (!isNaN(floatTemp)) {
                  sum += floatTemp;
                  count++;
                }
              });
              if (count === 0) {
                $('#averageTemp').html(");");
              } else {
                let average = sum / count;
                $('#averageTemp').html("" + (Math.round( average * 10 ) / 10) + " °C");
              }
            }
        } else {
            // We reached our target server, but it returned an error
            alert ("error for IP " + IP + ": " + request.responseText);
            var theElement = document.getElementById(idOfTempElement);
            theElement.innerHTML = "Error";
        }
    };

    request.onerror = function() {
        alert("There was a connection error of some sort for " + IP);
         alert ("error for IP " + IP + ": " + request.responseText);
        var theElement = document.getElementById(idOfTempElement);
        theElement.innerHTML = "Error";
    };

    request.send();

}

<?php
    include("config.php");
?>

</script>

<div class="thermostat">
<p></p>
<input class="knob" data-width="60%"
data-fgcolor="#CC7070"
data-angleArc=250
data-min="10"
data-max="30"
data-angleOffset=-125
data-step=".1"
value="<?php echo $config['targetTemp']?>">
</div>
<script>
    $(function() {
        $(".knob").knob();
    });
    $(".knob").knob({
        'release' : function (v) {
          setThermostat(v);
        }
    });
</script>

<?php
    if ($config['deviceIPs'] != null && $config['devices'] != null && trim($config['devices']) != '' && trim($config['deviceIPs']) != '') {

        $IPs = explode(',', $config['deviceIPs']);
        $devices = explode(',', $config['devices']);
        foreach($IPs as $index => $IP) {
            $IP = trim($IP);
            echo '<div class="row">' . PHP_EOL;
            echo '<div class="col-6">' . PHP_EOL;
            echo '<div class="temperaturelocation">' . PHP_EOL;
            echo $devices[$index] . PHP_EOL;
            echo '</div>' . PHP_EOL;
            echo '</div>' . PHP_EOL;
            echo '<div class="col-6">' . PHP_EOL;
            echo '<div class="temperature temp" id="'.$devices[$index].'">' . PHP_EOL;
            echo '<img class="loader" src="images/loading-big.gif" height="40" /></div>' . PHP_EOL;
            echo '</div>' . PHP_EOL;
            echo '</div>' . PHP_EOL;
            echo '<script>fetchTempFromRaspberryPi("'. $IP .'", "' .$devices[$index] . '", "' . $config['unit'] .'");</script>' . PHP_EOL;
        }
    } else {
        echo "No configured devices";
    }
?>

<div class="row">
  <div class="col-6">
    <div class="temperaturelocation">Average</div>
  </div>
<div class="col-6">
  <div class="temperature" id="averageTemp">
    <img class="loader" src="images/loading-big.gif" height="40" /></div>
  </div>
</div>
