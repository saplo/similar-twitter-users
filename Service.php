<?php
require_once 'settings.php';
require_once 'libraries/MyException.php';
require_once 'libraries/Debug.php';
require_once 'libraries/Saplo.php';
require_once 'libraries/twitter/tmhOAuth.php';
require_once 'Tweet.php';

/*
 * Sets default exception handler that will catch any uncaught exceptions
 */
set_exception_handler(array('MyException', 'default_exception_handler'));

/*
 * Set username of Twitter user we will be looking for similar users for 
 */
isset($_POST['username']) ? $username = strtolower($_POST['username']) : $username = "";

/*
 * Check if a Twitter username has been submitted, if not -> display form
 */
if ( ! empty($username))
{
	$service = new Service();
	
	/*
	 * Check if this user has already been added to Saplo API, if the user
	 * exists then we will skip searching for new tweets and just check for
	 * similar users right away.
	 */
	if ( ! $group_id = $service->get_group_by_name($username))
	{
		/*
		 * Get tweets from this Twitter user
		 */
		$tweets = $service->get_tweets_by_username($username, NBR_OF_TWEETS);
		
		/*
		 * Add tweets by this Twitter user to a Saplo Collection.
		 * 
		 * A Saplo Collection is an archeive where you store all your texts when
		 * using Saplo API.
		 * 
		 * More about Saplo Collections:
		 * http://developer.saplo.com/topic/collection
		 * 
		 * More about how to add texts to a Saplo Collection:
		 * http://developer.saplo.com/method/text-create
		 */
		$service->add_tweets_to_collection($tweets);
		
		/*
		 * Create a Saplo Group where we can store tweets by this Twitter user.
		 * 
		 * A Saplo Group is a segment of a Saplo Collection that can be used for
		 * grouping togheter texts that have something in common, e.g. articles
		 * on different topics such as Sport or Crime.
		 * 
		 * More about Saplo Groups:
		 * http://developer.saplo.com/topic/group
		 * 
		 * More about creating a Saplo Group:
		 * http://developer.saplo.com/method/group-create
		 */
		$group_id = $service->create_group($username);
		
		/*
		 * Add tweets from this Twitter user to our newly created Saplo Group
		 * 
		 * More about adding texts to a Saplo Group:
		 * http://developer.saplo.com/method/group-addText
		 */
		$service->add_tweets_to_group($group_id, $tweets);
	}
	
	/*
	 * Get Saplo Groups similar to the group we have just created. Since each
	 * Saplo Group in this application represents a specific Twitter user,
	 * this operation will match Twitter users against each other.
	 * 
	 * More about matching Saplo Groups against each other:
	 * http://developer.saplo.com/method/group-relatedGroups
	 */
	$groups = $service->get_similar_groups($group_id);
}

/**
 * All methods available in this application
 */
class Service
{
	/*
	 * Saplo API client
	 */
	private $saplo;
	
	/*
	 * OAuth client
	 */
	private $oauth;
	
	// ----------------------------------------------------------------------
	
	/**
	 * Set up our Saplo API client (this will automatically connect you to
	 * Saplo API) and our OAuth client needed in order to make requests to
	 * Twitter API.
	 */
	public function __construct()
	{
		$this->saplo = new Saplo(API_KEY, SECRET_KEY);
		
		// Create our OAuth object
		$this->oauth = new tmhOAuth(array(
			'consumer_key'    => CONSUMER_KEY,
			'consumer_secret' => CONSUMER_SECRET,
			'user_token'      => USER_TOKEN,
			'user_secret'     => USER_SECRET
		));
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Get ID of Saplo Group with a specific name
	 * 
	 * Get a list of all Saplo Groups through Saplo API and search in that
	 * list for a group with a specific name.
	 * 
	 * @param string $name
	 * 
	 * @return int ID of Saplo Group named $name
	 */
	public function get_group_by_name($name)
	{
		$response = $this->saplo->request('group.list');
		
		foreach ($response['groups'] as $group)
		{
			if ($group['name'] == $name)
			{
				return $group['group_id'];
			}
		}
		
		return false;
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Fetch the latest tweets created by a specific user
	 * 
	 * @param string $username      Username of Twitter user
	 * @param int    $nbr_of_tweets Number of tweets we will fetch from this Twitter user
	 * 
	 * @return array Array containing tweets by this Twitter user (Tweet objects)
	 */
	public function get_tweets_by_username($username, $nbr_of_tweets = 20)
	{
		$code = $this->oauth->request('GET', $this->oauth->url('1/statuses/user_timeline'), array(
			'screen_name' => $username,
			'count'       => $nbr_of_tweets
		));

		if ($code == 200)
		{
			$response = json_decode($this->oauth->response['response'], true);
			
			$tweets = array();
			
			foreach ($response as $tweet)
			{
				$created_at = new DateTime($tweet['created_at']);
				
				$obj = new Tweet();
				
				$obj->set_id($tweet['id']);
				$obj->set_username($tweet['user']['screen_name']);
				$obj->set_text($tweet['text']);
				$obj->set_created_at($created_at->format('Y-m-d H:i:s'));
				
				$tweets[] = $obj;
			}
			
			return $tweets;
		}
		else
		{
			throw new MyException('Failed fetching tweets through Twitter API');
		}
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Store tweets as texts in our Saplo Collection
	 * 
	 * First we will create a request array containing all requests we want
	 * to send to Saplo API. We will then send these requests in a batch to
	 * Saplo (faster then sending them one by one).
	 * 
	 * @param array $tweets Array containing our tweets (Tweet objects)
	 * 
	 * @return void
	 */
	public function add_tweets_to_collection($tweets)
	{
		$batch = array();
		
		foreach ($tweets as $tweet)
		{
			$params = array(
				'collection_id' => COLLECTION_ID,
				'body'          => $tweet->text,
				'publish_date'  => $tweet->created_at,
				'authors'       => $tweet->username,
				'ext_text_id'   => (string) $tweet->id
			);
			
			$batch[] = array(
				'method'	=> 'text.create',
				'params'	=> $params,
				'id'		=> $tweet->id,
				'jsonrpc'	=> '2.0'
			);
		}
		
		/*
		 * Perform multiple requests to Saplo API (batch of requests).
		 * 
		 * It would be quite easy to go through the response returned from Saplo
		 * API for these requests, but we won't go into that in this tutorial,
		 * let's just assume that everything went well.
		 */
		$this->saplo->batch_requests($batch);
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Create a Saplo Group where we can store tweets by a specific Twitter
	 * user.
	 * 
	 * A Saplo Group is a segment of a Saplo Collection that can be used for
	 * grouping togheter texts that have something in common, e.g. articles
	 * on different topics such as Sport or Crime.
	 * 
	 * @param string $username
	 * 
	 * @return int ID of Saplo Group where tweets from this Twitter user is stored
	 * 
	 * @link http://developer.saplo.com/topic/group
	 * @link http://developer.saplo.com/method/group-create
	 */
	public function create_group($username)
	{
		$params = array(
			'name'     => $username,
			'language' => 'en'
		);
		
		$response = $this->saplo->request('group.create', $params);
		$group_id = $response['group_id'];
		
		/*
		 * Check that everything went well when creating group
		 */
		if (isset($group_id) AND $group_id > 0)
		{
			return $group_id;
		}
		else
		{
			throw new MyException('Unknown error occured when trying to create new Saplo Group');
		}
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Add tweets (that has been stored as texts in a Saplo Collection) to
	 * our Saplo Group.
	 * 
	 * First we will create a request array containing all requests we want
	 * to send to Saplo API. We will then send these requests in a batch to
	 * Saplo (faster then sending them one by one).
	 * 
	 * @param int   $group_id ID of Saplo Group where we would like to store these tweets
	 * @param array $tweets   Array containing our tweets (Tweet objects)
	 * 
	 * @return void
	 */
	public function add_tweets_to_group($group_id, $tweets)
	{
		$batch = array();
		
		foreach ($tweets as $tweet)
		{
			$params = array(
				'group_id'      => (int) $group_id,
				'collection_id' => COLLECTION_ID,
				'ext_text_id'   => (string) $tweet->id
			);
			
			$batch[] = array(
				'method'	=> 'group.addText',
				'params'	=> $params,
				'id'		=> $tweet->id,
				'jsonrpc'	=> '2.0'
			);
		}
		
		/*
		 * Perform multiple requests to Saplo API (batch of requests).
		 * 
		 * It would be quite easy to go through the response returned from Saplo
		 * API for these requests, but we won't go into that in this tutorial,
		 * let's just assume that everything went well.
		 */
		$this->saplo->batch_requests($batch);
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Get Saplo Groups similar to the group we have just created. Since each
	 * Saplo Group in this application represents a specific Twitter user,
	 * this operation will match Twitter users against each other.
	 * 
	 * @param int $group_id
	 * 
	 * @return array Array of Saplo Groups similar to group we are matching against
	 * 
	 * @link http://developer.saplo.com/method/group-relatedGroups
	 */
	public function get_similar_groups($group_id)
	{
		$params = array(
			'group_id' => (int) $group_id,
			'wait'     => 20
		);
		
		try
		{
			return $this->saplo->request('group.relatedGroups', $params, 'related_groups');
		}
		catch (SaploException $e)
		{
			return array();
		}
	}
}
