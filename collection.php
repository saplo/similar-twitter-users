<?php
require_once 'settings.php';
require_once 'libraries/Saplo.php';

/*
 * Create a Saplo collection where tweets can be stored.
 */

$saplo = new Saplo(API_KEY, SECRET_KEY);

try
{
	$params = array(
		'name'     => 'My Tweet Collection',
		'language' => 'en'
	);
	
	$response = $saplo->request('collection.create', $params);
	
	echo "Collection ID: " . $response['collection_id'];
}
catch (SaploException $e)
{
	echo "<p>Saplo API error</p>";
	echo "<p>Code: " . $e->getCode() . "</p>";
	echo "<p>Message: " . $e->getMessage() . "</p>";
	
	if ($e->getCode() == 1203)
	{
		$response = $saplo->request('collection.list', array(), 'collections');
		
		echo "<p>List of collecions owned by this API account:</p>";
				
		foreach ($response as $collection)
		{
			echo $collection['collection_id'] . " (" . $collection['collection_name'] . ")";
		}
	}
}
