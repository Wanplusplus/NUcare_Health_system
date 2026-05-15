<!DOCTYPE html>
<html>
<head>

    <title>Forgot Password</title>

    <link rel="stylesheet"
          href="../assets/css/forgot_password.css">

</head>

<body>

<div class="container">

    <form id="forgotPasswordForm">

        <h2>Forgot Password</h2>

        <p>
            Enter your email address to reset your password.
        </p>

        <input type="email"
               name="email"
               placeholder="Enter Email"
               required>

        <button type="submit">

            Send Reset Link

        </button>

        <div id="message"></div>

    </form>

</div>

<script src="../assets/js/forgot_password.js"></script>

</body>
</html>