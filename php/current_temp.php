
<?php
    $command = '/home/pi/thermometer/sense_temp.py nosave';
    echo exec($command . ' 2>&1');
?>