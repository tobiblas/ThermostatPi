<?php
    
    $config = array();

    $file_handle = fopen("admin.properties", "r");
    while (!feof($file_handle)) {
        $line = fgets($file_handle);
        
        $row_data = explode(':', $line);
        
        $key = trim($row_data[0]);
        $value = trim($row_data[1]);
        
        if (strlen($key) > 0) {
            $config[$key] = $value;
        }
    }
    fclose($file_handle);
?>