Installation and Configuration
------------------------------

<!-- BEGIN-MARKDOWN-TOC -->
* [Project structure](#project-structure)
* [Base installation](#base-installation)
	* [Recommended Environment](#recommended-environment)
	* [Users](#users)
	* [Clone the `ape` Repository](#clone-the-ape-repository)
	* [Print Configuration](#print-configuration)
	* [Install required packages](#install-required-packages)
* [Workflow](#workflow)
	* [Processing Email](#processing-email)
	* [Printing](#printing)
	* [Setting up cronjobs](#setting-up-cronjobs)

<!-- END-MARKDOWN-TOC -->

## Project structure

Throughout the configuration, `<mailuser>` is a placeholder for an
actual username.

APE may live in the `<mailuser>` home directory

`/home/<mailuser>/ape`

and has the following directory structure

* `/composer/`    -   Mail parser Library
* `/docs/`        -   Documentation and License information
* `/history/`     -   Copy of already printed PDFs, sorted by days
* `/html/`        -   Web interface, accessed by Apache
* `/log/`         -   Logfiles
* `/queue/`       -   PDFs awaiting to be printed with next cronjob
* `/tmp/`         -   Incoming emails as HTML files

and important files

* `cronMagazinDruck.php`  -   executed by cronjob
* `cronScanauftrag.php`   -   executed by cronjob
* `init.php`              -   startup service
* `print.conf`            -   configuration file (directories & printers)
* `printJob.class.php`    -   called by init.php

## Base installation

### Recommended Environment

- Debian 11 (Bullseye) Installation with PHP 7.4
- Open incoming port 25

### Users

Create a dedicated user `<mailuser>`

```
sudo useradd -d <mailuser>
```

### Clone the `ape` Repository
Inside `/home/<mailuser>/` run

```
git clone https://github.com/UB-Mannheim/ape.git
```

### Print Configuration

Rename [`print.conf.example`](./print.conf.example) to `print.conf`
and adapt the paths to your system.

### Install required packages

#### Debian packages

- exim4
- cups
- apache2
- libapache2-mod-php
- weasyprint
- git

#### Using composer
- php-mime-mail-parser

Assuming [composer](https://getcomposer.org) is already 
[installed](https://getcomposer.org/doc/00-intro.md) change to 
directory `/home/<mailuser>/ape/composer` and run

```
composer install
```

to install php-mime-mail-parser.

## Workflow

### Processing Email
- exim4 retrieves emails on incoming port 25 (!firewall restrictions)
  and redirects them all to the `<mailuser>` as specified in `/etc/aliases`

```
*: <mailuser>
```

- the `<mailuser>` forwards incoming email to the ape script as specified
  in `/home/<mailuser>/.forward`

```
<mailuser>@servername.de,"|php -q /home/<mailuser>/ape/init.php"
```

### Printing

- the named script parses the email and gathers the required
  information before printing via CUPS print server

### Setting up cronjobs

See [crontab example](./examples/config/crontab.debian)
