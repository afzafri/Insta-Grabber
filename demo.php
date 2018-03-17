<html>
<head>
<title>Fetch Instagram's Photo Data</title>
<!-- Tell the browser to be responsive to screen width -->
<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

<!-- jQuery -->
<script
  src="https://code.jquery.com/jquery-3.3.1.min.js"
  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
  crossorigin="anonymous"></script>

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

<a href="https://github.com/afzafri/Insta-Grabber"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://camo.githubusercontent.com/365986a132ccd6a44c23a9169022c0b5c890c387/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f7265645f6161303030302e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_red_aa0000.png"></a>
</head>
<body>

<div class="container">

<br>


<div class="panel panel-success">
	<div class="panel-heading">
		<strong>Fetch Instagrams's Photo Data</strong>
	</div>
	
	<div class="panel-body">

	<ul class="nav nav-tabs">

		<?php
			$postDataActive = "";
			$profileDataActive = "";
			if(isset($_POST['postData']))
			{
				$postDataActive = "active";
			}
			else if(isset($_POST['profileData']))
			{
				$profileDataActive = "active";
			}
			else
			{
				$postDataActive = "active";
			}
		?>
	 
		<li class="<?php echo $postDataActive; ?>"><a data-toggle="tab" href="#menu1">Fetch Post Picture</a></li>

      	<li class="<?php echo $profileDataActive; ?>"><a data-toggle="tab" href="#menu2">Fetch Profile Picture</a></li>
	</ul>

	<div class="tab-content">
	  <div id="menu1" class="tab-pane fade in <?php echo $postDataActive; ?>">
	   
	   	<br>
	  	<div class="form-group">

			<form action="./demo.php" method="post">
				<label for="url">Instagram Photo URL: </label>

				<div class="input-group">
					<input class="form-control" type="text" placeholder="Enter the Instagram Photo/Video post URL here" name="url" value="<?php echo  (isset($_POST['url'])) ? $_POST['url'] : ""; ?>">
					<span class="input-group-btn">
						<input class="btn btn-success" type="submit" name="postData">
					</span>
				</div>
			</form>

		</div>

	  </div>
	  <div id="menu2" class="tab-pane fade in <?php echo $profileDataActive; ?>">
	    
	  	<br>
	  	<div class="form-group">

			<form action="./demo.php" method="post">
				<label for="url">Instagram Username: </label>

				<div class="input-group">
					<span class="input-group-addon"><i>@</i></span>
					<input class="form-control" type="text" placeholder="Enter username" name="username" value="<?php echo  (isset($_POST['username'])) ? $_POST['username'] : ""; ?>">
					<span class="input-group-btn">
						<input class="btn btn-success" type="submit" name="profileData">
					</span>
				</div>
			</form>

		</div>

	  </div>
	</div>


	</div>
</div>


<?php

if(isset($_POST['postData']))
{

	?>

	<div class="panel panel-info">
		<div class="panel-heading">
			<strong>Results:</strong>
		</div>

		<div class="panel-body">
			<center>

	<?php

				$url = "http://localhost/instafetch/index.php?postUrl=".$_POST['url']; # the full URL to the API including the instagram post url
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
				$location_name = $parsed['data']['location']['name'];
				$location_url = $parsed['data']['location']['url'];
				$tagged_users = $parsed['data']['tagged_users'];

				// loop to display all photo and videos available
				$countImg = 0;
				$carouselindphoto = "";
				$carouselimgs = "";
				foreach($img as $img)
				{
					$countImg++;

					$activetag = "";
					if($countImg == 1)
					{
						$activetag = "active";
					}

					$carouselimgs .= "<div class='item $activetag'>
						                <img src='$img' alt='Photo #".($countImg+1)."' height='450px' class='img-responsive img-thumbnail'/>
						            </div>";

					$carouselindphoto .= "<li data-target='#carouselPhotos' data-slide-to='$countImg' class='$activetag'></li>";
				}

				echo "<h4>Photos</h4>";
				echo "<div id='carouselPhotos' class='carousel slide' data-ride='carousel'>
				        <!-- Carousel indicators -->
				        <ol class='carousel-indicators'>
				            $carouselindphoto
				        </ol>   
				        <!-- Wrapper for carousel items -->
				        <div class='carousel-inner'>";
				        
				echo $carouselimgs;
			
				echo "</div>
				        <!-- Carousel controls -->
				        <a class='carousel-control left' href='#carouselPhotos' data-slide='prev'>
				            <span class='glyphicon glyphicon-chevron-left'></span>
				        </a>
				        <a class='carousel-control right' href='#carouselPhotos' data-slide='next'>
				            <span class='glyphicon glyphicon-chevron-right'></span>
				        </a>
				    </div><br><br>";

				$countVid = 0;
				$carouselindvid = "";
				$carouselvids = "";

				foreach($video as $video)
				{
					// if no video, do not show the video player
					if(!empty($video))
					{
						$countVid++;

						$activetag = "";
						if($countVid == 1)
						{
							$activetag = "active";
						}

						$carouselvids .= "
						<div class='item $activetag'>
							<video height='450px' controls class='img-responsive img-thumbnail'>
							  <source src='$video' type='video/mp4'>
							Your browser does not support the video tag.
							</video>
						</div>
						";

						$carouselindvid .= "<li data-target='#carouselVideos' data-slide-to='$countVid' class='$activetag'></li>";
					}
				}

				// if videos available, show the videos
				if($countVid > 0)
				{
					echo "<h4>Videos</h4>";
					echo "<div id='carouselVideos' class='carousel slide' data-ride='carousel'>
				        <!-- Carousel indicators -->
				        <ol class='carousel-indicators'>
				            $carouselindphoto
				        </ol>   
				        <!-- Wrapper for carousel items -->
				        <div class='carousel-inner'>";
				        
					echo $carouselvids;
				
					echo "</div>
					        <!-- Carousel controls -->
					        <a class='carousel-control left' href='#carouselVideos' data-slide='prev'>
					            <span class='glyphicon glyphicon-chevron-left'></span>
					        </a>
					        <a class='carousel-control right' href='#carouselVideos' data-slide='next'>
					            <span class='glyphicon glyphicon-chevron-right'></span>
					        </a>
					    </div><br><br>";
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
								<td><a href='$location_url' target='_blank'>$location_name</a></td>
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
								echo "<a href='https://www.instagram.com/$users/' target='_blank'>$users</a><br>";
							}

				echo " 			</td>
							</tr>";
						
				echo " </table>";	

	echo "
			<br><br>
			<i>Afif Zafri &copy; 2017</i>
			</center>

	</div>";

}
else if(isset($_POST['profileData']))
{

	?>

	<div class="panel panel-info">
		<div class="panel-heading">
			<strong>Results:</strong>
		</div>

		<div class="panel-body">
			<center>

	<?php

				$url = "http://localhost/instafetch/index.php?username=".$_POST['username']; # the full URL to the API including the instagram post url
				$getdata = file_get_contents($url); # use files_get_contents() to fetch the data, but you can also use cURL, or javascript/jquery json
				$parsed = json_decode($getdata,true); # decode the json into array. set true to return array instead of object

				//get data
				$userid = $parsed['data']['user_id'];
				$username = $parsed['data']['username'];
				$full_name = $parsed['data']['full_name'];
				$biography = $parsed['data']['biography'];
				$external_url = $parsed['data']['external_url'];
				$followedby = $parsed['data']['followedby'];
				$follows = $parsed['data']['follows'];
				$no_posts = $parsed['data']['no_posts'];
				$profilepic = $parsed['data']['profilepic'];

				echo "
							<h4>Profile Picture</h4>
							<img src='$profilepic' title='Profile Picture' height='450px' class='img-responsive img-thumbnail'/>

							<br><br>
							
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
								<th class='bg-info text-white'>Bio :</th>
								<td>$biography</td>
							</tr>
							<tr>
								<th class='bg-info text-white'>Website :</th>
								<td><a href='$external_url' target='_blank'>$external_url</a></td>
							</tr>
							<tr>
								<th class='bg-info text-white'>Followers :</th>
								<td>$followedby</td>
							</tr> 
							<tr>
								<th class='bg-info text-white'>Following :</th>
								<td>$follows</td>
							</tr>
							<tr>
								<th class='bg-info text-white'>Number of posts :</th>
								<td>$no_posts</td>
							</tr>
							";
						
				echo " </table>";	

	echo "
			<br><br>
			<i>Afif Zafri &copy; 2017</i>
			</center>

	</div>";

}

?>
		

</div>
</body>
</html>