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
        <section class="form">
            <h1>Log-in</h1>
            <form action="after-registration.php" method="POST">
                <input class="email" type="email" name="login-email" placeholder="Name"><br>
                <input class="password" type="password" name="login-password" placeholder="Password"><br>
                <input class="btn" type="submit" value="Log-in">
            </form>
        </section>
    </main>

    <?php require "assets/footer.php"; ?>
    <script src="./js/passwordchecker.js"></script>
</body>
</html>