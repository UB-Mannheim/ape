<?php

class printJob {

    // Set Variables
    private $__CFG__;
    private $__PATH__;
    private $__LOG__;
    private $__FILE__;
    private $__MAIL__;
    private $__PARSER__;

    function __construct() {
    //
    // call:    without parameter = standard
    //          stream input

        // switch: constructors with multiple parameters
        $a = func_get_args();
        $i = func_num_args();

        // call constructor by parameter count
        if (method_exists($this,$f='__construct'.$i)) {
            call_user_func_array(array($this,$f),$a);
        } else {

        // START (standard)

            // Load Config
            $this->getConfig();
            $this->__MAIL__ = true;

            // Read StreamInput
            $content = $this->streamInput();

            $this->getContent($content);

        }
    }

    function __construct1($filename) {
    //
    // call:    with 1 parameter = filename
    //          file input

        // Load Config
        $this->getConfig();
        $this->__MAIL__ = false;

         // Load File Content
        $content = $this->fileInput($filename);

        $this->getContent($content);
    }

    function __construct2($cron, $job) {
    //
    // call:    with 2 parameters = cronjob
    //          activaed by cron

        $this->getConfig();

        // no queue assigned -> ""
        if($job=="cronMagazindruck") {
            $this->printByNow($cron, $job, "magazin");
        }
        if($job=="cronScanauftrag") {
            $this->printByNow($cron, $job, "scanauftrag");
        }


    }

    protected function getConfig() {
    //
    // Read Configuration
    //

        // Initialize Variables
        $this->__CFG__ = parse_ini_file("print.conf", TRUE);
        $this->__PATH__ = $this->__CFG__["common"]["root"];
        $this->__LOG__ = $this->__CFG__["common"]["log"];

        // Include Mailparser Library
        require_once $this->__CFG__["lib"]["mailparser"];
        $this->__PARSER__ = new PhpMimeMailParser\Parser();

    }

    protected function writeLog($msg) {
    //
    // Logwriter
    //

        $log = $this->__CFG__["common"]["log"];

        $fdw = fopen($log, "a+");
            fwrite($fdw, $msg . "\n");
        fclose($fdw);

    }

    protected function streamInput() {
    //
    // Reading StreamInput
    //
    print "STREAM IN";

        $this->writeLog("--- READING MAIL ---");

        $this->__PARSER__->setStream(fopen("php://stdin", "r"));

            $email = ""; // zu pruefen, ob weiterhin benoetigt
            $to = $this->__PARSER__->getHeader('to');
            $from = $this->__PARSER__->getHeader('from');
            $subject = $this->__PARSER__->getHeader('subject');

            $text = $this->__PARSER__->getMessageBody('text');
            $html = $this->__PARSER__->getMessageBody('html');
            $htmlEmbedded = $this->__PARSER__->getMessageBody('htmlEmbedded'); //HTML Body included data
        /*
        // Mailobobjekt erstellen?
        include ("Mail.class.php");
        $mail = new Mail();
        $mail->setContent($email, $to, $from, $subject, $text, $html, $htmlEmbedded);

        $this->__MAIL__ = true;

        $this->writeLog("-- Html-Text: " .$html);
        $this->writeLog("-- Html-Text (Data): " .$htmlEmbedded);

        return $mail;
    */

        // return plain text, no headers, etc.
        return $htmlEmbedded;

    }

    protected function fileInput($filename) {
    //
    // Reading FileInput
    //

        $this->__FILE__ = $this->__PATH__.$filename;
        print "FILE: " . $this->__FILE__ ."\r\n";

        if (file_exists($this->__FILE__)) {

            $this->writeLog("--- READING LOCAL FILE ---");
            // $this->writeLog("source file: ".$__FILE__);

            $localfile = fopen($this->__FILE__, "r");
            $email = "";

            // read file content
            while(!feof($localfile))
            {
                $email .= fgets($localfile,1024);
                // $this->writeLog($email);
                }

            // close file
            fclose($localfile);
            /*
            $this->__PARSER__->setText($email);

                $email = ""; // zu pruefen, ob weiterhin benoetigt
                $to = $this->__PARSER__->getHeader('to');
                $from = $this->__PARSER__->getHeader('from');
                $subject = $this->__PARSER__->getHeader('subject');

                $text = $this->__PARSER__->getMessageBody('text');
                $html = $this->__PARSER__->getMessageBody('html');
                $htmlEmbedded = $this->__PARSER__->getMessageBody('htmlEmbedded'); //HTML Body included data

            // Mailobobjekt erstellen
            include ("Mail.class.php");
            $mail = new Mail();
            $mail->setContent($email, $to, $from, $subject, $text, $html, $htmlEmbedded);

            $this->__MAIL__ = false;

            $this->writeLog($email);

            return $mail;
            */

            // return plain text
            return $email;
        }
    }

    protected function getContent($email) {

        // retrieve all necessary information for creating a job and assigning the right queue

        // Date
        $date_rfc = date(DATE_RFC822);
        $date = date("Y-m-d_H-i-s");

        // Unique ID
        $uid = uniqid();
        $udate = $date."__".$uid;

///////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////

// alter Block
$printer = "";
$to = "kyocera@mail.bib.uni-mannheim.de"; // tmp

        switch($to) {

            case "kyocera@mail.bib.uni-mannheim.de": $printer = "Kyocera_ECOSYS_M2530dn";
                break;
            case "konica@mail.bib.uni-mannheim.de": $printer = "KONICA_MINOLTA_C360";
                break;
            case "epson@mail.bib.uni-mannheim.de": $printer = "T88V";
                break;
            default: $printer = "Kyocera_ECOSYS_M2530dn";
        }

        // $mailstr = "New mail received at " .$printer. " :" .$date_rfc. "\nSubject: " .$subject. "\nTo: " .$to. "\nFrom :" .$from. "\nText: \n" .$htmlEmbedded;

        // print " --- \n" . $email;





        // Get Information from HTML
        $printjob = array();
        // if (preg_match_all('#<h2>(?:.*?)</h2>#is', $email, $matches)) {
        if (preg_match_all('|<h2 id="print_type">(.*)</h2>|U', $email, $type)) {
            $printjob["type"] = $type[1][0];
        }
        if (preg_match_all('|<h2 id="print_library">(.*)</h2>|U', $email, $library)) {
            $printjob["library"] = $library[1][0];
        }
        if (preg_match_all('|<h2 id="print_callnumber">(.*)</h2>|U', $email, $callnumber)) {
            $printjob["callnumber"] = $callnumber[1][0];
        }
        if (preg_match_all('|<h2 id="print_level">(.*)</h2>|U', $email, $level)) {
            $printjob["level"] = $level[1][0];
        }

        $name = $printjob["type"]."__".$printjob["library"]."__".$printjob["level"]."__".$printjob["callnumber"];
        $name = preg_replace('/\s+/', '_', $name);
        $name = preg_replace('/\,/', '', $name);

        // posssibliy outdated ...
        $printjob["name"] = $name;
        //

        print "\r\n------------------------------------------------------- \r\n";
        print $name ."\r\n";
        print "------------------------------------------------------- \r\n";


        print "QUEUE \t\t::".$printjob["type"]."\r\n";
        // copy to magazin|scan oder direktdruck

        print "SECTION \t::".$printjob["library"]."\r\n";
        // copy to magazin

        print "FLOOR \t\t::".$printjob["level"]."\r\n";
        // copy to magazin

        print "SIGNATURE \t::".$printjob["callnumber"]."\r\n";
        // copy to magazin

///////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////

        // bisher weitgehend unbearbeitet
        $filename = $this->__CFG__["common"]["tmp"].$name."____incoming__".$udate.".html";
        $pdf = $this->__CFG__["common"]["tmp"].$name."____pdf__".$udate.".pdf";
        $this->writeLog("-- writing html file: ".$filename);

        $fdw = fopen($filename, "w+");
        // Embedded Html Only
        fwrite($fdw, $email);
        // fwrite($fdw, $htmlEmbedded);

        // old self-generated "header"
        // fwrite($fdw, $mailstr);

        // all information from stdin
        // fwrite($fdw, $email);

        fclose($fdw);

        $this->writeLog("-- file: ".$filename." written");

        // Convert HTML to PDF
        $this->writeLog("-- Create PDF: ".$pdf);

        // quoting filename & pdfname for conversion
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

        // check if files should be deleted?
        // unlink($filename);
        // unlink($pdf);

        $this->writeLog("--- END ---");

        // Process Print Job
        // $this->processPrint($printjob["type"], $printjob["library"], $pdf);
        $this->processPrint($printjob["type"], $printjob["library"], $printjob["level"], $pdf);

        // local test (no pdf generator)
        // $this->processPrint($printjob["type"], $printjob["library"], $filename);

    }

    protected function printByFloor($file, $floor, $queue) {

        // WESTFLUEGEL
            if($floor=="Westfluegel" || $floor=="Untergeschoss" || $floor=="Erdgeschoss" || $floor=="Galerie") {
                $this->sendToQueue($queue, "WEST", $file);
            } else {
        // MAGAZIN SW
                $this->sendToQueue($queue, "SW", $file);
            }

    }

    // protected function processPrint($type, $section, $file) {
    protected function processPrint($type, $section, $floor, $file) {
        $queue = "";

        // get type from html content of email
        switch($type) {
            case "ruecklagezettel": $queue = "ruecklage";
            break;
            case "magazinbestellung": $queue = "magazin";
            break;
            case "scanauftrag": $queue = "scanauftrag";
            break;
            case "quittung": $queue = "quittung";
            break;
            case "mahnung": $queue = "mahnung";
            break;
            case "bestellliste": $queue = "medienbearb";
            break;
            case "erwerbungsstornierung": $queue = "medienbearb";
            break;
            case "erwerbungsmahnung": $queue = "medienbearb";
            break;
            case "fernleihe": $queue = "fernleihe";
            break;
            case "eingangsbeleg": $queue = "eingangsbeleg";
            break;
            default:
                // Printer "Ausleitheke"
                $queue = "fallback";
        }

        // RUECKLAGE ZETTEL
        if($queue=="ruecklage") {
            switch($section) {
                case "BB Schloss Schneckenhof, West":
                    $this->printByNow($this->__CFG__["printer"]["printer08"], $file, $queue);
                    // sendToQueue("magazin", "BSE", $file);
                    break;
                case "BB A3":
                    $this->printByNow($this->__CFG__["printer"]["printer10"], $file, $queue);
                    break;
                case "BB A5":
                    $this->printByNow($this->__CFG__["printer"]["printer46"], $file, $queue);
                    break;
                case "BB Schloss Schneckenhof, BWL":
                    $this->printByNow($this->__CFG__["printer"]["printer29"], $file, $queue);
                    break;
                case "BB Schloss Ehrenhof":
                    $this->printByNow($this->__CFG__["printer"]["printer48"], $file, $queue);
                    break;
                case "Ausleihzentrum_Westfluegel":
                    $this->printByNow($this->__CFG__["printer"]["printer52"], $file, $queue);
                    break;
                default:
                    $this->printByNow($this->__CFG__["printer"]["printer08"], $file, $queue);
            }
        }

        // MAGAZINDRUCK + SCANAUFTRAG
        if( ($queue=="magazin") || ($queue=="scanauftrag") ) {
            // $this->sendToQueue("magazin", "", $file);
            switch($section) {
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
            default:
                $this->sendToQueue($queue, "", $file);
                }
        }

        // MEDIENBEARBEITUNG (Bestellliste, Erwerbungsstornierung, Erwerbungsmahnung)
        if($queue=="medienbearb") {
            switch($section) {
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
        if( ($queue=="quittung") || ($queue=="mahnung") || ($queue=="fallback") ) {
            $this->printByNow($this->__CFG__["printer"]["printer08"], $file, $queue);
        }

        if($queue=="fernleihe") {
            $this->printByNow($this->__CFG__["printer"]["repro"], $file, $queue);
        }

        if($queue=="eingangsbeleg") {
            $this->printByNow($this->__CFG__["printer"]["magazin"], $file, $queue);
        }

    }

    protected function printByNow($printer, $file, $queue) {
    //
    // Direct Printing or Printing via Cronjob
    //

    // Date
    $date_rfc = date(DATE_RFC822);
    $date = date("Y-m-d");

/// /// /// /// --- /// /// /// ///
// 2do $this->__CFG__["queue"]["magazin"] durch $this->__CFG__["queue"][$queue] ersetzen

    // Is Cronjob?
    if( ($file=="cronMagazindruck") || ($file=="cronScanauftrag") ) {

        $printer = "";

        // Cron: Magazindruck
        if($file=="cronMagazindruck") {
            $dir = $this->__CFG__["queue"]["magazin"];
            print "cronMagazindruck\r\n";
                $this->writeLog("Jobtype: cronMagazindruck\r\n");
        }

        // Cron: Scanauftrag
        if($file=="cronScanauftrag") {
            $dir = $this->__CFG__["queue"]["scanauftrag"];
            print "cronScanauftrag\r\n";
                $this->writeLog("Jobtype: cronScanauftrag\r\n");
        }

// bis hier
/// /// /// /// --- /// /// /// ///

        $files = array_diff(scandir($dir), array('..', '.'));

        foreach($files as $f) {

            if(is_dir($dir."/".$f)) {

                $subdir = array_diff(scandir($dir."/".$f), array('..', '.'));

                    foreach($subdir as $s) {
                        $print_cmd = "";
                            switch($f) {
                                case "A3":
                                    $printer = $this->__CFG__["printer"]["printer50"];
                                    break;
                                case "A5":
                                    $printer = $this->__CFG__["printer"]["konicaA5"];
                                    break;
                                case "BWL":
                                    $printer = $this->__CFG__["printer"]["printer29"];
                                    break;
                                case "BSE":
                                    $printer = $this->__CFG__["printer"]["printer21"];
                                    break;
                                case "SW":
                                    if($queue=="magazin") {
                                        $printer = $this->__CFG__["printer"]["magazin"];
                                    } else {
                                        $printer = $this->__CFG__["printer"]["printer08"];
                                    }
                                    break;
                                case "WEST":
                                    $printer = $this->__CFG__["printer"]["printer52"];
                                    break;
                                default:
                                    $printer = $this->__CFG__["printer"]["printer08"];
                            }

                        if($s != "dummy") {
                            $print_cmd = "lp -o fit-to-page -d " .$printer. " " .$dir.$f."/".quotemeta($s);
                                $this->writeLog("\r\n Printing on queue: ".$queue. " with command: " .$print_cmd);
                            shell_exec($print_cmd);
                                if($printer=="printer52") {
                                    $print_debug_cmd = "lp -o fit-to-page -d Kyocera_ECOSYS_M2530dn " .$dir.$f."/".quotemeta($s);
                                    shell_exec($print_debug_cmd);
                                }

                            $h_dir = basename($dir);    // print ($dir) . "\r\n";   // dir
                            $h_subdir = $f;             // print ($f) . "\r\n";     // subdir
                            $h_file = $s;               // print ($s) . "\r\n";     // file

                            // move to history directory
                            if (!file_exists($this->__CFG__["common"]["history"].$h_dir."/".$date)) {
                                mkdir($this->__CFG__["common"]["history"].$h_dir."/".$date, 0777, true);
                            }
                            $movedFile = basename($h_file);
                            rename($dir.$f."/".$s, "/home/mailuser/alma_print/history/".$h_dir."/".$date."/".$movedFile);
                        }

                    }

                } else {

    		    // print jobs in ROOT
                    if($f != "dummy") {
                        $printer = $this->__CFG__["printer"]["printer08"];
                        // DEBUG
                        // $printer = "PRINTER08_SW";
                        $print_cmd = "lp -o fit-to-page -d " .$printer. " " .$dir.quotemeta($f);
                        shell_exec($print_cmd);
                        // DEBUG
                        // echo $print_cmd . "\r\n";

                        $h_dir = basename($dir);
                        $h_file = $f;

                        // move to history directory

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

        // // $print_cmd = "lp -o fit-to-page -d " .$printer. " " .$file; // ." >/dev/null 2>&1 &";

	// Letze funktionierende Konfuguration vor Aenderung Eingagnsbeleg
	// $print_cmd = "lp -d " .$printer. " " .$file; // ." >/dev/null 2>&1 &";

	// Aenderung fuer Eingangsbeleg#
	$print_cmd = "lp -o fit-to-page -d " .$printer. " " .$file;

        $this->writeLog("-- ". $print_cmd);

        shell_exec($print_cmd);
        print $print_cmd;

        // move to history directory /direct/
        if (!file_exists($this->__CFG__["common"]["history"].$queue."/".$date)) {
            mkdir($this->__CFG__["common"]["history"].$queue."/".$date, 0777, true);
        }

        $movedFile = basename($file);
        rename($file, $this->__CFG__["common"]["history"].$queue."/".$date."/".$movedFile);

        }

    }

    protected function sendToQueue($queue, $section, $file) {
    //
    // Copy File to Queue
    //

        $cp_cmd = "cp \"".$file."\" \"".$this->__CFG__["queue"][$queue]."\"/".$section;

        // local test (slashes)
        // $cp_cmd = "copy \"".$file."\" \"".$this->__CFG__["queue"][$queue]."\"\".$section;

        // print($cp_cmd);
        shell_exec($cp_cmd);

    }
}
?>
