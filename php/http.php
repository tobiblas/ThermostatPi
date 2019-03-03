<?php

    $request_url = 'http://' . $_GET['url'];
    $contents = file_get_contents($url);
    if($contents !== false){
      //Print out the contents.
      echo $contents;
    } else {
      die('Could not fetch ' + $contents);
    }
?>
