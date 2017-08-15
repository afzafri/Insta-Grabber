# Insta Grabber - Unofficial Instagram API
- Grab Instagram's post data from Instagram website, and return a JSON formatted strings.
- Note: 
  - This is not the official API, this is actually just a "hack", or workaround for obtaining the data. This might not last forever.
  - Only work for public instagram accounts, cannot fetch data from a private account.

## Updates
- 15/08/2017
	- Update the API since Instagram now change the JSON data structure.
- 02/03/2017
	- Update script to grab all photos from Instagram post. Since now Instagram has allowed users to upload multiple photos per post.
	- Now script will grab video url, if the Instagram post is a video.
	- Redesigned UI for Demo page. Using Bootstrap framework.
- 20/02/2017
	- Created a separated page/file for demo.
	- API (index.php) only scrape instagram post, and return a new JSON formatted strings.
	- Check if data found, display message

## Created By
1. Afif Zafri
2. Date : 29/12/2015
3. Contact Me : http://fb.me/afzafri

## Installation

Drop all files into your server

## Usage
- ```http://site.com/index.php?url=POSTURL```
- where ```POSTURL``` is the instagram picture url.
- It will then return a JSON formatted string, you can parse the JSON string and do what you want with it.

## Credits

1. References : http://stackoverflow.com/
2. Sensei : Mohd Shahril

## License
This library is under ```MIT license```, please look at the LICENSE file
