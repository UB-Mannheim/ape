Documentation
=============

Directories & files
-------------------

APE may live in the <mailusers> home directory
    * /home/<mailuser>/alma_print

and has the following directory structure
        * /composer/    -   Mailparser Library
        * /docs/        -   Documentation and License information
        * /history/     -   Copy of already printed PDFs, sorted by days
        * /html/        -   Webinterface, accessed by apache
        * /log/         -   Logfiles
        * /queue/       -   PDFs awaiting to be printed with next cronjob
        * /tmp/         -   Incoming mails as HTML files

and important files
        * cronMagazinDruck.php  -   executed by cronjob
        * cronScanauftrag.php   -   executed by cronjob
        * init.php              -   startup service
        * print.conf            -   configuration file (directories & printers)
        * printJob.class.php    -   called by init.php


Configuration
-------------

See 'print.conf.example' for more information