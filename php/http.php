<?php
    $request_url = 'http://' . $_GET['url'];
    $contents = file_get_contents($url);
    echo $contents;
?>
