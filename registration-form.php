<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php require "assets/header.php"; ?>

    <main>
        <section class="registration-form">
            <h1>Register</h1>
            <form action="after-registration.php" method="POST">
                <input class="reg-input" type="text" name="name" placeholder="Full Name" required><br>
                <input class="reg-input" type="email" name="email" placeholder="Email" required><br>
                <input class="reg-input password-first" type="password" name="password" placeholder="Password" required><br>
                <input class="reg-input password-second" type="password" name="password-again" placeholder="Confirm Password" required><br>
                <input class="btn" type="submit" value="Register">
                <p class="result-text"></p>
            </form>
        </section>
    </main>

    <?php require "assets/footer.php"; ?>
    <script src="./js/passwordchecker.js"></script>
</body>
</html>