
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
Location to use for outdoor temperature (default is "malmo,sweden", other examples are "London", "London,UK" etc.)
<br/><input type="text" id="outdoorLocationText" value=<?php if ($config['outdoorLocation'] != NULL) { echo '"' . $config['outdoorLocation'] . '"';} else { echo '""';} ?>
onkeydown="if (event.keyCode == 13) setProperty('outdoorLocation', '' + document.getElementById('outdoorLocationText').value)" ><br>
</div>

<div id="setting">
Api key for <a href="http://openweathermap.org/">openwathermap.org</a>. If no key is provided then no outdoor temp will be shown or saved
<br/><input type="text" id="apiKeyText" value=<?php if ($config['openweatherApiKey'] != NULL) { echo '"' . $config['openweatherApiKey'] . '"';} else { echo '""';} ?>
onkeydown="if (event.keyCode == 13) setProperty('openweatherApiKey', '' + document.getElementById('apiKeyText').value)" ><br>
</div>

<div id="setting">
Unit<br>

<input type="radio" name="unit" id="unit" value="kelvin" onclick="setProperty('unit', 'kelvin')" <?php if ($config['unit'] != NULL && $config['unit'] == "kelvin") { echo 'checked';} ?> ><label class="unit" for="kelvin">°K</label><br>
<input type="radio" name="unit" id="unit" value="celsius" onclick="setProperty('unit', 'celsius')" <?php if ($config['unit'] != NULL && $config['unit'] == "celsius") { echo 'checked';} ?> ><label class="unit" for="celsius">°C</label><br>
<input type="radio" name="unit" id="unit" value="fahrenheit" onclick="setProperty('unit', 'fahrenheit')" <?php if ($config['unit'] != NULL && $config['unit'] == "fahrenheit") { echo 'checked';} ?> ><label class="unit" for="fahrenheit">°F</label>

</div>

<div id="setting">
Pellet boiler (changes will trigger temp measurement which is saved to database)<br>

<input type="radio" name="pellet" id="pellet" value="on" onclick="setProperty('pellet', 'Pellets på')" <?php if ($config['pellet'] != NULL && $config['pellet'] == "Pellets på") { echo 'checked';} ?> ><label class="pellet" for="on">ON</label><br>
<input type="radio" name="pellet" id="pellet" value="off" onclick="setProperty('pellet', 'Pellets av')" <?php if ($config['pellet'] != NULL && $config['pellet'] == "Pellets av") { echo 'checked';} ?> ><label class="pellet" for="off">OFF</label><br>

</div>

<br>
Save each field by pressing enter.
</div>

</div>

</div>
