<?php

require_once("vendor/autoload.php");

class printJob
{
    private array $__CFG__;
    private object $__PARSER__;

    /**
     * Default constructor.
     */
    function __construct()
    {
        $this->__CFG__ = parse_ini_file("print.conf", true);
        $this->__PARSER__ = new PhpMimeMailParser\Parser();

        $a = func_get_args();
        $i = func_num_args();

        if (method_exists($this, $f = '__construct'.$i)) {
            call_user_func_array(array($this,$f), $a);
        } else {
            $this->getContent($this->streamInput());
        }
    }

    /**
     * Constructor taking one argument.
     *
     * This constructor is used for manually processing input files.
     */
    function __construct1(string $filename)
    {
        $content = $this->fileInput($filename);
        $this->getContent($content);
    }

    /**
     * Constructor taking two arguments.
     *
     * This constructor is used for cron printing.
     */
    function __construct2(string $cron, string $job)
    {
        if ($job=="cronMagazindruck") {
            $this->printByNow($cron, $job, "magazin");
        }
        if ($job=="cronScanauftrag") {
            $this->printByNow($cron, $job, "scanauftrag");
        }
    }

    /**
     * Writes a log message to the configured log file.
     */
    protected function writeLog(string $msg): void
    {
        $log = $this->__CFG__["common"]["log"];
        $fdw = fopen($log, "a+");
        fwrite($fdw, $msg . "\n");
        fclose($fdw);
    }

    /**
     * Parses a HTML email and returns its body with embedded contents.
     */
    protected function parseHTMLMail(string $file): string
    {
        $this->__PARSER__->setStream(fopen($file, "r"));
        $to = $this->__PARSER__->getHeader('to');
        $from = $this->__PARSER__->getHeader('from');
        $subject = $this->__PARSER__->getHeader('subject');

        $text = $this->__PARSER__->getMessageBody('text');
        $html = $this->__PARSER__->getMessageBody('html');
        $htmlEmbedded = $this->__PARSER__->getMessageBody('htmlEmbedded');

        return $htmlEmbedded;
    }

    /**
     * Parses a HTML email from standard input.
     */
    protected function streamInput(): string
    {
        $this->writeLog("--- READING MAIL ---");
        return $this->parseHTMLMail("php://stdin");
    }

    /**
     * Processes a local file given as constructor argument.
     * @return string
     */
    protected function fileInput(string $filename): string
    {
        $actual_filename = $filename;

        // if file is not found directly, try looking for it in mail directory (if configured)
        if (!file_exists($actual_filename)
            && array_key_exists("mail", $this->__CFG__["common"])) {
            $actual_filename = $this->__CFG__["common"]["mail"] . $filename;
        }

        if (!file_exists($actual_filename)) {
            print ("File " . $filename . " not found.\n");
            return "";
        }

        $this->writeLog("--- READING LOCAL FILE ---");
        $localfile = fopen($actual_filename, "r");
        $email = "";

        while (!feof($localfile)) {
            $email .= fgets($localfile, 1024);
        }
        fclose($localfile);

        // try detecting emails
        if (preg_match("/^Return-path: <alma@exlibrisgroup.com>.*/", $email)) {
            print("Detected email, using HTML parser\n");
            return $this->parseHTMLMail($actual_filename);
        } else {
            print("Using raw contents of file\n");
        }

        return $email;
    }

    /**
     * @return void
     */
    protected function getContent(string $email): void
    {
        $date_rfc = date(DATE_RFC822);
        $date = date("Y-m-d_H-i-s");

        $uid = uniqid();
        $udate = $date."__".$uid;

        $printjob = array("type" => "", "library" => "", "callnumber" => "", "level" => "");
        if (preg_match_all('|<h2 id="print_type">(.*)</h2>|U', $email, $type)) {
            $printjob["type"] = $type[1][0];
            $this->writeLog($printjob["type"]."\n");
        }
        if (preg_match_all('|<h2 id="print_library">(.*)</h2>|U', $email, $library)) {
            $printjob["library"] = $library[1][0];
            $this->writeLog($printjob["library"]."\n");
        }
        if (preg_match_all('|<h2 id="print_callnumber">(.*)</h2>|U', $email, $callnumber)) {
            $printjob["callnumber"] = $callnumber[1][0];
            $this->writeLog($printjob["callnumber"]."\n");
        }
        if (preg_match_all('|<h2 id="print_level">(.*)</h2>|U', $email, $level)) {
            $printjob["level"] = $level[1][0];
            $this->writeLog($printjob["level"]."\n");
        }

        # abort if seat reservation during Corona crisis
        if ($printjob["level"] === "Arbeitsplatz") {
            $this->writeLog("--- END ---");
            return;
        }

        $name = $printjob["type"] . "__" . $printjob["library"] . "__" . $printjob["level"]
              . "__" . $printjob["callnumber"];
        $name = preg_replace('/\s+/', '_', $name);
        $name = preg_replace('/\,/', '', $name);
        // changing signature: "/" to "-"
        $name = preg_replace('/\//', '-', $name);

        $filename = $this->__CFG__["common"]["tmp"] . "${name}____incoming__$udate.html";
        $pdf = $this->__CFG__["common"]["tmp"] . "${name}____pdf__$udate.pdf";
        $this->writeLog("-- writing html file: $filename");

        // Create File
        $fdw = fopen($filename, "w+");
        fwrite($fdw, $email);
        fclose($fdw);
        $this->writeLog("-- file: ".$filename." written");

        // Convert HTML to PDF
        $this->writeLog("-- Create PDF: ".$pdf);

        // Quoting HTML- & PDF-Filename for conversion
        $q_filename = escapeshellarg($filename);
        $q_pdf = escapeshellarg($pdf);
        $convert_cmd = "/usr/bin/weasyprint -q -s " . $this->__CFG__["common"]["root"]
                     . "weasy.css $q_filename $q_pdf";
        shell_exec($convert_cmd);

        // File Creation Successful?
        if (file_exists($pdf)) {
            $this->writeLog("-- file: $pdf successfully created");
        } else {
            $this->writeLog("-- file: $pdf not found");
        }
        $this->writeLog("--- END ---");

        $this->processPrint(
            $printjob["type"],
            $printjob["library"],
            $printjob["level"],
            $pdf
        );
    }

    protected function printByFloor(string $file, string $floor, string $queue): void
    {
        if ($floor=="Westfluegel" || $floor=="Untergeschoss"
            || $floor=="Erdgeschoss" || $floor=="Galerie"
        ) {
            $this->sendToQueue($queue, "WEST", $file);
        } else {
            $this->sendToQueue($queue, "SW", $file);
        }
    }

    protected function processPrint(string $type, string $section, string $floor, string $file): void
    {
        $queue = "";
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
                $queue = "fallback";
        }

        // RUECKLAGEZETTEL
        if ($queue=="ruecklage") {
            switch ($section) {
                case "BB Schloss Schneckenhof, BWL":
                case "BB Schloss Schneckenhof, West":
                    $this->printByNow($this->__CFG__["printer"]["printer_BSS"], $file, $queue);
                    break;
                case "BB A3":
                case "BB A3, Testothek":
                case "BB A3, Mediathek":
                    $this->printByNow($this->__CFG__["printer"]["printer_A3"], $file, $queue);
                    break;
                case "BB A5":
                case "MZES":
                    $this->printByNow($this->__CFG__["printer"]["printer_A5"], $file, $queue);
                    break;
                case "BB Schloss Ehrenhof":
                case "Bibl. f. Accounting u. Taxation":
                    $this->printByNow($this->__CFG__["printer"]["printer_BSE"], $file, $queue);
                    break;
                case "Ausleihzentrum_Westfluegel":
                    $this->printByNow($this->__CFG__["printer"]["printer_WF_DINA5"], $file, $queue);
                    break;
                default:
                    $this->printByNow($this->__CFG__["printer"]["fallback"], $file, $queue);
            }
        }

        // MAGAZINDRUCK + SCANAUFTRAG
        if (($queue=="magazin") || ($queue=="scanauftrag")) {
            switch ($section) {
                case "BB Schloss Schneckenhof, West":
                    $this->sendToQueue($queue, "SW", $file);
                    break;
                case "BB A3":
                case "BB A3, Testothek":
                case "BB A3, Mediathek":
                    $this->sendToQueue($queue, "A3", $file);
                    break;
                case "BB A5":
                case "MZES":
                    $this->sendToQueue($queue, "A5", $file);
                    break;
                case "BB Schloss Schneckenhof, BWL":
                    $this->sendToQueue($queue, "BWL", $file);
                    break;
                case "BB Schloss Ehrenhof":
                case "Bibl. f. Accounting u. Taxation":
                case "Binnenschifffahrtsrecht, Bibl.":
                case "BB Schloss Ehrenhof - IMGB":
                    $this->sendToQueue($queue, "BSE", $file);
                    break;
                case "Ausleihzentrum_Westfluegel":
                    $this->printByFloor($file, $floor, $queue);
                    break;
                default:
                    $this->sendToQueue($queue, "", $file);
            }
        }

        // MEDIENBEARBEITUNG (Bestellliste, Erwerbungsstornierung, Erwerbungsmahnung)
        if ($queue=="medienbearb") {
            switch ($section) {
                case "BB Schloss Schneckenhof, West":
                    $this->printByNow($this->__CFG__["printer"]["fallback"], $file, $queue);
                    break;
                case "BB A3":
                    $this->printByNow($this->__CFG__["printer"]["printer_A3"], $file, $queue);
                    break;
                case "BB A5":
                    $this->printByNow($this->__CFG__["printer"]["printer_A5_2"], $file, $queue);
                    break;
                case "BB Schloss Schneckenhof, BWL":
                    $this->printByNow($this->__CFG__["printer"]["fallback"], $file, $queue);
                    break;
                case "BB Schloss Ehrenhof":
                    $this->printByNow($this->__CFG__["printer"]["fallback"], $file, $queue);
                    break;
                default:
                    $this->printByNow($this->__CFG__["printer"]["fallback"], $file, $queue);
            }
        }

        // QUITTUNGSDRUCK, 3.MAHNUNG & "FALLBACK"
        if (($queue=="quittung") || ($queue=="mahnung") || ($queue=="fallback")) {
            $this->printByNow($this->__CFG__["printer"]["fallback"], $file, $queue);
        }

        if ($queue=="fernleihe") {
            $this->printByNow($this->__CFG__["printer"]["repro"], $file, $queue);
        }

        if ($queue=="eingangsbeleg") {
            $this->printByNow($this->__CFG__["printer"]["repro_DINA5"], $file, $queue);
        }
    }

    protected function printByNow(string $printer, string $file, string $queue): void
    {
        $date_rfc = date(DATE_RFC822);
        $date = date("Y-m-d");
        $dir = "";

        // Is Cronjob?
        if (($file=="cronMagazindruck") || ($file=="cronScanauftrag")) {
            $printer = "";

            // Cron: Magazindruck
            if ($file=="cronMagazindruck") {
                $dir = $this->__CFG__["queue"]["magazin"];
                $this->writeLog("Jobtype: cronMagazindruck\n");
            }

            // Cron: Scanauftrag
            if ($file=="cronScanauftrag") {
                $dir = $this->__CFG__["queue"]["scanauftrag"];
                $this->writeLog("Jobtype: cronScanauftrag\n");
            }

            $files = array_diff(scandir($dir), array('..', '.'));

            foreach ($files as $f) {
                if (is_dir($dir."/".$f)) {
                    $subdir = array_diff(scandir($dir."/".$f), array('..', '.'));

                    foreach ($subdir as $s) {
                        $print_cmd = "";
                        switch ($f) {
                            case "A3":
                                $printer = $this->__CFG__["printer"]["printer_A3"];
                                break;
                            case "A5":
                                $printer = $this->__CFG__["printer"]["printer_A5_2"];
                                break;
                            case "BWL":
                                $printer = $this->__CFG__["printer"]["printer_BSS"];
                                break;
                            case "BSE":
                                $printer = $this->__CFG__["printer"]["printer_BSE_2"];
                                break;
                            case "SW":
                                if ($queue=="magazin") {
                                    $printer = $this->__CFG__["printer"]["magazin"];
                                } else {
                                    $printer = $this->__CFG__["printer"]["printer_BSS"];
                                }
                                break;
                            case "WEST": // A5 bei Magazindruck
                                if ($queue=="magazin") {
                                    $printer = $this->__CFG__["printer"]["printer_WF_DINA5"];
                                } else {
                                    $printer = $this->__CFG__["printer"]["printer_WF"];
                                }
                                break;
                            default:
                                $printer = $this->__CFG__["printer"]["fallback"];
                        }

                        if ($s != "dummy") {
                            $print_cmd = "lp -o fit-to-page -d $printer " . escapeshellarg("$dir$f/$s");
                            $this->writeLog("\n Printing on queue: $queue with command: "
                                            . $print_cmd);
                            shell_exec($print_cmd);

                            $h_dir = basename($dir);    // print ($dir) . "\n";   // dir
                            $h_subdir = $f;             // print ($f) . "\n";     // subdir
                            $h_file = $s;               // print ($s) . "\n";     // file
                            $h_datedir = $this->__CFG__["common"]["history"] . "$h_dir/$date";

                            // Move to History Directory
                            if (!file_exists($h_datedir)) {
                                mkdir($h_datedir, 0777, true);
                            }
                            $movedFile = basename($h_file);
                            rename($dir.$f."/".$s, "$h_datedir/$movedFile");
                        }
                    }
                } else {
                    // Print Jobs in ROOT Directory
                    if ($f != "dummy") {
                        $printer = $this->__CFG__["printer"]["fallback"];
                        $print_cmd = "lp -o fit-to-page -d $printer " . escapeshellarg("$dir$f");
                        shell_exec($print_cmd);

                        $h_dir = basename($dir);
                        $h_file = $f;
                        $h_datedir = $this->__CFG__["common"]["history"] . "$h_dir/$date";

                        // Move to History Directory
                        if (!file_exists($h_datedir)) {
                            mkdir($h_datedir, 0777, true);
                        }

                        $movedFile = basename($h_file);
                        rename($dir.$f, "$h_datedir/$movedFile");
                    }
                }
            }
        } else {
            // No Cronjob, called directly from processPrint()

            $this->writeLog("-- start printing: $file");
            $print_cmd = "lp -o fit-to-page -d $printer ".escapeshellarg($file);
            $this->writeLog("-- $print_cmd");
            shell_exec($print_cmd);

            $h_datedir = $this->__CFG__["common"]["history"] . "$queue/$date";
            // Move to History Directory /direct/
            if (!file_exists($h_datedir)) {
                mkdir($h_datedir, 0777, true);
            }

            $movedFile = basename($file);
            rename($file, "$h_datedir/$movedFile");
        }
    }

    protected function sendToQueue(string $queue, string $section, string $file): void
    {
        copy($file, $this->__CFG__["queue"][$queue]."/$section/".basename($file));
    }
}
