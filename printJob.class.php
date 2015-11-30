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

        $a = func_get_args();
        $i = func_num_args();

        if (method_exists($this,$f='__construct'.$i)) {
            call_user_func_array(array($this,$f),$a);
        } else {

            $this->getConfig();

            $this->__MAIL__ = true;

            // Read StreamInput
            $content = $this->streamInput();

            $this->getContent($content);

        }
    }

    function __construct1($filename) {

        $this->getConfig();

        $this->__MAIL__ = false;

         // Load File Content
        $content = $this->fileInput($filename);

        $this->getContent($content);
    }

    protected function getConfig() {

        // Initialize Variables
        $this->__CFG__ = parse_ini_file("print.conf", TRUE);
        $this->__PATH__ = $this->__CFG__["common"]["root"];
        $this->__LOG__ = $this->__CFG__["common"]["log"];

        // Include Mailparser Library
        require_once $this->__CFG__["lib"]["mailparser"];
        $this->__PARSER__ = new PhpMimeMailParser\Parser();

    }

    protected function writeLog($msg) {

        $log = $this->__CFG__["common"]["log"];
        $fdw = fopen($log, "a+");
            fwrite($fdw, $msg . "\n");
        fclose($fdw);
    }

    protected function streamInput() {
        print "STREAM IN";

        $this->writeLog("--- READING MAIL ---");
        /*
            //listen to incoming e-mails
            $sock = fopen ("php://stdin", 'r');

            //read e-mail into buffer
            while (!feof($sock))
            {
                $email .= fread($sock, 1024);
            }

            //close socket
            fclose($sock);
        */

        $this->__PARSER__->setStream(fopen("php://stdin", "r"));

            $email = ""; // zu pruefen, ob weiterhin benoetigt
            $to = $this->__PARSER__->getHeader('to');
            $from = $this->__PARSER__->getHeader('from');
            $subject = $this->__PARSER__->getHeader('subject');

            $text = $this->__PARSER__->getMessageBody('text');
            $html = $this->__PARSER__->getMessageBody('html');
            $htmlEmbedded = $this->__PARSER__->getMessageBody('htmlEmbedded'); //HTML Body included data
/*
        // Mailobobjekt erstellen
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
        if (preg_match_all('|<h2 id="type">(.*)</h2>|U', $email, $type)) {
            $printjob["type"] = $type[1][0];
        }
        if (preg_match_all('|<h2 id="library">(.*)</h2>|U', $email, $library)) {
            $printjob["library"] = $library[1][0];
        }
        if (preg_match_all('|<h2 id="callnumber">(.*)</h2>|U', $email, $callnumber)) {
            $printjob["callnumber"] = $callnumber[1][0];
        }
        if (preg_match_all('|<h2 id="level">(.*)</h2>|U', $email, $level)) {
            $printjob["level"] = $level[1][0];
        }

        $name = $printjob["type"]."__".$printjob["library"]."__".$printjob["callnumber"]."__".$printjob["level"];
        $name = preg_replace('/\s+/', '_', $name);
        $name = preg_replace('/\,/', '', $name);

        $printjob["name"] = $name;

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

        // html to pdf
        $this->writeLog("-- create pdf: ".$pdf);

        $convert_cmd = "/usr/local/bin/wkhtmltopdf -q ".$filename." ".$pdf;

        $this->writeLog("-- ". $convert_cmd);

        exec($convert_cmd);

        if (file_exists($pdf)) {
            $this->writeLog("-- file: ".$pdf." successfully created");
        } else {
            $this->writeLog("-- file: ".$pdf." not found");
        }

        $this->writeLog("-- start printing: ".$pdf);

        $print_cmd = "lp -d " .$printer. " " .$pdf; // ." >/dev/null 2>&1 &";

        $this->writeLog("-- ". $print_cmd);

        shell_exec($print_cmd);

        // unlink($filename);
        // unlink($pdf);

    $this->writeLog("--- END ---");

    }

}

/*
$config = parse_ini_file("print.conf", TRUE);

require_once "glb.php.class";

echo $config["common"]["log"];
*/
?>