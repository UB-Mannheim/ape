<?php

    $now = getdate();
    print_r($now);
    if ($now['mon'] == 10 && $now['mday'] == 3) {
        echo("Feiertag, kein Magazindruck\n");
        exit(1);
    }

    include ("printJob.class.php");

    $print = new printJob("", "cronMagazindruck");

?>
