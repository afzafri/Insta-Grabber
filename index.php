<?php

if(isset($_GET['url']))
{ 
	$url = $_GET['url'];
	
	//get source code of the url/intagram photo page and store into var
	$data = file_get_contents("$url");
	
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
