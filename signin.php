<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <?php require "assets/header.php"; ?>

    <main>
        <section class="form">
            <h1>Log-in</h1>
            <form action="after-signin.php" method="POST">
                <input class="email" type="email" name="email" placeholder="Email" required><br>
                <input class="password" type="password" name="password" placeholder="Password" required><br>
                <input class="btn" type="submit" value="Log-in">
            </form>
        </section>
    </main>

    <?php require "assets/footer.php"; ?>
</body>
</html>