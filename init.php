#!/usr/bin/php -q
<?php

    require "printJob.class.php";

if (isset($argv[1])) {
    // Aufruf mit Datei
    $print = new printJob($argv[1]);
} else {
    // Aufruf mit STDIN
    $print = new printJob();
}

?>
