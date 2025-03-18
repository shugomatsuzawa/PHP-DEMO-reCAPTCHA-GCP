<?php
require 'config.php';

$recaptcha_sitekey = RECAPTCHA_SITEKEY;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>reCAPTCHA Enterprise PHP Sample</title>
  <meta name="robots" content="noindex">
  <script src="https://www.google.com/recaptcha/enterprise.js?render=<?php print $recaptcha_sitekey ?>"></script>
  <script>
    function onSubmit(token) {
      document.getElementById("demo-form").submit();
    }
  </script>
</head>

<body>
  <h1>reCAPTCHA Enterprise PHP Sample</h1>
  <p>reCAPTCHA Enterprise PHP Sample</p>
  <form id="demo-form" action="complete.php" method="POST">
    <input type="text" name="name" placeholder="Name"><br>
    <input type="email" name="email" placeholder="demo@example.com"><br>
    <input type="text" name="subject" placeholder="Subject"><br>
    <textarea name="comment" placeholder="Comment"></textarea><br>
    <button class="g-recaptcha"
      data-sitekey="<?php print $recaptcha_sitekey ?>"
      data-callback='onSubmit'
      data-action='submit'>
      Submit
    </button>
  </form>
</body>

</html>