ALMA Print Extension (APE) - Easily Print Custom Letters & Notifications
========================================================================

Copyright (C) 2015-2017 Universitätsbibliothek Mannheim

Authors: Alexander Wagner (UB Mannheim)

This is free software. You may use it under the terms of the
GNU General Public License (GPL). See [docs/gpl.txt](docs/gpl.txt) for details.

Parts of the software use different licenses which are listed
in file [LICENSE](LICENSE).


Summary
-------

Every E-Mail ALMA sends can be printed out by a custom printer within your
local network. "Request Letters", "Resource Slips", "Reminders" or other mails
that are received by the server are directly forwarded to the APE script.

The APE script parses incoming HTML mails and converts them to PDF. The
generated PDF file can be printed out on any user-defined printer.

APE mainly supports two functions:

 * direct print (prints mail immediately)
 * queue-controlled print (prints collected mails by cronjob)

APE has a simple and directory based file structure. Configuration changes can
easily be made by editing text files.

Additionally APE offers functions like caching mails in a temp directory.
It even has a print history, logging mechanisms and a print preview
for the best possible transparency.


Hard & Software requirements
----------------------------

APE was built on a Debian server system, but can be installed on any other
Windows or Linux system that is able to run an SMTP and web server as well.

The description beneath covers the software requirements of a Linux based
environment:

 * SMTP active and running
 * Apache2 or similar
 * PHP5 or higher
 * PHP Mime Mail Parser
 * CUPS
 * wkhtmltopdf


Installation
------------

Mannheim University Library develops and installs APE as a web application
on a Linux server running Debian GNU Linux (Jessie). Other hardware and software
combinations can also be used, but might require some smaller modifications.

See [INSTALL.md](INSTALL.md) for detailed information.


Bug reports
-----------

Please file your bug reports to https://github.com/UB-Mannheim/Ape/issues.
Make sure that you are using the latest version of the software
before sending a report.


Contributing
------------

Bug fixes, new functions, suggestions for new features and
other user feedback are appreciated.

The source code is available from https://github.com/UB-Mannheim/Ape.
Please prepare your code contributions also on GitHub.


Acknowledgments
---------------

This project uses other free software:

* [PHP Mime Mail Parser](https://github.com/php-mime-mail-parser/php-mime-mail-parser)
* [wkhtmltopdf](https://github.com/wkhtmltopdf/wkhtmltopdf) – http://wkhtmltopdf.org/
