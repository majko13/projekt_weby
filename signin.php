<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="assets/styles.css?v=<?= time() ?>">
</head>
<body>
    <?php require "assets/header.php"; ?>

    <main>
        <section class="form">
            <h1>Log-in</h1>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert error" id="error-alert">
                    <?php
                    switch($_GET['error']) {
                        case 'invalid_credentials':
                            echo '<strong>Login Failed!</strong> Invalid email or password. Please try again.';
                            break;
                        case 'unauthorized':
                            echo '<strong>Access Denied!</strong> Unauthorized access attempt.';
                            break;
                        default:
                            echo '<strong>Error!</strong> An unexpected error occurred.';
                    }
                    ?>
                </div>
            <?php endif; ?>

            <form action="after-signin.php" method="POST">
                <input class="email" type="email" name="email" placeholder="Email" required><br>
                <input class="password" type="password" name="password" placeholder="Password" required><br>
                <input class="btn" type="submit" value="Log-in">
            </form>
        </section>
    </main>

    <!-- Error Modal -->
    <div class="modal-overlay" id="error-modal">
        <div class="modal-content">
            <div class="modal-title">🚫 Login Error</div>
            <div class="modal-message" id="modal-error-message">
                Invalid email or password. Please check your credentials and try again.
            </div>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeErrorModal()">Try Again</button>
            </div>
        </div>
    </div>

    <script>
        // Show modal if there's an error parameter
        window.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const error = urlParams.get('error');

            if (error) {
                let message = '';
                switch(error) {
                    case 'invalid_credentials':
                        message = '❌ <strong>Login Failed!</strong><br><br>The email or password you entered is incorrect. Please check your credentials and try again.';
                        break;
                    case 'unauthorized':
                        message = '🚫 <strong>Access Denied!</strong><br><br>Unauthorized access attempt detected. Please log in properly.';
                        break;
                    default:
                        message = '⚠️ <strong>Error!</strong><br><br>An unexpected error occurred. Please try again.';
                }

                document.getElementById('modal-error-message').innerHTML = message;
                document.getElementById('error-modal').style.display = 'flex';

                // Remove error parameter from URL without refreshing
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }
        });

        function closeErrorModal() {
            document.getElementById('error-modal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('error-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeErrorModal();
            }
        });
    </script>

    <?php require "assets/footer.php"; ?>
</body>
</html>