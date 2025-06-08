<footer class="app-footer">
    <div class="footer-container">
        <div class="footer-content">
            <div class="footer-brand">
                <h3>📅 Rezervace Tříd</h3>
                <p>Profesionální systém pro rezervaci učeben</p>
            </div>

            <div class="footer-info">
                <div class="footer-section">
                    <h4>Kontakt</h4>
                    <p>📧 info@rezervace-trid.cz</p>
                    <p>📞 +420 123 456 789</p>
                </div>

                <div class="footer-section">
                    <h4>Rychlé odkazy</h4>
                    <ul class="footer-links">
                        <?php if ($isLoggedIn ?? false): ?>
                            <li><a href="classes.php">Třídy</a></li>
                            <li><a href="dashboard.php">Dashboard</a></li>
                        <?php else: ?>
                            <li><a href="signin.php">Přihlášení</a></li>
                            <li><a href="registration-form.php">Registrace</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> Rezervace Tříd | Vytvořil Mário Miklovič</p>
            <p class="footer-version">Verze 1.0 | Poslední aktualizace: <?= date('d.m.Y') ?></p>
        </div>
    </div>
</footer>