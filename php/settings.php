
<script>
function setProperty(property, value)
{
    var xmlHttp = new XMLHttpRequest();
    xmlHttp.onreadystatechange = function() {
        if (xmlHttp.readyState == 4) {
            if ( xmlHttp.status != 200) {
                alert("Failed to set setting");
            }
            //alert (xmlHttp.responseText);
            window.location.href = window.location.href;
        }
    }
    xmlHttp.open("GET", "change_setting.php?key=" + property + "&value=" + encodeURIComponent(value), true); // true for asynchronous
    xmlHttp.send(null);
}

</script>

<?php
include("config.php");
?>


<div class="row">
<div class="col-12">

<div id="settings">

<div id="setting">
List of connected device IDs (Example: livingroom,kitchen,garage). Use comma as separator and no funny characters or spaces. The IDs are used in the history graphs.<br/><input type="text" id="devicesText" value=<?php if ($config['devices'] != NULL) { echo '"' . $config['devices'] . '"';} else { echo '""';} ?>
onkeydown="if (event.keyCode == 13) setProperty('devices', '' + document.getElementById('devicesText').value)" ><br>
</div>

<div id="setting">
List of connected device IP addresses (Example: 192.168.0.100,192.168.0.104,192.168.0.105). Use comma as separator and no funny characters or spaces. Make sure the IP addresses are static (configure this in your router settings) <br/><input type="text" id="deviceIPsText" value=<?php if ($config['deviceIPs'] != NULL) { echo '"' . $config['deviceIPs'] . '"';} else { echo '""';} ?>
onkeydown="if (event.keyCode == 13) setProperty('deviceIPs', '' + document.getElementById('deviceIPsText').value)" ><br>
</div>

<div id="setting">
Path to thermometer application home folder (default is /home/pi/temp) <br/><input type="text" id="tempHomeText" value=<?php if ($config['temp_home'] != NULL) { echo '"' . $config['temp_home'] . '"';} else { echo '""';} ?>
onkeydown="if (event.keyCode == 13) setProperty('temp_home', '' + document.getElementById('tempHomeText').value)" ><br>
</div>

<div id="setting">
Breakpoint for expensive hours. Recommended -2 degrees. If temperature goes below thermostat setting minus this value then turn on pump anyway.
<br/><input type="text" id="expensiveBreakpoint" value=<?php if ($config['expensiveBreakpoint'] != NULL) { echo '"' . $config['expensiveBreakpoint'] . '"';} else { echo '""';} ?>
onkeydown="if (event.keyCode == 13) setProperty('expensiveBreakpoint', '' + document.getElementById('expensiveBreakpoint').value)" ><br>
</div>

<div id="setting">
Breakpoint for medium expensive hours. Recommended -1 degrees. If temperature goes below thermostat setting minus this value then turn on pump anyway.
<br/><input type="text" id="mediumExpensiveBreakpoint" value=<?php if ($config['mediumExpensiveBreakpoint'] != NULL) { echo '"' . $config['mediumExpensiveBreakpoint'] . '"';} else { echo '""';} ?>
onkeydown="if (event.keyCode == 13) setProperty('mediumExpensiveBreakpoint', '' + document.getElementById('mediumExpensiveBreakpoint').value)" ><br>
</div>

<div id="setting">
Unit<br>

<input type="radio" name="unit" id="unit" value="kelvin" onclick="setProperty('unit', 'kelvin')" <?php if ($config['unit'] != NULL && $config['unit'] == "kelvin") { echo 'checked';} ?> ><label class="unit" for="kelvin">°K</label><br>
<input type="radio" name="unit" id="unit" value="celsius" onclick="setProperty('unit', 'celsius')" <?php if ($config['unit'] != NULL && $config['unit'] == "celsius") { echo 'checked';} ?> ><label class="unit" for="celsius">°C</label><br>
<input type="radio" name="unit" id="unit" value="fahrenheit" onclick="setProperty('unit', 'fahrenheit')" <?php if ($config['unit'] != NULL && $config['unit'] == "fahrenheit") { echo 'checked';} ?> ><label class="unit" for="fahrenheit">°F</label>

</div>


<br>
Save each field by pressing enter.
</div>

</div>

</div>
