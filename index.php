<!DOCTYPE html>
<html>
  <head>
    <title>Song Request to a discord webhook</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script>
      $(document).ready(function() {
        $('#request-form').submit(function(e) {
          e.preventDefault();
          var title = $('#title').val();
          var artist = $('#artist').val();
          var message = $('#message').val();
          $.ajax({
            url: 'sendrequest.php',
            type: 'POST',
            data: {
              title: title,
              artist: artist,
              message: message
            },
            success: function(response) {
              if (response.success) {
                alert(response.message);
                $('#title').val('');
                $('#artist').val('');
                $('#message').val('');
              } else {
                alert(response.message);
              }
            },
            error: function(xhr, status, error) {
              alert('Error sending request: ' + error);
            }
          });
        });
      });
    </script>
  </head>
  <body>
    <h1>Song Request Form</h1>
    <form id="request-form">
      <label for="title">Song Title:</label>
      <input type="text" id="title" name="title" required><br><br>
      <label for="artist">Artist:</label>
      <input type="text" id="artist" name="artist" required><br><br>
      <label for="message">Message:</label>
      <input type="text" id="message" name="message"><br><br>
      <button type="submit">Submit Request</button>
    </form>
  </body>
</html>
