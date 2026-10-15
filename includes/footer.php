</main>
    <footer class="footer">
        <p>Bahrain Rescue Centre for Animals &middot; Hamala, Bahrain</p>
    </footer>

    <?php 
    // Dynamically check if we are in a subfolder (like admin/) to prefix asset paths correctly
    $is_subfolder = file_exists('../config/db.php') || strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
    $asset_prefix = $is_subfolder ? '../' : '';
    ?>

    <!-- Global UI Sound Effects -->
    <audio id="global-hover-sound" src="<?= $asset_prefix ?>assets/audio/pop.mp3" preload="auto"></audio>
    <audio id="global-click-sound" src="<?= $asset_prefix ?>assets/audio/click.mp3" preload="auto"></audio>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.animal-profile-card, .btn-details, .btn-adopt, .btn-foster, a').forEach(el => {
                // Hover sound
                el.addEventListener('mouseenter', () => {
                    const hoverAudio = document.getElementById('global-hover-sound');
                    if (hoverAudio) {
                        hoverAudio.currentTime = 0;
                        hoverAudio.play().catch(e => {});
                    }
                });

                // Click sound with navigation delay
                el.addEventListener('click', (e) => {
                    const clickAudio = document.getElementById('global-click-sound');
                    if (clickAudio) {
                        clickAudio.currentTime = 0;
                        clickAudio.play().catch(e => {});
                    }

                    const href = el.getAttribute('href');
                    const target = el.getAttribute('target');

                    // If it's a local page link, delay the jump slightly so the sound plays
                    if (href && href !== '#' && !href.startsWith('javascript') && (!target || target === '_self')) {
                        if (!e.ctrlKey && !e.metaKey && !e.shiftKey && !e.altKey) {
                            e.preventDefault(); // Pause instant navigation
                            setTimeout(() => {
                                window.location.href = href;
                            }, 150); // 150ms delay to let the click sound finish
                        }
                    }
                });
            });
        });
    </script>
</body>

</html>