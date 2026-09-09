    </main> <!-- .main-content is opened by each page after navbar.php -->
</div> <!-- .app-shell -->

<footer class="app-footer">
    <p>Student Performance Predictor &amp; Analytics System &middot;
       Educational demo project &middot; Weighted-Factor Model <?= MODEL_VERSION ?></p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="<?= isset($basePath) ? $basePath : '' ?>assets/js/script.js"></script>
</body>
</html>
