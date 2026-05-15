<?php

$token = $_GET['token'] ?? '';

?>

<!DOCTYPE html>
<html>
<head>

    <title>Reset Password</title>

    <link rel="stylesheet"
          href="../assets/css/reset_password.css">

</head>

<body>

<div class="container">

    <form id="resetPasswordForm">

        <h2>Reset Password</h2>

        <input type="hidden"
               name="token"
               value="<?php echo htmlspecialchars($token); ?>">

        <input type="password"
               name="password"
               id="password"
               placeholder="New Password"
               required>

        <input type="password"
               name="confirm_password"
               placeholder="Confirm Password"
               required>

        <button type="submit">

            Update Password

        </button>

        <div id="message"></div>

    </form>

</div>

<script src="../assets/js/reset_password.js"></script>

</body>
</html>