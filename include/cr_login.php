<?php
include("rest_client.php");

if($rest = new CR\tools\rest("https://rest.cleverreach.com/v2"));

if($token = $rest->post('/login',
	array(
		"client_id"=>'5811',
		"login"=>'admin',
		"password"=>'xjLwdB4J'
	)
)){
    $rest->setAuthMode("bearer", $token);
}
?>
