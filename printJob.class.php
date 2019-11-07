<?php

class printJob
{
    // Set Variables
    private $__CFG__;
    private $__PATH__;
    private $__LOG__;
    private $__FILE__;
    private $__MAIL__;
    private $__PARSER__;

    function __construct()
    {
        //
        // Call:    Without Parameter = Standard
        //          Stream Input

        // Switch: Constructors with multiple Parameters
        $a = func_get_args();
        $i = func_num_args();

        // Call Constructor by Parameter Count
        if (method_exists($this, $f = '__construct'.$i)) {
            call_user_func_array(array($this,$f), $a);
        } else {
            // START (Standard)

            // Load Config
            $this->getConfig();
            $this->__MAIL__ = true;

            // Read StreamInput
            $content = $this->streamInput();

            $this->getContent($content);
        }
    }

    function __construct1($filename)
    {
        //
        // Call:    With 1 Parameter = filename
        //          File Input

        // Load Config
        $this->getConfig();
        $this->__MAIL__ = false;

         // Load File Content
        $content = $this->fileInput($filename);

        $this->getContent($content);
    }

    function __construct2($cron, $job)
    {
        //
        // Call:    With 2 Parameters = cronjob
        //          Activated by Cron

        $this->getConfig();

        // No Queue assigned -> ""
        if ($job=="cronMagazindruck") {
            $this->printByNow($cron, $job, "magazin");
        }
        if ($job=="cronScanauftrag") {
            $this->printByNow($cron, $job, "scanauftrag");
        }
    }

    protected function getConfig()
    {
        //
        // Read Configuration
        //

        // Initialize Variables
        $this->__CFG__ = parse_ini_file("print.conf", true);
        $this->__PATH__ = $this->__CFG__["common"]["root"];
        $this->__LOG__ = $this->__CFG__["common"]["log"];

        // Include Mailparser Library
        include_once $this->__CFG__["lib"]["mailparser"];
        $this->__PARSER__ = new PhpMimeMailParser\Parser();
    }

    protected function writeLog($msg)
    {
        //
        // Logwriter
        //

        $log = $this->__CFG__["common"]["log"];

        $fdw = fopen($log, "a+");
            fwrite($fdw, $msg . "\n");
        fclose($fdw);
    }

    protected function streamInput()
    {
        //
        // Reading StreamInput
        //
        // print "STREAM IN";

        $this->writeLog("--- READING MAIL ---");

        $this->__PARSER__->setStream(fopen("php://stdin", "r"));

            $to = $this->__PARSER__->getHeader('to');
            $from = $this->__PARSER__->getHeader('from');
            $subject = $this->__PARSER__->getHeader('subject');

            $text = $this->__PARSER__->getMessageBody('text');
            $html = $this->__PARSER__->getMessageBody('html');
            $htmlEmbedded = $this->__PARSER__->getMessageBody('htmlEmbedded'); //HTML Body included data

        // Return plain Text, no Headers, etc.
        return $htmlEmbedded;
    }

    protected function fileInput($filename)
    {
        //
        // Reading FileInput
        //

        // if path = "alma_print" STANDARD as configured
        // $this->__FILE__ = $this->__PATH__.$filename;

        // if path = "Maildir", static path because it is NO REGULAR  action
        // 2do: add input file path to parameter in init.php
        $this->__FILE__ = "/home/mailuser/Maildir/new/".$filename;

        print "FILE: " . $this->__FILE__ ."\r\n";

        if (file_exists($this->__FILE__)) {
            // print("DEBUG: source file: ".$__FILE__);

            $this->writeLog("--- READING LOCAL FILE ---");
            // $this->writeLog("source file: ".$__FILE__);

            $localfile = fopen($this->__FILE__, "r");
            $email = "";

            // Read File Content
            while (!feof($localfile)) {
                $email .= fgets($localfile, 1024);
                // $this->writeLog($email);
            }

            // Close File
            fclose($localfile);

            // tmp BUGFIX
            // print($email);

            // Return plain Text
            return $email;
        } else {
            print ("File " . $this->__FILE__ . " not found.");
        }
    }

    protected function getContent($email)
    {
        // Retrieve all necessary Information for creating a Job and
        // assigning the right Queue

        // Date
        $date_rfc = date(DATE_RFC822);
        $date = date("Y-m-d_H-i-s");

        // Unique ID
        $uid = uniqid();
        $udate = $date."__".$uid;

        // Get Information from HTML
        $printjob = array();

        // TODO: hier sollten alle Arrayelemente vorbelegt werden,
        // damit $printjob vollständig definiert ist.

        if (preg_match_all('|<h2 id="print_type">(.*)</h2>|U', $email, $type)) {
            $printjob["type"] = $type[1][0];
            $this->writeLog($printjob["type"]."\r\n");
        }
        if (preg_match_all('|<h2 id="print_library">(.*)</h2>|U', $email, $library)) {
            $printjob["library"] = $library[1][0];
            $this->writeLog($printjob["library"]."\r\n");
        }
        if (preg_match_all('|<h2 id="print_callnumber">(.*)</h2>|U', $email, $callnumber)) {
            $printjob["callnumber"] = $callnumber[1][0];
            $this->writeLog($printjob["callnumber"]."\r\n");
        }
        if (preg_match_all('|<h2 id="print_level">(.*)</h2>|U', $email, $level)) {
            $printjob["level"] = $level[1][0];
            $this->writeLog($printjob["level"]."\r\n");
        }

        // TODO:
        // PHP Notice:  Undefined index: callnumber in /home/mailuser/alma_print/printJob.class.php on line 193

        $name = $printjob["type"]."__".$printjob["library"]."__".$printjob["level"]."__".$printjob["callnumber"];
        $name = preg_replace('/\s+/', '_', $name);
        $name = preg_replace('/\,/', '', $name);
        // changing signature: "/" to "-"
        $name = preg_replace('/\//', '-', $name);

            // DEBUG
            // print "\r\n--------------------------------------------- \r\n";
            // print $name ."\r\n";
            // print "--------------------------------------------- \r\n";

            // print "QUEUE \t\t::".$printjob["type"]."\r\n";
            // print "SECTION \t::".$printjob["library"]."\r\n";
            // print "FLOOR \t\t::".$printjob["level"]."\r\n";
            // print "SIGNATURE \t::".$printjob["callnumber"]."\r\n";

        // Setting Filenames (html/pdf)
        $filename = $this->__CFG__["common"]["tmp"].$name."____incoming__".$udate.".html";
        $pdf = $this->__CFG__["common"]["tmp"].$name."____pdf__".$udate.".pdf";
        $this->writeLog("-- writing html file: ".$filename);

        // Create File
        $fdw = fopen($filename, "w+");
        fwrite($fdw, $email);
        fclose($fdw);
        $this->writeLog("-- file: ".$filename." written");

        // Convert HTML to PDF
        $this->writeLog("-- Create PDF: ".$pdf);

        // Quoting HTML- & PDF-Filename for conversion
        $q_filename = quotemeta($filename);
        $q_pdf = quotemeta($pdf);
        $convert_cmd = "/usr/local/bin/wkhtmltopdf -q ".$q_filename." ".$q_pdf;
        // $this->writeLog("-- ". $convert_cmd);
        shell_exec($convert_cmd);

        // File Creation Successful?
        if (file_exists($pdf)) {
            $this->writeLog("-- file: ".$pdf." successfully created");
        } else {
            $this->writeLog("-- file: ".$pdf." not found");
        }

        // Uncomment if HTML files should be deleted after conversion
        // unlink($filename);

        // NOT RECOMMENDED: Uncomment to delete PDF files before printing
        // unlink($pdf);

        $this->writeLog("--- END ---");

        // Process Print Job
        // $this->processPrint($printjob["type"], $printjob["library"], $pdf);
        $this->processPrint(
            $printjob["type"],
            $printjob["library"],
            $printjob["level"],
            $pdf
        );

        // Local Test (no PDF Generator)
        // $this->processPrint($printjob["type"], $printjob["library"], $filename);
    }

    protected function printByFloor($file, $floor, $queue)
    {
        // WESTFLUEGEL
        if ($floor=="Westfluegel" || $floor=="Untergeschoss"
            || $floor=="Erdgeschoss" || $floor=="Galerie"
        ) {
            $this->sendToQueue($queue, "WEST", $file);
        } else {
            // MAGAZIN SW
            $this->sendToQueue($queue, "SW", $file);
        }
    }

    protected function processPrint($type, $section, $floor, $file)
    {
        $queue = "";

        // Get Type from HTML Content of Email
        switch ($type) {
            case "ruecklagezettel":
                $queue = "ruecklage";
                break;
            case "magazinbestellung":
                $queue = "magazin";
                break;
            case "scanauftrag":
                $queue = "scanauftrag";
                break;
            case "quittung":
                $queue = "quittung";
                break;
            case "mahnung":
                $queue = "mahnung";
                break;
            case "bestellliste":
                $queue = "medienbearb";
                break;
            case "erwerbungsstornierung":
                $queue = "medienbearb";
                break;
            case "erwerbungsmahnung":
                $queue = "medienbearb";
                break;
            case "fernleihe":
                $queue = "fernleihe";
                break;
            case "eingangsbeleg":
                $queue = "eingangsbeleg";
                break;
            default:
                // Printer "Ausleihtheke"
                $queue = "fallback";
        }

        // RUECKLAGEZETTEL
        if ($queue=="ruecklage") {
            switch ($section) {
                case "BB Schloss Schneckenhof, West":
                    $this->printByNow($this->__CFG__["printer"]["printer38"], $file, $queue);
                    // sendToQueue("magazin", "BSE", $file);
                    break;
                case "BB A3":
                    $this->printByNow($this->__CFG__["printer"]["printer10"], $file, $queue);
                    break;
                case "BB A5":
                    $this->printByNow($this->__CFG__["printer"]["printer46"], $file, $queue);
                    break;
                case "BB Schloss Schneckenhof, BWL":
                    $this->printByNow($this->__CFG__["printer"]["printer38"], $file, $queue);
                    break;
                case "BB Schloss Ehrenhof":
                    $this->printByNow($this->__CFG__["printer"]["printer48"], $file, $queue);
                    break;
                case "Ausleihzentrum_Westfluegel":
                    // $this->printByNow($this->__CFG__["printer"]["printer52"], $file, $queue);
                    $this->printByNow($this->__CFG__["printer"]["printer52_DINA5"], $file, $queue);
                    break;
                case "MZES":
                    $this->printByNow($this->__CFG__["printer"]["printer46"], $file, $queue);
                    break;
                default:
                    $this->printByNow($this->__CFG__["printer"]["printer08"], $file, $queue);
            }
        }

        // MAGAZINDRUCK + SCANAUFTRAG
        if (($queue=="magazin") || ($queue=="scanauftrag")) {
            // $this->sendToQueue("magazin", "", $file);
            switch ($section) {
                case "BB Schloss Schneckenhof, West":
                    $this->sendToQueue($queue, "SW", $file);
                    break;
                case "BB A3":
                    $this->sendToQueue($queue, "A3", $file);
                    break;
                case "BB A5":
                    $this->sendToQueue($queue, "A5", $file);
                    break;
                case "BB Schloss Schneckenhof, BWL":
                    $this->sendToQueue($queue, "BWL", $file);
                    break;
                case "BB Schloss Ehrenhof":
                    $this->sendToQueue($queue, "BSE", $file);
                    break;
                case "Ausleihzentrum_Westfluegel":
                    $this->printByFloor($file, $floor, $queue);
                    // if (UG, EG, Galerie) sendToQueue("Westf")
                    // else (Stock_01 - 11) sendToQueue ("SW")
                    break;
                case "MZES":
                    // Send to Queue A5 (same  printer)
                    $this->sendToQueue($queue, "A5", $file);
                    break;
            //
            // Sonderfaelle IMGB, Accounting, Binnenschiffahrt
                case "BB Schloss Ehrenhof - IMGB":
                    $this->sendToQueue($queue, "BSE", $file);
                    break;
                case "Bibl. f. Accounting u. Taxation":
                    $this->sendToQueue($queue, "BSE", $file);
                    break;
                case "Binnenschifffahrtsrecht, Bibl.":
                    $this->sendToQueue($queue, "BSE", $file);
                    break;
                default:
                    $this->sendToQueue($queue, "", $file);
            }
        }

        // MEDIENBEARBEITUNG (Bestellliste, Erwerbungsstornierung, Erwerbungsmahnung)
        if ($queue=="medienbearb") {
            switch ($section) {
                case "BB Schloss Schneckenhof, West":
                    $this->printByNow($this->__CFG__["printer"]["printer09"], $file, $queue);
                    break;
                case "BB A3":
                    $this->printByNow($this->__CFG__["printer"]["konicaA3"], $file, $queue);
                    break;
                case "BB A5":
                    $this->printByNow($this->__CFG__["printer"]["konicaA5"], $file, $queue);
                    break;
                case "BB Schloss Schneckenhof, BWL":
                    $this->printByNow($this->__CFG__["printer"]["printer19"], $file, $queue);
                    break;
                case "BB Schloss Ehrenhof":
                    $this->printByNow($this->__CFG__["printer"]["printer20"], $file, $queue);
                    break;
                default:
                    $this->printByNow($this->__CFG__["printer"]["printer08"], $file, $queue);
            }
        }

        // QUITTUNGSDRUCK, 3.MAHNUNG & "FALLBACK"
        if (($queue=="quittung") || ($queue=="mahnung") || ($queue=="fallback")) {
            $this->printByNow($this->__CFG__["printer"]["printer08"], $file, $queue);
        }

        if ($queue=="fernleihe") {
            $this->printByNow($this->__CFG__["printer"]["repro"], $file, $queue);
        }

        if ($queue=="eingangsbeleg") {
            $this->printByNow($this->__CFG__["printer"]["magazin"], $file, $queue);
        }
    }

    protected function printByNow($printer, $file, $queue)
    {
        //
        // Direct Printing or Printing via Cronjob
        //

        // Date
        $date_rfc = date(DATE_RFC822);
        $date = date("Y-m-d");

        /// /// /// /// --- /// /// /// ///
        // 2do $this->__CFG__["queue"]["magazin"] durch $this->__CFG__["queue"][$queue] ersetzen

        // Is Cronjob?
        if (($file=="cronMagazindruck") || ($file=="cronScanauftrag")) {
            $printer = "";

            // Cron: Magazindruck
            if ($file=="cronMagazindruck") {
                $dir = $this->__CFG__["queue"]["magazin"];
                print "cronMagazindruck\r\n";
                $this->writeLog("Jobtype: cronMagazindruck\r\n");
            }

            // Cron: Scanauftrag
            if ($file=="cronScanauftrag") {
                $dir = $this->__CFG__["queue"]["scanauftrag"];
                print "cronScanauftrag\r\n";
                $this->writeLog("Jobtype: cronScanauftrag\r\n");
            }

            // bis hier
            /// /// /// /// --- /// /// /// ///

            $files = array_diff(scandir($dir), array('..', '.'));

            foreach ($files as $f) {
                if (is_dir($dir."/".$f)) {
                    $subdir = array_diff(scandir($dir."/".$f), array('..', '.'));

                    foreach ($subdir as $s) {
                        $print_cmd = "";
                        switch ($f) {
                            case "A3":
                                $printer = $this->__CFG__["printer"]["printer50"];
                                break;
                            case "A5":
                                $printer = $this->__CFG__["printer"]["konicaA5"];
                                break;
                            case "BWL":
                                $printer = $this->__CFG__["printer"]["printer38"];
                                break;
                            case "BSE":
                                $printer = $this->__CFG__["printer"]["printer21"];
                                break;
                            case "SW":
                                if ($queue=="magazin") {
                                    $printer = $this->__CFG__["printer"]["magazin"];
                                } else {
                                    $printer = $this->__CFG__["printer"]["printer38"];
                                }
                                break;
                            case "WEST": // A5 bei Magazindruck
                                if ($queue=="magazin") {
                                    $printer = $this->__CFG__["printer"]["printer52_DINA5"];
                                } else {
                                    $printer = $this->__CFG__["printer"]["printer52"];
                                }
                                break;
                            default:
                                $printer = $this->__CFG__["printer"]["printer08"];
                        }

                        if ($s != "dummy") {
                            $print_cmd = "lp -o fit-to-page -d " .$printer. " " .$dir.$f."/".quotemeta($s);
                            // $print_cmd = "lp -o fit-to-page -o sides=two-sided-long-edge -d " .$printer. " " .$dir.$f."/".quotemeta($s);
                            $this->writeLog("\r\n Printing on queue: ".$queue. " with command: " .$print_cmd);
                            shell_exec($print_cmd);
                            if ($printer=="printer52") {
                                $print_debug_cmd = "lp -o fit-to-page -d Kyocera_ECOSYS_M2530dn " .$dir.$f."/".quotemeta($s);
                                // $print_debug_cmd = "lp -o fit-to-page -o sides=two-sided-long-edge -d Kyocera_ECOSYS_M2530dn " .$dir.$f."/".quotemeta($s);
                                shell_exec($print_debug_cmd);
                            }

                            $h_dir = basename($dir);    // print ($dir) . "\r\n";   // dir
                            $h_subdir = $f;             // print ($f) . "\r\n";     // subdir
                            $h_file = $s;               // print ($s) . "\r\n";     // file

                            // Move to History Directory
                            if (!file_exists($this->__CFG__["common"]["history"].$h_dir."/".$date)) {
                                mkdir($this->__CFG__["common"]["history"].$h_dir."/".$date, 0777, true);
                            }
                            $movedFile = basename($h_file);
                            rename($dir.$f."/".$s, "/home/mailuser/alma_print/history/".$h_dir."/".$date."/".$movedFile);
                        }
                    }
                } else {
                    // Print Jobs in ROOT Directory
                    if ($f != "dummy") {
                        $printer = $this->__CFG__["printer"]["printer08"];
                        // DEBUG
                        // $printer = "PRINTER08_SW";
                        $print_cmd = "lp -o fit-to-page -d " .$printer. " " .$dir.quotemeta($f);
                        // $print_cmd = "lp -o fit-to-page -o sides=two-sided-long-edge -d " .$printer. " " .$dir.quotemeta($f);
                        shell_exec($print_cmd);
                        // DEBUG
                        // echo $print_cmd . "\r\n";

                        $h_dir = basename($dir);
                        $h_file = $f;

                        // Move to History Directory
                        if (!file_exists($this->__CFG__["common"]["history"].$h_dir."/".$date)) {
                            mkdir($this->__CFG__["common"]["history"].$h_dir."/".$date, 0777, true);
                        }
                        // DEBUG
                        // if (!file_exists($history_dir.$h_dir."/".$date)) {
                            // // mkdir($history_dir.$h_dir."/".$date, 0777, true);
                            // echo "creating dir: " . $history_dir.$h_dir."/".$date;

                        // }
                        $movedFile = basename($h_file);
                        rename($dir.$f, "/home/mailuser/alma_print/history/".$h_dir."/".$date."/".$movedFile);
                        // DEBUG
                        // echo "renaming from: ". $dir.$f . " to " . "/home/mailuser/alma_print/history/".$h_dir."/".$date."/TEST_".$movedFile;
                        //echo "\r\n\r\n";
                    }
                    // end print in ROOT
                }
            }
        } else {
            // No Cronjob, called directly from processPrint()

            $this->writeLog("-- start printing: ".$file);

            $print_cmd = "lp -o fit-to-page -d " .$printer. " " .quotemeta($file);
            // $print_cmd = "lp -o fit-to-page -o sides=two-sided-long-edge -d " .$printer. " " .$file;

            $this->writeLog("-- ". $print_cmd);

            shell_exec($print_cmd);
            print $print_cmd;

            // Move to History Directory /direct/
            if (!file_exists($this->__CFG__["common"]["history"].$queue."/".$date)) {
                mkdir($this->__CFG__["common"]["history"].$queue."/".$date, 0777, true);
            }

            $movedFile = basename($file);
            rename($file, $this->__CFG__["common"]["history"].$queue."/".$date."/".$movedFile);
        }
    }

    protected function sendToQueue($queue, $section, $file)
    {
        //
        // Copy File to Queue
        //

        $cp_cmd = "cp \"".$file."\" \"".$this->__CFG__["queue"][$queue]."\"/".$section;

        // Local Test (Slashes)
        // $cp_cmd = "copy \"".$file."\" \"".$this->__CFG__["queue"][$queue]."\"\".$section;

        // print($cp_cmd);
        shell_exec($cp_cmd);
    }
}
