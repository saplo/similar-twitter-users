<?php
require_once 'settings.php';
require_once 'libraries/Saplo.php';

/*
 * Create a Saplo collection where tweets can be stored.
 */
try
{
	$saplo = new Saplo(API_KEY, SECRET_KEY);
	
	$params = array(
		'name'     => 'My Tweet Collection',
		'language' => 'en'
	);
	
	$response = $saplo->request('collection.create', $params);
	
	echo "Collection ID: " . $response['collection_id'];
}
catch (SaploException $e)
{
	echo "<h2>Saplo API error</h2>";
	echo "Code: " . $e->getCode() . "<br />";
	echo "Message: " . $e->getMessage();
	
	if ($e->getCode() == 1203)
	{
		$response = $saplo->request('collection.list', array(), 'collections');
		
		echo "<h2>List of collecions owned by this API account:</h2>";
				
		foreach ($response as $collection)
		{
			echo $collection['collection_id'] . " (" . $collection['name'] . ")<br />";
		}
	}
}
