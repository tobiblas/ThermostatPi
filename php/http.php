<?php
    $request_url = 'http://' . $_GET['url'];
    $contents = file_get_contents($request_url);
    echo $contents;
?>
