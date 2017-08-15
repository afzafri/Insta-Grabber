<?php

/*  Instagram Post API created by Afif Zafri.
    Post details are fetched directly from Instagram website,
    parse the content, and return JSON formatted string.
    Please note that this is not the official API, this is actually just a "hack",
    or workaround.
    Usage: http://site.com/index.php?url=URLPOST , where URLPOST is the Instagram post url
*/

header("Access-Control-Allow-Origin: *"); # enable CORS

if(isset($_GET['url']))
{ 
	$url = $_GET['url'];

	# use cURL instead of file_get_contents(), this is because on some server, file_get_contents() cannot be used
    # cURL also have more options and customizable
    $ch = curl_init(); # initialize curl object
    curl_setopt($ch, CURLOPT_URL, $url); # set url
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); # receive server response
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); # do not verify SSL
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

	//initialized new associative array, for storing the data
	//why not just return the scraped json? well as you can see below, the original json is wayyyy to deep
	$jsondata = array();
	$jsondata['http_code'] = $httpstatus; # set http response code into the array
	
	if(isset($jsonobj['entry_data']['PostPage']))
	{
		//test output new json structure
		$jsondata['message'] = "Data available";
		print_r($jsonobj['entry_data']['PostPage'][0]['graphql']['shortcode_media']['edge_media_to_caption']['edges'][0]['node']['text']);
		/*
		//data fetched from array that used to store decoded JSON
		$caption = (isset($jsonobj['entry_data']['PostPage'][0]['graphql']['shortcode_media']['edge_media_to_caption']['edges']['node']['text'])) ? $jsonobj['entry_data']['PostPage'][0]['graphql']['shortcode_media']['edge_media_to_caption']['edges']['node']['text'] : null;
		$username = $jsonobj['entry_data']['PostPage'][0]['media']['owner']['username'];
		$full_name = $jsonobj['entry_data']['PostPage'][0]['media']['owner']['full_name'];
		$userid = $jsonobj['entry_data']['PostPage'][0]['media']['owner']['id'];
		$location = $jsonobj['entry_data']['PostPage'][0]['media']['location']['name'];
		$likes = $jsonobj['entry_data']['PostPage'][0]['media']['likes']['count'];
		$comments = $jsonobj['entry_data']['PostPage'][0]['media']['comments']['count'];
		$arrusersphoto = $jsonobj['entry_data']['PostPage'][0]['media']['usertags']['nodes'];
		$img = array(); // array for storing user photos
		$video = "";

		//check if the instagram post have multiple photos or not and store into var
		if(isset($jsonobj['entry_data']['PostPage'][0]['media']['edge_sidecar_to_children']))
		{
			foreach($jsonobj['entry_data']['PostPage'][0]['media']['edge_sidecar_to_children']['edges'] as $images) 
			{
				$img[] = $images['node']['display_url'];
			}
		}  
		else
		{
			$img[] = $jsonobj['entry_data']['PostPage'][0]['media']['display_src'];
		} 

		//check if the post contain video
		if($jsonobj['entry_data']['PostPage'][0]['media']['is_video'] == true)
		{
			$video = $jsonobj['entry_data']['PostPage'][0]['media']['video_url'];
		}  

		//store data
		$jsondata['data']['user_id'] = $userid;
		$jsondata['data']['username'] = $username;
		$jsondata['data']['full_name'] = $full_name;
		$jsondata['data']['image_url'] = $img;
		$jsondata['data']['video_url'] = $video;
		$jsondata['data']['caption'] = $caption;
		$jsondata['data']['likes'] = $likes;
		$jsondata['data']['comments'] = $comments;
		$jsondata['data']['location'] = $location;
		$jsondata['data']['tagged_users'] = array();
		
		//loop array to get list of users_in_photo
		for($i=0;$i<count($arrusersphoto);$i++)
		{
			$jsondata['data']['tagged_users'][] = $jsonobj['entry_data']['PostPage'][0]['media']['usertags']['nodes'][$i]['user']['username'];
		}*/
	}
	else
	{
		$jsondata['message'] = "Data not available. Post deleted or account is private.";
	}

	# project info
    $jsondata['info']['creator'] = "Afif Zafri (afzafri)";
    $jsondata['info']['project_page'] = "https://github.com/afzafri/Insta-Grabber";
    $jsondata['info']['date_updated'] = "20/02/2017";
	
	// convert the array into JSON strings, and print
	echo json_encode($jsondata);

}
else
{
	?>
	<p>
		Instagram Post API created by Afif Zafri. <br>
	    Post details are fetched directly from Instagram website, <br>
	    parse the content, and return JSON formatted string. <br>
	    Please note that this is not the official API, this is actually just a "hack", <br>
	    or workaround. <br>
	    Usage: http://site.com/index.php?url=URLPOST , where URLPOST is the Instagram post url <br>
	</p>
	<?php
}

?>
