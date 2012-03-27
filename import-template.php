<?php
#source db DSN - see README
$mas90db = "DNS=MAS90Mirror;";

#destination db DSN - see README
$mssqldb_dsn = "SQLServer";
$mssqldb_username = "Administrator";
$mssqldb_password = "somegreatpassword";

#sends a notification email after completion
$email_to_notify = "test@example.com";

#name of table to mirror
$table_name = "AR_Customer";

$start_time = time();
error_reporting(E_ALL);
ini_set('display_errors','On');

#helper
function addslashes_mssql($str){
	if (is_array($str)) {
	    foreach($str AS $id => $value) {
	        $str[$id] = addslashes_mssql($value);
	    }
	} else {
	    $str = str_replace("'", "''", $str);    
	}
	
	return $str;
}

#db connects
$db=odbc_connect($mas90db,"","");
$mssql=odbc_connect($mssqldb_dsn,$mssqldb_username,$mssql_password);

// Select all rows from the desired table
$sql="SELECT * FROM $table_name";
$query=odbc_exec($db, $sql);
$p = 0;


#Drop all rows from destination table (ensures data integrity)
$check = "TRUNCATE TABLE $table_name;";
odbc_exec($mssql,$check);	


while($row = odbc_fetch_array($query))
{	
	$p++;
	
	#Clear values
	$keys = "";
	$values = "";

	#Build the query
	foreach ($row as $k => $v)
	{
		$keys .= $k.",";
		$values .= "'".addslashes_mssql($v)."',";
	}

	#Join it all together
	$keys = substr($keys,0,strlen($keys)-1);
	$values = substr($values,0,strlen($values)-1);
	$insert = sprintf("INSERT INTO $table_name (%s) VALUES (%s);",$keys,$values);
	odbc_exec($mssql,$insert);	
}

$end_time = time();
$elapsed_time = date("i\m, s\s",($end_time-$start_time));;

$to      = $email_to_notify;
$subject = "$table_name mirrored succesfully";
$message = "$table_name mirrored successfully in $elapsed_time with $p rows inserted.";

$headers = 'From: MAS90Mirror' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

mail($to, $subject, $message, $headers);

?>