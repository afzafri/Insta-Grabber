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
<form action="demo.php" method="post">
Instagram Photo URL : <input type="text" name="url"><br><br>
<input type="submit" name="submit">
</form>

<?php

if(isset($_POST['submit']))
{
	$url = "http://localhost/instafetch/index.php?url=".$_POST['url']; # the full URL to the API including the instagram post url
	$getdata = file_get_contents($url); # use files_get_contents() to fetch the data, but you can also use cURL, or javascript/jquery json
	$parsed = json_decode($getdata,true); # decode the json into array. set true to return array instead of object

	//get data
	$userid = $parsed['data']['user_id'];
	$username = $parsed['data']['username'];
	$full_name = $parsed['data']['full_name'];
	$img = $parsed['data']['image_url'];
	$video = $parsed['data']['video_url'];
	$caption = $parsed['data']['caption'];
	$likes = $parsed['data']['likes'];
	$comments = $parsed['data']['comments'];
	$location = $parsed['data']['location'];
	$tagged_users = $parsed['data']['tagged_users'];

	echo " <h3>Data : </h3>";

	// check if post not contain video, display photos, if yes, display video
	if($video == "")
	{
		foreach($img as $img)
		{
			echo "<img src='$img' title='photo' height='300px'>";
		}
	}
	else
	{
		echo "
			<video controls>
			  <source src='$video' type='video/mp4'>
			Your browser does not support the video tag.
			</video>
			";
	}
	

	echo "
				<br>
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
	
	foreach($tagged_users as $users)
	{
		echo $users."<br>";
	}

	echo " </td></tr>";
			
	echo " </table>";	
}

?>