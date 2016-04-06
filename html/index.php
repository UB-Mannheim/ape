<?php

$url = loadNavigation();

function loadNavigation() {

$path = "";

if (isset($_GET['nav'])) {
        $id = $_GET['nav'];
        switch($id) {
            case "queue": $path = "queue/";
            break;
            case "history": $path = "history/";
            break;
            case "print_server": $path = "https://134.155.36.54:631/";
            break;
            case "logfile": $path = "log/debug.log";
            break;
            default: $path = "history/";
        }
    } else {
        $path = "history/";
    }

    return $path;
}
?>


<html>
<head>
<style>
body {
    background-color: #efefef;
    font-family: Tahoma;
}
#site {
    max-width: 1200px;
    height: 100%;
    max-height: 800px;
    margin-left: auto;
    margin-right: auto;
    background-color: white;
}
#menu {
    width: 100%;
    max-width: 1200px;
    height: 100%;
    max-height: 20px;
    background-color: #990000;
    padding: 5 0 5 0;
    color: white;
}
#logo, #top_left {
    float: left;
}
#top_right {
    float: right;
}
iframe {
    width:100%;
    max-width:1200px;
    height:100%;
    max-height:700px;
    border:0px;
}
a, a:hover, a:visited, a:link {
    color: white;
    padding: 0 10 0 5;
    text-decoration: none;
}
</style>
</head>
<body>

<div id="site">

<div id="menu">
<div id="logo">
<a href="index.php"><img src="img/document-print-preview.png" width="25" /></a>
</div>
<div id="top_left">
<a href="index.php"><span style="margin-left:10px">Alma Print - Webadmin</span></a>
</div>
<div id="top_right">
<a href="index.php?nav=queue">Zeige Queue</a> |
<a href="index.php?nav=history">Zeige History</a> |
<a href="index.php?nav=print_server">Gehe zu Druckserver</a> |
<a href="index.php?nav=logfile">Zeige Logfile</a>
</div>
</div>

<div id="spacer" style="clear: both;" />

<div id="content">
<iframe src="<?php echo $url; ?>" />
</div>

</div>

</body>
</html>
