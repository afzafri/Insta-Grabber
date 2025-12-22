<?php

/*  Instagram Post API created by Afif Zafri.
    Post details are fetched directly from Instagram website,
    parse the content, and return JSON formatted string.
    Please note that this is not the official API, this is actually just a "hack",
    or workaround.

    1. For fetching post data
    Usage: http://site.com/index.php?postUrl=URLPOST , where URLPOST is the Instagram post url

    2. For fetching user's profile data
	Usage: http://site.com/index.php?username=USERNAME , where USERNAME is the Instagram user profile url
*/

header("Access-Control-Allow-Origin: *"); # enable CORS

// Helper function to extract meta tag content
function getMetaTag($html, $property) {
    if (preg_match('/<meta\s+(?:property|name)="' . preg_quote($property, '/') . '"\s+content="([^"]*)"/', $html, $matches)) {
        return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    return null;
}

// Helper function to extract username from description
function extractUsername($description) {
    // Format: "83 likes, 5 comments - afzafri on April 10, 2024: ..."
    if (preg_match('/- ([a-zA-Z0-9_.]+) on /', $description, $matches)) {
        return $matches[1];
    }
    return null;
}

// Helper function to extract likes and comments
function extractStats($description) {
    $likes = 0;
    $comments = 0;

    if (preg_match('/(\d+)\s+like/', $description, $matches)) {
        $likes = (int)$matches[1];
    }

    if (preg_match('/(\d+)\s+comment/', $description, $matches)) {
        $comments = (int)$matches[1];
    }

    return array('likes' => $likes, 'comments' => $comments);
}

// Helper function to extract caption from description
function extractCaption($description) {
    // Decode HTML entities first (&quot; -> ", &#039; -> ', etc.)
    $description = html_entity_decode($description, ENT_QUOTES, 'UTF-8');

    // Format: "83 likes, 5 comments - afzafri on April 10, 2024: "caption text"."
    if (preg_match('/: "([^"]+)"/', $description, $matches)) {
        return $matches[1];
    }
    // Try alternative format without quotes
    if (preg_match('/\d{4}: (.+)\.\s*$/', $description, $matches)) {
        return trim($matches[1]);
    }
    return null;
}

// Helper function to extract image ID from URL
function getImageIdFromUrl($url) {
    // Extract the unique image ID from URL (e.g., 488072845_18493597114029755)
    if (preg_match('/\/([0-9]+_[0-9]+)/', $url, $matches)) {
        return $matches[1];
    }
    return null;
}

// Helper function to extract efg parameter from URL (encoding format)
function getEfgFromUrl($url) {
    // Extract efg parameter
    if (preg_match('/[&?]efg=([^&]+)/', $url, $matches)) {
        return urldecode($matches[1]);
    }
    return null;
}

// Helper function to extract ig_cache_key from URL (unique per image)
function getCacheKeyFromUrl($url) {
    // Extract ig_cache_key parameter which is unique to each image
    if (preg_match('/[&?]ig_cache_key=([^&]+)/', $url, $matches)) {
        // Get the base ID without version suffix
        $cacheKey = urldecode($matches[1]);
        // Remove version suffix like ".3-ccb7-5"
        $cacheKey = preg_replace('/\.\d+-.*$/', '', $cacheKey);
        return $cacheKey;
    }
    return null;
}

// Helper function to extract media ID from ig_cache_key
function getMediaIdFromCacheKey($cacheKey) {
    // Cache key is base64 encoded, decode to get media ID
    $decoded = base64_decode($cacheKey);
    // Media ID is typically the numeric part
    if (preg_match('/(\d+)/', $decoded, $matches)) {
        return $matches[1];
    }
    return null;
}

// Helper function to categorize image resolution
function categorizeResolution($url, $width = null, $height = null) {
    // Check URL patterns for resolution indicators
    if (preg_match('/dst-jpg_e35_s(\d+)x(\d+)/', $url, $matches)) {
        $size = max((int)$matches[1], (int)$matches[2]);
    } else if (preg_match('/[._]p(\d+)x(\d+)/', $url, $matches)) {
        $size = max((int)$matches[1], (int)$matches[2]);
    } else if ($height) {
        $size = $height;
    } else if ($width) {
        $size = $width;
    } else {
        return 'original';
    }

    // Categorize based on size
    if ($size >= 1800) return 'original';
    if ($size >= 1080) return 'fhd';
    if ($size >= 720) return 'hd';
    if ($size >= 480) return 'sd';
    return 'thumbnail';
}

// Helper function to extract data from data-sjs JSON elements
function extractDataSjs($html, $postUrl = null, $targetCaption = null) {
    $result = array(
        'images' => array(),
        'images_by_resolution' => array(), // New: grouped by image ID and resolution
        'videos' => array(),
        'caption' => null,
        'likes' => 0,
        'comments' => 0,
        'location' => array(
            'name' => null,
            'id' => null,
            'slug' => null,
            'lat' => null,
            'lng' => null,
            'url' => null
        ),
        'tagged_users' => array()
    );

    // Extract shortcode from post URL if provided (e.g., /p/C5koM8BpURA/ -> C5koM8BpURA)
    $shortcode = null;
    if ($postUrl && preg_match('/\/p\/([^\/]+)/', $postUrl, $matches)) {
        $shortcode = $matches[1];
    }

    // Normalize target caption for comparison (trim and remove extra spaces)
    if ($targetCaption) {
        $targetCaption = trim(preg_replace('/\s+/', ' ', $targetCaption));
    }

    // Find all script tags with data-sjs
    preg_match_all('/<script type="application\/json"[^>]*data-sjs[^>]*>(.*?)<\/script>/s', $html, $matches);

    // Initialize reference media ID (will be set from first valid image in target post)
    $referenceMediaId = null;

    // FIRST PASS: Look for shortcode-specific API data (xdt_api__v1__media__shortcode__web_info)
    // This contains ONLY the requested post, not a feed
    $foundShortcodeData = false;
    foreach ($matches[1] as $json_str) {
        if (strpos($json_str, 'xdt_api__v1__media__shortcode__web_info') !== false) {
            $data = json_decode($json_str, true);
            if ($data && json_last_error() === JSON_ERROR_NONE) {
                findMediaUrls($data, $result, $shortcode, true, $targetCaption, $referenceMediaId);
                $foundShortcodeData = true;
            }
        }
    }

    // SECOND PASS: If no shortcode-specific data found, process all scripts with caption filtering
    // This handles profile timeline feeds that contain multiple posts mixed together
    if (!$foundShortcodeData) {
        foreach ($matches[1] as $json_str) {
            $data = json_decode($json_str, true);
            if ($data && json_last_error() === JSON_ERROR_NONE) {
                // Use strict caption matching to filter out other posts
                findMediaUrls($data, $result, $shortcode, false, $targetCaption, $referenceMediaId);
            }
        }
    }

    // Build simplified image array with only highest resolution of each unique image
    foreach ($result['images_by_resolution'] as $imageId => $resolutions) {
        // Prefer original, then fhd, then hd, then sd, then thumbnail
        $priority = ['original', 'fhd', 'hd', 'sd', 'thumbnail'];
        foreach ($priority as $res) {
            if (isset($resolutions[$res])) {
                $result['images'][] = $resolutions[$res];
                break;
            }
        }
    }

    return $result;
}

// Recursive function to find media URLs in nested JSON
function findMediaUrls($data, &$result, $shortcode = null, $inTargetPost = false, $targetCaption = null, &$referenceMediaId = null) {
    if (is_array($data)) {
        // If not already in target post, check if we should process this node
        if (!$inTargetPost) {
            // Check if we're in the target post data by shortcode
            if ($shortcode && isset($data['code']) && $data['code'] === $shortcode) {
                $inTargetPost = true;
            }
            // Alternative check: look for shortcode field
            else if ($shortcode && isset($data['shortcode']) && $data['shortcode'] === $shortcode) {
                $inTargetPost = true;
            }
            // Check caption to verify we're in the correct post (used when processing feeds)
            else if ($targetCaption) {
                // Look for caption in new format (items array)
                $currentCaption = null;
                if (isset($data['caption']['text'])) {
                    $currentCaption = trim(preg_replace('/\s+/', ' ', $data['caption']['text']));
                }
                // Old format
                else if (isset($data['edge_media_to_caption']['edges'][0]['node']['text'])) {
                    $currentCaption = trim(preg_replace('/\s+/', ' ', $data['edge_media_to_caption']['edges'][0]['node']['text']));
                }

                if ($currentCaption) {
                    // If caption doesn't match, this is a different post - skip it entirely
                    if ($currentCaption !== $targetCaption) {
                        return; // Exit early, don't process images from different posts
                    }
                    // Caption matches - we're in the target post
                    $inTargetPost = true;
                }
            }
        }

        foreach ($data as $key => $value) {
            // Look for candidates array (Instagram's new format with multiple resolutions)
            if ($key === 'candidates' && is_array($value)) {
                // Process all candidates to group by resolution
                foreach ($value as $candidate) {
                    if (isset($candidate['url']) && !empty($candidate['url'])) {
                        $url = $candidate['url'];

                        // Domain filtering: Only apply when NOT in confirmed target post
                        // When $inTargetPost is true (from shortcode-specific data), accept all URLs
                        // Otherwise filter to scontent domains only
                        if (!$inTargetPost) {
                            if (strpos($url, 'scontent.cdninstagram.com') === false &&
                                strpos($url, 'scontent-') === false) {
                                continue; // Skip images from other domains when processing feeds
                            }
                        }

                        // Extract image ID and categorize resolution
                        $imageId = getImageIdFromUrl($url);
                        if ($imageId) {
                            $width = isset($candidate['width']) ? $candidate['width'] : null;
                            $height = isset($candidate['height']) ? $candidate['height'] : null;
                            $resolution = categorizeResolution($url, $width, $height);

                            // Store by image ID and resolution
                            if (!isset($result['images_by_resolution'][$imageId])) {
                                $result['images_by_resolution'][$imageId] = array();
                            }

                            // Only keep the first URL for each resolution category
                            if (!isset($result['images_by_resolution'][$imageId][$resolution])) {
                                $result['images_by_resolution'][$imageId][$resolution] = $url;
                            }
                        }
                    }
                }
            }
            // Look for display_url (old format - high quality images)
            else if ($key === 'display_url' && is_string($value) && !empty($value)) {
                // Domain filtering: Only apply when NOT in confirmed target post
                $passedDomainFilter = $inTargetPost ||
                                     (strpos($value, 'scontent.cdninstagram.com') !== false ||
                                      strpos($value, 'scontent-') !== false);

                if ($passedDomainFilter) {
                    $imageId = getImageIdFromUrl($value);
                    if ($imageId) {
                        if (!isset($result['images_by_resolution'][$imageId])) {
                            $result['images_by_resolution'][$imageId] = array();
                        }
                        // display_url is usually original quality
                        if (!isset($result['images_by_resolution'][$imageId]['original'])) {
                            $result['images_by_resolution'][$imageId]['original'] = $value;
                        }
                    }
                }
            }
            // Look for video URLs
            else if ($key === 'video_url' && is_string($value) && !empty($value)) {
                // Domain filtering: Only apply when NOT in confirmed target post
                $passedDomainFilter = $inTargetPost ||
                                     (strpos($value, 'scontent.cdninstagram.com') !== false ||
                                      strpos($value, 'scontent-') !== false);

                if ($passedDomainFilter) {
                    if (!in_array($value, $result['videos'])) {
                        $result['videos'][] = $value;
                    }
                }
            }
            // Look for carousel media (multiple images)
            else if ($key === 'edge_sidecar_to_children' && isset($value['edges'])) {
                foreach ($value['edges'] as $edge) {
                    if (isset($edge['node'])) {
                        findMediaUrls($edge['node'], $result, $shortcode, $inTargetPost, $targetCaption, $referenceMediaId);
                    }
                }
            }
            // Look for caption
            else if ($key === 'edge_media_to_caption' && isset($value['edges'][0]['node']['text'])) {
                if (empty($result['caption'])) {
                    $result['caption'] = $value['edges'][0]['node']['text'];
                }
            }
            // Look for likes count
            else if ($key === 'edge_media_preview_like' && isset($value['count'])) {
                $result['likes'] = max($result['likes'], (int)$value['count']);
            }
            // Look for comments count
            else if ($key === 'edge_media_to_comment' && isset($value['count'])) {
                $result['comments'] = max($result['comments'], (int)$value['count']);
            }
            // Look for location
            else if ($key === 'location' && is_array($value)) {
                if (isset($value['name'])) $result['location']['name'] = $value['name'];

                // Support both 'id' (old format) and 'pk' (new format)
                if (isset($value['id'])) {
                    $result['location']['id'] = $value['id'];
                } else if (isset($value['pk'])) {
                    $result['location']['id'] = $value['pk'];
                }

                if (isset($value['slug'])) $result['location']['slug'] = $value['slug'];

                // Extract coordinates (new format)
                if (isset($value['lat'])) $result['location']['lat'] = $value['lat'];
                if (isset($value['lng'])) $result['location']['lng'] = $value['lng'];

                // Build location URL (only if we have both id and slug)
                if (!empty($result['location']['id']) && !empty($value['slug'])) {
                    $result['location']['url'] = 'https://www.instagram.com/explore/locations/' . $result['location']['id'] . '/' . $value['slug'] . '/';
                }
            }
            // Look for tagged users (old format)
            else if ($key === 'edge_media_to_tagged_user' && isset($value['edges'])) {
                foreach ($value['edges'] as $edge) {
                    if (isset($edge['node']['user']['username'])) {
                        $username = $edge['node']['user']['username'];
                        if (!in_array($username, $result['tagged_users'])) {
                            $result['tagged_users'][] = $username;
                        }
                    }
                }
            }
            // Look for tagged users (new format: usertags.in)
            else if ($key === 'usertags' && isset($value['in']) && is_array($value['in'])) {
                foreach ($value['in'] as $tag) {
                    if (isset($tag['user']['username'])) {
                        $username = $tag['user']['username'];
                        if (!in_array($username, $result['tagged_users'])) {
                            $result['tagged_users'][] = $username;
                        }
                    }
                }
            }
            // Recursively search nested arrays/objects
            else if (is_array($value)) {
                findMediaUrls($value, $result, $shortcode, $inTargetPost, $targetCaption, $referenceMediaId);
            }
        }
    }
}

//get instagram's post(pictures) data
if(isset($_GET['postUrl']))
{
	$url = $_GET['postUrl'];

	# use cURL instead of file_get_contents(), this is because on some server, file_get_contents() cannot be used
    # cURL also have more options and customizable
    $ch = curl_init(); # initialize curl object
    curl_setopt($ch, CURLOPT_URL, $url); # set url
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); # receive server response
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); # do not verify SSL
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    # Add browser headers to avoid bot detection
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9',
        'Accept-Encoding: gzip, deflate, br, zstd',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Sec-Fetch-User: ?1',
        'sec-ch-ua: "Chromium";v="120", "Google Chrome";v="120", "Not_A Brand";v="99"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "Windows"',
        'dpr: 2',
        'viewport-width: 1920',
        'Cache-Control: no-cache, no-store, must-revalidate',
        'Pragma: no-cache',
        'Expires: 0'
    ));
    curl_setopt($ch, CURLOPT_ENCODING, ''); # handle compressed responses

    # Disable cURL's internal cache
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, true);

    $data = curl_exec($ch); # execute curl, fetch webpage content
    $curl_error = curl_error($ch);
    $httpstatus = curl_getinfo($ch, CURLINFO_HTTP_CODE); # receive http response status
    curl_close($ch);  # close curl

	//initialized new associative array, for storing the data
	$jsondata = array();
	$jsondata['http_code'] = $httpstatus; # set http response code into the array

	if($httpstatus == 200 && !empty($data))
	{
		// Extract data from meta tags first (needed for caption comparison)
		$og_title = getMetaTag($data, 'og:title');
		$og_description = getMetaTag($data, 'og:description');
		$og_image = getMetaTag($data, 'og:image');
		$og_url = getMetaTag($data, 'og:url');
		$owner_user_id = getMetaTag($data, 'instapp:owner_user_id');
		$media_id = getMetaTag($data, 'al:ios:url'); // Contains media ID

		// Extract media ID from al:ios:url (format: instagram://media?id=3342973634362033216)
		$media_id_value = null;
		if ($media_id && preg_match('/id=(\d+)/', $media_id, $matches)) {
			$media_id_value = $matches[1];
		}

		// Extract username and stats from description
		$username = extractUsername($og_description);
		$stats = extractStats($og_description);
		$caption = extractCaption($og_description);

		// Now extract data from data-sjs JSON elements with caption verification
		$sjs_data = extractDataSjs($data, $url, $caption);

		// Extract full name from og:title (format: "Afif Zafri on Instagram: "caption"")
		$full_name = null;
		if ($og_title && preg_match('/^([^:]+) on Instagram:/', $og_title, $matches)) {
			$full_name = trim($matches[1]);
		}

		// Use data-sjs images if available, otherwise fallback to meta tag image
		$img = array();
		if (!empty($sjs_data['images'])) {
			$img = $sjs_data['images'];
		} else if ($og_image) {
			$img[] = $og_image;
		}

		// Use data-sjs videos if available
		$videos = !empty($sjs_data['videos']) ? $sjs_data['videos'] : array();

		// Use data-sjs caption if available, otherwise use meta tag caption
		if (!empty($sjs_data['caption'])) {
			$caption = $sjs_data['caption'];
		}

		// Use data-sjs stats if available, otherwise use meta tag stats
		$likes = $sjs_data['likes'] > 0 ? $sjs_data['likes'] : $stats['likes'];
		$comments = $sjs_data['comments'] > 0 ? $sjs_data['comments'] : $stats['comments'];

		// Use data-sjs location if available
		$location = $sjs_data['location'];

		// Use data-sjs tagged users if available
		$tagged_users = $sjs_data['tagged_users'];

		//store data
		$jsondata['data']['user_id'] = $owner_user_id;
		$jsondata['data']['username'] = $username;
		$jsondata['data']['full_name'] = $full_name;
		$jsondata['data']['media_id'] = $media_id_value;
		$jsondata['data']['image_url'] = $img;
		$jsondata['data']['images_by_resolution'] = $sjs_data['images_by_resolution']; // Grouped by image ID and resolution
		$jsondata['data']['video_url'] = $videos;
		$jsondata['data']['caption'] = $caption;
		$jsondata['data']['likes'] = $likes;
		$jsondata['data']['comments'] = $comments;
		$jsondata['data']['location'] = $location;
		$jsondata['data']['tagged_users'] = $tagged_users;
		$jsondata['data']['post_url'] = $og_url;

		// Add note if data-sjs was not available
		if (empty($sjs_data['images']) && empty($sjs_data['videos'])) {
			$jsondata['note'] = 'Limited data available. Instagram may have restricted access to detailed post data.';
		}
	}
	else
	{
		$jsondata['message'] = "Data not available. Post deleted, account is private, or HTTP error occurred.";
		if (!empty($curl_error)) {
			$jsondata['error'] = $curl_error;
		}
	}

	# project info
    $jsondata['info']['creator'] = "Afif Zafri (afzafri)";
    $jsondata['info']['project_page'] = "https://github.com/afzafri/Insta-Grabber";
    $jsondata['info']['date_updated'] = "19/12/2025";

	// convert the array into JSON strings, and print
	echo json_encode($jsondata);

}
//get user's profile data like the profile picture
else if(isset($_GET['username']))
{
	$url = "https://www.instagram.com/".$_GET['username']."/";

	# use cURL instead of file_get_contents(), this is because on some server, file_get_contents() cannot be used
    # cURL also have more options and customizable
    $ch = curl_init(); # initialize curl object
    curl_setopt($ch, CURLOPT_URL, $url); # set url
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); # receive server response
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); # do not verify SSL
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    # Add browser headers to avoid bot detection
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.9',
        'Accept-Encoding: gzip, deflate, br, zstd',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Sec-Fetch-Dest: document',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-Site: none',
        'Sec-Fetch-User: ?1',
        'sec-ch-ua: "Chromium";v="120", "Google Chrome";v="120", "Not_A Brand";v="99"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "Windows"',
        'dpr: 2',
        'viewport-width: 1920',
        'Cache-Control: no-cache, no-store, must-revalidate',
        'Pragma: no-cache',
        'Expires: 0'
    ));
    curl_setopt($ch, CURLOPT_ENCODING, ''); # handle compressed responses

    # Disable cURL's internal cache
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, true);

    $data = curl_exec($ch); # execute curl, fetch webpage content
    $curl_error = curl_error($ch);
    $httpstatus = curl_getinfo($ch, CURLINFO_HTTP_CODE); # receive http response status
    curl_close($ch);  # close curl

	//initialized new associative array, for storing the data
	$jsondata = array();
	$jsondata['http_code'] = $httpstatus; # set http response code into the array

	if($httpstatus == 200 && !empty($data))
	{
		// Extract data from meta tags
		$og_title = getMetaTag($data, 'og:title');
		$og_description = getMetaTag($data, 'og:description');
		$og_image = getMetaTag($data, 'og:image');
		$owner_user_id = getMetaTag($data, 'instapp:owner_user_id');

		// Extract stats from description
		// Format: "12K Followers, 1,234 Following, 567 Posts - See Instagram photos and videos from Username (@username)"
		$followers = 0;
		$following = 0;
		$posts = 0;
		$username = $_GET['username'];
		$full_name = null;
		$biography = null;

		// Try to extract from og:title first (format: "Full Name (@username) • Instagram photos and videos")
		if ($og_title && preg_match('/^([^(]+)\s+\(@([^)]+)\)/', $og_title, $matches)) {
			$full_name = trim($matches[1]);
			$username = $matches[2];
		}

		// Extract follower stats from description
		if ($og_description) {
			// Match formats like "12K Followers" or "1,234 Followers"
			if (preg_match('/([\d,]+[KM]?)\s+Followers/i', $og_description, $matches)) {
				$followers = $matches[1];
				// Convert K and M to numbers
				if (strpos($followers, 'K') !== false) {
					$followers = (int)str_replace(',', '', $followers) * 1000;
				} elseif (strpos($followers, 'M') !== false) {
					$followers = (int)str_replace(',', '', $followers) * 1000000;
				} else {
					$followers = (int)str_replace(',', '', $followers);
				}
			}

			if (preg_match('/([\d,]+[KM]?)\s+Following/i', $og_description, $matches)) {
				$following = $matches[1];
				if (strpos($following, 'K') !== false) {
					$following = (int)str_replace(',', '', $following) * 1000;
				} elseif (strpos($following, 'M') !== false) {
					$following = (int)str_replace(',', '', $following) * 1000000;
				} else {
					$following = (int)str_replace(',', '', $following);
				}
			}

			if (preg_match('/([\d,]+[KM]?)\s+Posts/i', $og_description, $matches)) {
				$posts = $matches[1];
				if (strpos($posts, 'K') !== false) {
					$posts = (int)str_replace(',', '', $posts) * 1000;
				} elseif (strpos($posts, 'M') !== false) {
					$posts = (int)str_replace(',', '', $posts) * 1000000;
				} else {
					$posts = (int)str_replace(',', '', $posts);
				}
			}
		}

		//store data
		$jsondata['data']['user_id'] = $owner_user_id;
		$jsondata['data']['username'] = $username;
		$jsondata['data']['full_name'] = $full_name;
		$jsondata['data']['biography'] = $biography;
		$jsondata['data']['external_url'] = null;
		$jsondata['data']['followedby'] = $followers;
		$jsondata['data']['follows'] = $following;
		$jsondata['data']['no_posts'] = $posts;
		$jsondata['data']['profilepic'] = $og_image; // Profile picture from og:image

		$jsondata['note'] = 'Due to Instagram changes, some data (biography, external URL) cannot be extracted from meta tags alone.';
	}
	else
	{
		$jsondata['message'] = "Data not available. Account deactivated, account is private, or HTTP error occurred.";
		if (!empty($curl_error)) {
			$jsondata['error'] = $curl_error;
		}
	}

	# project info
    $jsondata['info']['creator'] = "Afif Zafri (afzafri)";
    $jsondata['info']['project_page'] = "https://github.com/afzafri/Insta-Grabber";
    $jsondata['info']['date_updated'] = "19/12/2025";

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
	    or workaround. <br><br>
	    1. For fetching post data<br>
    	Usage: http://site.com/index.php?postUrl=URLPOST , where URLPOST is the Instagram post url<br><br>

    	2. For fetching user's profile data<br>
		Usage: http://site.com/index.php?username=USERNAME , where USERNAME is the Instagram user profile url <br>
	</p>
	<?php
}

?>
