<?php

 $servername = "us-east.connect.psdb.cloud";
 $username = "f4ysyyefp9jcwcfpo9sk";
 $password = "pscale_pw_3xTkRjFLXhieGXgMEKiui32922j9WR5XYKNsuUHVB7o";
 $dbname = "project-app-ols";

 $connection = new mysqli($servername, $username, $password, $dbname);
 if(!$connection)
 {
     die("Connection failed: " . $connection->connect_error);
 }