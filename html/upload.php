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
        echo '<a href="uploads/'. $_FILES['file']['name'] .'">';
        echo 'uploads/'. $_FILES['file']['name'];
        echo '</a>';
    }
}
} else {
    echo "<h1>Datei hochladen</h1>";
}

?>
<html>
<head>
<style>
body {
    background-color: #efefef;
    font-family: Tahoma;
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
<form name="uploadformular" enctype="multipart/form-data" action="upload.php" method="post" >
Datei: <input type="file" name="file" size="60" maxlength="255" >
<input type="Submit" name="submit" value="Datei hochladen">
</form>

<iframe src="uploads" />

</body>
</html>