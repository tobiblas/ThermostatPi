<?php
    $dir = 'sqlite:/Users/tobiblas/Sites/ThermometerPi/ThermometerPi/temperature.db';
    #$dir = 'sqlite:/home/pi/temperature.db';
    
    try {
        $dbh  = new PDO($dir) or die("cannot open the database");
    } catch (PDOException $e) {
        print "Error!: " . $e->getMessage() . "<br/>";
        die();
    }
?>
