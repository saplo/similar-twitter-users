<?php

/**
 * Simple exception handler class
 * 
 * @author stenberg
 */
class MyException extends Exception
{
	public static function default_exception_handler($e)
	{
		echo '<div class="alert alert-error">'
		   . '    <p>'
		   . '        Exception: <strong>' . $e->getMessage() . '</strong>'
		   . '    </p>'
		   . '    <p>'
		   . '        Exception occured in file '
		   . '        <strong>' . $e->getFile() . '</strong> '
		   . '        on line '
		   . '        <strong>' . $e->getLine() . '</strong>'
		   . '    </p>'
		   . '</div>';
	}
}