<?php
//Database connection to Supabase
$supa_host = "aws-1-us-east-1.pooler.supabase.com";
$supa_user = "postgres.huwohuvtntnnoiekrpkb";
$supa_password = "unicesmag@@";
$supa_dbname = "postgres";
$supa_port  = "6543";


//Database connection to local server
$local_host = "localhost";//127.0.0.1
$local_user = "postgres";
$local_password = "123";
$local_dbname = "marketapp";
$local_port  = "5432";

$supa_data_connection = "
    host=$supa_host
    user=$supa_user
    password=$supa_password
    dbname=$supa_dbname
    port=$supa_port
";

$local_data_connection = "
    host=$local_host
    user=$local_user
    password=$local_password
    dbname=$local_dbname
    port=$local_port
";


$conn_supa = pg_connect($supa_data_connection);
$conn_local = pg_connect($local_data_connection);
if(!$conn_supa){
    echo"Error";
}else{
    //echo"Connection sucessful:::";
}
/*
if(!$conn_local){
    echo"Error";
}else{
    echo"Connection sucessful:::";
}
*/
?>