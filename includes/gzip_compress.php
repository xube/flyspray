<?php

	// function to compress the output buffer in preparation for transmission to client
	function compress_output($output)
	{
		// compress and return the output buffer
		return gzencode($output);
	}

	// Check if the browser supports gzip encoding: HTTP_ACCEPT_ENCODING
	if (strstr($HTTP_SERVER_VARS['HTTP_ACCEPT_ENCODING'], 'gzip'))
	{
		// Start output buffering, and register compress_output() function
		ob_start('compress_output');
		// Tell the browser the content is compressed with gzip
		header('Content-Encoding: gzip');
	}
	else
	{
		// The browser doesn't support gzip. Start standard php output buffering.
		ob_start();
	}

?>