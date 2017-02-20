<?php

if(isset($_GET['url']))
{ 
	$url = $_GET['url'];

	# use cURL instead of file_get_contents(), this is because on some server, file_get_contents() cannot be used
    # cURL also have more options and customizable
    $ch = curl_init(); # initialize curl object
    curl_setopt($ch, CURLOPT_URL, $url); # set url
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); # receive server response
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); # do verify SSL
    $data = curl_exec($ch); # execute curl, fetch webpage content
    echo curl_error($ch);
    $httpstatus = curl_getinfo($ch, CURLINFO_HTTP_CODE); # receive http response status
    curl_close($ch);  # close curl
	
	//strpos to get location for begin and end of JSON data. to use with substr
	//Need to do this because we only need the JSON data not the whole source code
	$begin = strpos($data, '<script type="text/javascript">window._sharedData =') + strlen('<script type="text/javascript">window._sharedData ='); 
	$end   = strpos($data, ';</script>');
	
	//substr() function to get only JSON data from whole source code
	$text = substr($data, $begin, ($end - $begin));
	
	//decode JSON and store into array var $jsonobj
	$jsonobj = json_decode($text,true);
	
	//data fetched from array that used to store decoded JSON
	$img = $jsonobj['entry_data']['PostPage'][0]['media']['display_src'];
	$caption = (isset($jsonobj['entry_data']['PostPage'][0]['media']['caption'])) ? $jsonobj['entry_data']['PostPage'][0]['media']['caption'] : null;
	$username = $jsonobj['entry_data']['PostPage'][0]['media']['owner']['username'];
	$full_name = $jsonobj['entry_data']['PostPage'][0]['media']['owner']['full_name'];
	$userid = $jsonobj['entry_data']['PostPage'][0]['media']['owner']['id'];
	$location = $jsonobj['entry_data']['PostPage'][0]['media']['location']['name'];
	$likes = $jsonobj['entry_data']['PostPage'][0]['media']['likes']['count'];
	$comments = $jsonobj['entry_data']['PostPage'][0]['media']['comments']['count'];
	$arrusersphoto = $jsonobj['entry_data']['PostPage'][0]['media']['usertags']['nodes'];

	//initialized new associative array, for storing the data
	//why not just return the scraped json? well as you can see above, the original json is wayyyy to deep
	$jsondata = array();

	//store data
	$jsondata['user_id'] = $userid;
	$jsondata['username'] = $username;
	$jsondata['full_name'] = $full_name;
	$jsondata['image_url'] = $img;
	$jsondata['caption'] = $caption;
	$jsondata['likes'] = $likes;
	$jsondata['comments'] = $comments;
	$jsondata['location'] = $location;
	$jsondata['tagged_users'] = array();
	
	//loop array to get list of users_in_photo
	for($i=0;$i<count($arrusersphoto);$i++)
	{
		$jsondata['tagged_users'][] = $jsonobj['entry_data']['PostPage'][0]['media']['usertags']['nodes'][$i]['user']['username'];
	}
	
	// convert the array into JSON strings, and print
	echo json_encode($jsondata);

}
?>
