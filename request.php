<?php
/*
made by: djkeiran.co.uk
last updated: 09/03/23
time: 22:58
*/

// Set rate limit configuration
$maxRequestsPerDuration = 5;
$rateLimitDurationInSeconds = 60;

// Connect to Redis server
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

// Get client IP address
$clientIp = $_SERVER['REMOTE_ADDR'];

// Generate cache key for client IP address
$cacheKey = "rate_limit_$clientIp";

// Get number of requests made in the last X minutes
$requestCount = $redis->get($cacheKey);

if ($requestCount === false) {
  // No previous requests made, set count to 1 and cache for the duration
  $redis->setex($cacheKey, $rateLimitDurationInSeconds, 1);
} elseif ($requestCount < $maxRequestsPerDuration) {
  // Increment request count and update cache for the duration
  $redis->incr($cacheKey);
} else {
  // Rate limit exceeded, return error response
  header('Content-Type: application/json');
  echo json_encode(array(
    'success' => false,
    'message' => "Rate limit exceeded. Maximum of $maxRequestsPerDuration requests per $rateLimitDurationInSeconds seconds allowed",
  ));
  exit;
}

// Get the remaining time until the rate limit resets
$remainingTime = $redis->ttl($cacheKey);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = isset($_POST['title']) ? htmlspecialchars($_POST['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
  $artist = isset($_POST['artist']) ? htmlspecialchars($_POST['artist'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
  $message = isset($_POST['message']) ? htmlspecialchars($_POST['message'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';

  // Validate input
  if (empty($title) || empty($artist)) {
    header('Content-Type: application/json');
    echo json_encode(array(
      'success' => false,
      'message' => "Title and artist parameters are required",
    ));
    exit;
  }

  // Prevent XSS attacks
  $title = htmlspecialchars($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $artist = htmlspecialchars($artist, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $message = htmlspecialchars($message, ENT_QUOTES | ENT_HTML5, 'UTF-8');

  $searchUrl = "https://api.deezer.com/2.0/search?q=" . urlencode($title . ' ' . $artist);
  $searchResult = json_decode(file_get_contents($searchUrl), true);

  if (isset($searchResult['data']) && count($searchResult['data']) > 0) {
    $track = $searchResult['data'][0];

    $songName = htmlspecialchars($track['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $artistName = htmlspecialchars($track['artist']['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $artUrl = htmlspecialchars($track['album']['cover_big'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $discordUrl = "https://discord.com/api/webhooks/your_webhook_url_here"; // set your web hook here 
    $data = array(
      'username' => 'Song Request',
      'content' => '',
      'embeds' => array(
        array(
          'title' => $songName,
          'description' => $artistName,
          'thumbnail' => array(
            'url' => $artUrl,
          ),
          'color' => 0,
          'fields' => array(
            array(
              'name' => 'Message',
              'value' => $message,
            ),
          ),
        ),
      ),
    );
    $options = array(
      'http' => array(
        'header' => "Content-Type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($data),
      ),
    );
    $context = stream_context_create($options);
    $result = file_get_contents($discordUrl, false, $context);

    header('Content-Type: application/json');
    echo json_encode(array(
      'success' => true,
      'message' => "message sent to discord title '$title' and artist '$artist' message '$message'",
    ));
  } else {
    header('Content-Type: application/json');
    echo json_encode(array(
      'success' => false,
      'message' => "No tracks found with title '$title' and artist '$artist'",
    ));
  }
}
?>
