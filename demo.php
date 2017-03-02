<html>
<head>
<title>Fetch Instagram's Photo Data</title>
<!-- Tell the browser to be responsive to screen width -->
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

<!-- Bootstrap -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

<style type="text/css">
	body { 
	  	background: #f09433; 
		background: -moz-linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); 
		background: -webkit-linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%); 
		background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%); 
		filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#f09433', endColorstr='#bc1888',GradientType=1 );
	 }
</style>
</head>
<body>

<div class="container">

<br>


<div class="panel panel-success">
	<div class="panel-heading">
		<strong>Fetch Instagrams's Photo Data</strong>
	</div>
	
	<div class="panel-body">
		<div class="form-group">

			<form action="./demo.php" method="post">
				<label for="url">Instagram Photo URL: </label>

				<div class="input-group">
					<input class="form-control" type="text" placeholder="Enter the Instagram Photo/Video post URL here" name="url" value="<?php echo  (isset($_POST['url'])) ? $_POST['url'] : ""; ?>">
					<span class="input-group-btn">
						<input class="btn btn-success" type="submit" name="submit">
					</span>
				</div>
			</form>

		</div>
	</div>
</div>



<?php

if(isset($_POST['submit']))
{

	?>

	<div class="panel panel-info">
		<div class="panel-heading">
			<strong>Results:</strong>
		</div>

		<div class="panel-body">
			<center>

	<?php

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

				// check if post not contain video, display photos, if yes, display video
				if($video == "")
				{
					foreach($img as $img)
					{
						echo "<img src='$img' title='photo' height='450px' class='img-responsive img-thumbnail'><br><br>";
					}
				}
				else
				{
					echo "
						<video height='450px' controls class='img-responsive img-thumbnail'>
						  <source src='$video' type='video/mp4'>
						Your browser does not support the video tag.
						</video>
						<br>
						";
				}
				

				echo "
							<br>
							<div class='alert alert-warning'>
								<i>Caption : $caption</i>
							</div>

							<br>
							
							<table class='table table-responsive table-bordered table-hover'>
							<tr>
								<th class='bg-info text-white'>User ID :</th>
								<td>$userid</td>
							</tr>
							<tr>
								<th class='bg-info text-white'>Username :</th>
								<td>$username</td>
							</tr>
							<tr>
								<th class='bg-info text-white'>Full Name :</th>
								<td>$full_name</td>
							</tr>
							<tr>
								<th class='bg-info text-white'>Location :</th>
								<td>$location</td>
							</tr>
							<tr>
								<th class='bg-info text-white'>No. Comments :</th>
								<td>$comments</td>
							</tr>
							<tr>
								<th class='bg-info text-white'>No. Likes :</th>
								<td>$likes</td>
							</tr> ";
				
				echo " 		<tr>
								<th class='bg-info text-white'>Users in Photo :</th>

								<td>";	
				
							foreach($tagged_users as $users)
							{
								echo $users."<br>";
							}

				echo " 			</td>
							</tr>";
						
				echo " </table>";	

	echo "</center>

	</div>";

}

?>
		

</div>
</body>
</html>