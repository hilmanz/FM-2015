<?php
    $now = gmdate("D, d M Y H:i:s");
    header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
    header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
    header("Last-Modified: {$now} GMT");

    // force download  
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");

    // disposition / encoding on response body
    header("Content-Disposition: attachment;filename={$filename}");
    header("Content-Transfer-Encoding: binary");
    
    // File: /app/views/orders/csv/export.ctp
   
    // Loop through the data array
    //echo $form->create('Order',array('url'=>'/orders/export/orders_'.date("Ymd").'.csv')); 
    foreach ($data as $row)
    {
        // Loop through every value in a row
        foreach ($row['merchandise_orders'] as &$value)
        {
            // Apply opening and closing text delimiters to every value
            $value = "\"".$value."\"";
        }
        // Echo all values in a row comma separated
        echo implode(",",$row['merchandise_orders'])."\n";
    }
?> 