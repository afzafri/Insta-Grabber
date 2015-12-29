<html>
<head>
<title>Fetch Instagram's Photo Data</title>
<style>
table {
    border-collapse: collapse;
}

table, th, td {
    border: 1px solid black;
	padding: 10px;
	 text-align: left;
}
</style>
</head>
<body>
<center>
<h1>Fetch Instagrams's Photo Data</h1>
<form action="index.php" method="get">
Instagram Photo URL : <input type="text" name="url"><br><br>
<input type="submit" name="submit">
</form>

<?php

if(isset($_GET['submit']))
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
	$caption = $jsonobj['entry_data']['PostPage'][0]['media']['caption'];
	$username = $jsonobj['entry_data']['PostPage'][0]['media']['owner']['username'];
	$full_name = $jsonobj['entry_data']['PostPage'][0]['media']['owner']['full_name'];
	$userid = $jsonobj['entry_data']['PostPage'][0]['media']['owner']['id'];
	$location = $jsonobj['entry_data']['PostPage'][0]['media']['location']['name'];
	$likes = $jsonobj['entry_data']['PostPage'][0]['media']['likes']['count'];
	$comments = $jsonobj['entry_data']['PostPage'][0]['media']['comments']['count'];
	$arrusersphoto = $jsonobj['entry_data']['PostPage'][0]['media']['usertags']['nodes'];
	
	echo " <h3>Data : </h3>";
	echo "	<img src='$img' title='photo' height='300px'><br>
				<i>Caption : $caption</i><br><br>
				
				<table>
				<tr>
				<th>User ID :</th><td>$userid</td>
				</tr>
				<tr>
				<th>Username :</th><td>$username</td>
				</tr>
				<tr>
				<th>Full Name :</th><td>$full_name</td>
				</tr>
				<tr>
				<th>Location :</th><td>$location</td>
				</tr>
				<tr>
				<th>No. Comments :</th><td>$comments</td>
				</tr>
				<tr>
				<th>No. Likes :</th><td>$likes</td>
				</tr> ";
	echo " <tr>
				<th>Users in Photo :</th><td>";	
	
	//loop array to get list of users_in_photo
	for($i=0;$i<count($arrusersphoto);$i++)
	{
		$listuser = $jsonobj['entry_data']['PostPage'][0]['media']['usertags']['nodes'][$i]['user']['username'];
		echo "$listuser<br>";
	}
	echo " </td></tr>";
			
	echo " </table>";	

}
?>
