<?php

class Tweet
{
	public $id;
	public $username;
	public $text;
	public $created_at;
	
	public function __construct()
	{
		$this->set_id(0);
		$this->set_username("");
		$this->set_text("");
		$this->set_created_at("0000-00-00 00:00:00");
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Set id of tweet
	 * 
	 * @param int $id
	 */
	public function set_id($id)
	{
		$this->id = $id;
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Get id of tweet
	 * 
	 * @return int
	 */
	public function get_id()
	{
		return $this->id;
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Set twitter username
	 * 
	 * @param string $username
	 */
	public function set_username($username)
	{
		$this->username = $username;
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Get twitter username
	 * 
	 * @return string
	 */
	public function get_username()
	{
		return $this->username;
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Set tweet
	 * 
	 * @param string $text
	 */
	public function set_text($text)
	{
		$this->text = $text;
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Get tweet
	 * 
	 * @return string
	 */
	public function get_text()
	{
		return $this->text;
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Set date when tweet where created
	 * 
	 * @param string $created_at
	 */
	public function set_created_at($created_at)
	{
		$this->created_at = $created_at;
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Get date when tweet where created
	 * 
	 * @return string
	 */
	public function get_created_at()
	{
		return $this->created_at;
	}
}
