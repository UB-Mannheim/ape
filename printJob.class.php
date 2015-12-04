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

        $this->printByNow($cron, $job);

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
        $this->processPrint($printjob["type"], $printjob["library"], $pdf);

        // local test (no pdf generator)
        // $this->processPrint($printjob["type"], $printjob["library"], $filename);

    }

    protected function processPrint($type, $section, $file) {

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
            default:
                // Printer "Ausleitheke"
                $queue = "fallback";
        }

        // RUECKLAGE ZETTEL
        if($queue=="ruecklage") {
            switch($section) {
                case "BB Schloss Schneckenhof, West":
                    $this->printByNow($this->__CFG__["printer"]["printer08"], $file);
                    // sendToQueue("magazin", "BSE", $file);
                    break;
                case "BB A3":
                    $this->printByNow($this->__CFG__["printer"]["printer10"], $file);
                    break;
                case "BB A5":
                    $this->printByNow($this->__CFG__["printer"]["printer46"], $file);
                    break;
                case "BB Schloss Schneckenhof, BWL":
                    $this->printByNow($this->__CFG__["printer"]["printer29"], $file);
                    break;
                case "BB Schloss Ehrenhof":
                    $this->printByNow($this->__CFG__["printer"]["printer48"], $file);
                    break;
                default:
                    $this->printByNow($this->__CFG__["printer"]["printer08"], $file);
            }
        }

        // MAGAZINDRUCK
        if($queue=="magazin") {
            $this->sendToQueue("magazin", "", $file);
        }

        // SCANAUFTRAG
        if($queue=="scanauftrag") {
            switch($section) {
            case "BB Schloss Schneckenhof, West":
                $this->sendToQueue("scanauftrag", "SW", $file);
                break;
            case "BB A3":
                $this->sendToQueue("scanauftrag", "A3", $file);
                break;
            case "BB A5":
                $this->sendToQueue("scanauftrag", "A5", $file);
                break;
            case "BB Schloss Schneckenhof, BWL":
                $this->sendToQueue("scanauftrag", "BWL", $file);
                break;
            case "BB Schloss Ehrenhof":
                $this->sendToQueue("scanauftrag", "BSE", $file);
                break;
            default:
                $this->sendToQueue("scanauftrag", "", $file);
                }
        }

        // MEDIENBEARBEITUNG (Bestellliste, Erwerbungsstornierung, Erwerbungsmahnung)
        if($queue=="medienbearb") {
            switch($section) {
            case "BB Schloss Schneckenhof, West":
                $this->printByNow($this->__CFG__["printer"]["printer09"], $file);
                break;
            case "BB A3":
                $this->printByNow($this->__CFG__["printer"]["konicaA3"], $file);
                break;
            case "BB A5":
                $this->printByNow($this->__CFG__["printer"]["konicaA5"], $file);
                break;
            case "BB Schloss Schneckenhof, BWL":
                $this->printByNow($this->__CFG__["printer"]["printer19"], $file);
                break;
            case "BB Schloss Ehrenhof":
                $this->printByNow($this->__CFG__["printer"]["printer20"], $file);
                break;
            default:
                $this->printByNow($this->__CFG__["printer"]["printer08"], $file);
                }
        }

        // QUITTUNGSDRUCK,
        // 3.MAHNHUNG &
        // "FALLBACK"
        if( ($queue=="quittung") || ($queue=="mahnung") || ($queue=="fallback") ) {
            $this->printByNow($this->__CFG__["printer"]["printer08"], $file);

            // Date
            $date_rfc = date(DATE_RFC822);
            $date = date("Y-m-d");

            // can't be moved ... has already been moved in 'printByNow()' /direct/
            // move to history directory /$queue/
            if (!file_exists($this->__CFG__["common"]["history"].$queue."/".$date)) {
                mkdir($this->__CFG__["common"]["history"].$queue."/".$date, 0777, true);
            }

            $movedFile = basename($file);
            rename($file, $this->__CFG__["common"]["history"].$queue."/".$date."/".$movedFile);
        }

        if($queue=="fernleihe") {
            $this->printByNow($this->__CFG__["printer"]["repro"], $file);

            // Date
            $date_rfc = date(DATE_RFC822);
            $date = date("Y-m-d");

            // move to history directory /$queue/
            if (!file_exists($this->__CFG__["common"]["history"].$queue."/".$date)) {
                mkdir($this->__CFG__["common"]["history"].$queue."/".$date, 0777, true);
            }

            $movedFile = basename($file);
            rename($file, $this->__CFG__["common"]["history"].$queue."/".$date."/".$movedFile);
        }

    }

    protected function printByNow($printer, $file) {
    //
    // Printing
    //

    // Date
    $date_rfc = date(DATE_RFC822);
    $date = date("Y-m-d");

    // Is Cronjob?
    if( ($file=="cronMagazindruck") || ($file=="cronScanauftrag") ) {

        // Cron: Magazindruck
        if($file=="cronMagazindruck") {

        $dir = $this->__CFG__["queue"]["magazin"];

        $printer = $this->__CFG__["printer"]["magazin"];

        $files = array_diff(scandir($dir), array('..', '.'));

        print "cronMagazindruck\r\n";

            foreach($files as $f) {
                // a5 quer
                // $print_cmd = "lp (-o media=a5) -d " .$printer. " " .$dir."/".$f; // ." >/dev/null 2>&1 &";

                // a5 hoch, klein skaliert
                $print_cmd = "lp -o fit-to-page -d " .$printer. " " .$dir."/".$f; // >/dev/null 2>&1 &";

                shell_exec($print_cmd);
                print $print_cmd . "\r\n";

                // move to history directory /magazin/
                if (!file_exists($this->__CFG__["common"]["history"]."magazin/".$date)) {
                    mkdir($this->__CFG__["common"]["history"]."magazin/".$date, 0777, true);
                }

                rename($dir."/".$f, $this->__CFG__["common"]["history"]."magazin/".$date."/".$f);

            }

        }

        // Cron: Scanauftrag
        if($file=="cronScanauftrag") {

        $dir = $this->__CFG__["queue"]["scanauftrag"];
        $printer = "";

        $files = array_diff(scandir($dir), array('..', '.'));

            foreach($files as $f) {
                if(is_dir($dir."/".$f)) {
                    $sub = array_diff(scandir($dir."/".$f), array('..', '.'));
                        foreach($sub as $s) {
                            $print_cmd = "";
                            // echo $s . "\r\n";
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
                                    $printer = $this->__CFG__["printer"]["printer08"];
                                    break;
                                default:
                                    $printer = $this->__CFG__["printer"]["printer08"];
                            }

                            $print_cmd = "lp -d " .$printer. " " .$dir."/".$f."/".$s;
                            shell_exec($print_cmd);

                            print $print_cmd . "\r\n";
/*
                            // move to history directory /scanauftrag/
                            if (!file_exists($this->__CFG__["common"]["history"]."scanauftrag/".$date)) {
                                mkdir($this->__CFG__["common"]["history"]."scanauftrag/".$date, 0777, true);
                            }

                            $movedFile = basename($s);
                            rename($f, $this->__CFG__["common"]["history"]."scanauftrag/".$date."/".$movedFile);

*/
                            }
                } else {
                    // print jobs in ROOT
                }
            }
        }

    } else {

    // No Cronjob, called directly from processPrint()

        $this->writeLog("-- start printing: ".$file);

        $print_cmd = "lp -d " .$printer. " " .$file; // ." >/dev/null 2>&1 &";

        $this->writeLog("-- ". $print_cmd);

        shell_exec($print_cmd);
        print $print_cmd;

        // move to history directory /direct/
        if (!file_exists($this->__CFG__["common"]["history"]."direct/".$date)) {
            mkdir($this->__CFG__["common"]["history"]."direct/".$date, 0777, true);
        }

        $movedFile = basename($file);
        rename($file, $this->__CFG__["common"]["history"]."direct/".$date."/".$movedFile);

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