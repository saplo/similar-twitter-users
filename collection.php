<?php

require_once '../Saplo.php';

/*
 * Create a Saplo collection where tweets can be stored.
 */

$saplo = new Saplo();

try
{
	$params = array(
		'name'     => 'My Tweet Collection',
		'language' => 'en'
	);
	
	$response = $saplo->request('collection.create', $params);
	
	echo $response['collection_id'];
}
catch (SaploException $e)
{
	echo "Saplo API error\n";
	echo "Code: " . $e->getCode() . "\n";
	echo "Message: " . $e->getMessage();
}
