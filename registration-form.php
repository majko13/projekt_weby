<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Document</title>
</head>
<body>

    <?php require "assets/header.php"; ?>

    <main>
        <section class="registration-form">
            <h1>Register</h1>
            <form action="after-registration.php" method="POST">
                <input class="reg-input" type="text" name="username" placeholder="Name"><br>
                <input class="reg-input password-first" type="password" name="password" placeholder="Password"><br>
                <input class="reg-input password-second" type="password" name="password-again" placeholder="Password again"><br>
                <input class="btn" type="submit" value="Register">
                <p class="result-text"></p>
            </form>
        </section>
    </main>

    <?php require "assets/footer.php"; ?>
    <script src="./js/passwordchecker.js"></script>
</body>
</html>