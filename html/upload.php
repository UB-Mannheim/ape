<?php
/*
echo "<pre>";
echo "FILES:<br />";
print_r ($_FILES );
echo "</pre>";
*/
if(isset($_FILES['file'])) {
    if ( $_FILES['file']['name']  <> "" )
    {

        $zugelassenedateitypen = array("image/png", "image/jpeg", "image/gif", "text/html");

        if ( ! in_array( $_FILES['file']['type'] , $zugelassenedateitypen ))
        {
            echo "<p>Dateitype ist NICHT zugelassen</p>";
        }
        else
        {
            move_uploaded_file (
                 $_FILES['file']['tmp_name'] ,
                 'uploads/'. $_FILES['file']['name'] );

            echo "<p>Datei erfolgreich hochgeladen: ";
            echo '<a href="uploads/'. $_FILES['file']['name'] .'" target="_blank">';
            echo 'uploads/'. $_FILES['file']['name'];
            echo '</a>';
        }
    }
}

if (isset($_GET['fn'])) {
    if($_GET['fn'] == "delete") {
        $cmd = "rm /var/www/html/alma_print/uploads/*";
        shell_exec($cmd);
    }
}

?>
<html>
<head>
<style>
body {
    background-color: white;
}

iframe {
    width:100%;
    max-width:1200px;
    height:100%;
    max-height:700px;
    border:0px;
}
a, a:hover, a:visited, a:link {
    color: #990000;
    padding: 0 10 0 5;
    text-decoration: none;
}
</style>
</head>
<body>

<h2>Datei hochladen</h2>
<form name="uploadformular" enctype="multipart/form-data" action="upload.php" method="post" >
Datei ausw&auml;hlen: <input type="file" name="file" size="60" maxlength="255" >
<input type="Submit" name="submit" value="Datei hochladen">
</form>

<h2>L&ouml;schen</h2>
<form name="deleteformular" enctype="multipart/form-data" action="upload.php?fn=delete" method="get" >
<input type="Submit" name="submit" value="Alle Dateien l&ouml;schen">
</form>

<h2>Vorschau</h2>
<iframe src="uploads" />

</body>
</html>