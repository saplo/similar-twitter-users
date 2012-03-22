<?php

/**
 * Simple debugging class
 * 
 * @author stenberg
 */
class Debug
{
	/**
	 * Print debug message
	 * 
	 * @param string $message
	 * @param string $title   Set title of this debug message (optional)
	 * 
	 * @return void
	 */
	public static function msg($message, $title = "")
	{
		if (DEBUG_ON)
		{
			if ($title)
			{
				echo "<h4>" . $title . "</h4>";
			}
			
			echo "<pre>" . $message . "</pre>";
		}
	}
}