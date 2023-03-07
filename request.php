<?php
/*
made by: djkeiran.co.uk
last updated: 07/03/23
time: 21:03
*/

$title = isset($_GET['title']) ? trim($_GET['title']) : '';
$artist = isset($_GET['artist']) ? trim($_GET['artist']) : '';
$message = isset($_GET['message']) ? trim($_GET['message']) : '';

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
?>
