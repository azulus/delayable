<?php
/**
CURL Example (example_curl.php):

curl providers built-in functions for running parallel http requests through it's PHP library. Using 
curl_multi_add_handle, curl_multi_exec, and curl_multi_getcontent, the developer can request multiple
urls from curl at the same time and retrieve the results at the speed of the slowest request (as
opposed to the speed of all requests combined). The \example\UrlLoader class abstracts this work out
in the loadDelayed method and the Delayable framework will run all the calls in parallel as soon as 
the results are needed (when the echo is called in this example).
**/

require("example_common.php");

$urlLoader = delay( new \example\UrlLoader() );
$google = $urlLoader->get("http://www.google.com");
$yahoo = $urlLoader->get("http://www.yahoo.com");
$bing = $urlLoader->get("http://www.bing.com");

echo $google.$yahoo.$bing;
