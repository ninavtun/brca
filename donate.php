<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Fetch live donation info from database
$donation = $conn->query("SELECT * FROM donation_info LIMIT 1")->fetch_assoc();

$account_name = $donation['account_name'] ?? 'Bahrain Rescue Centre for Animals';
$account_number = $donation['account_number'] ?? 'National Bank of Bahrain (NBB) / BenefitPay';
$instructions = $donation['instructions'] ?? 'BRCA Animal Support Donation';
$qr_image = $donation['qr_image'] ?? 'qr_code.png';

$pageTitle = 'Donate';
$active = 'donate';
require 'includes/header.php';
?>

<div style="max-width: 1000px; margin: 40px auto; padding: 0 20px;">
    <!-- Header Section -->
    <div style="text-align: center; margin-bottom: 40px;">
        <h2 style="font-size: 2rem; color: var(--navy); margin-bottom: 8px;">Support the Centre</h2>
        <p style="color: var(--muted); font-size: 1.05rem;">Your donations help provide food, medicine, and care for rescued animals.</p>
    </div>

    <!-- Main Content Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px;">
        
        <!-- Left Column: Scan to Donate Card -->
        <div style="background: #fff; border: 1px solid var(--line, #d9dfe9); border-radius: 16px; padding: 30px; text-align: center; box-shadow: 0 4px 16px rgba(0,0,0,0.02); display: flex; flex-direction: column; align-items: center; justify-content: space-between;">
            <h3 style="font-size: 1.2rem; color: var(--navy); margin-bottom: 20px;">Scan to donate</h3>

            <!-- Extra Large Container -->
            <div style="position: relative; width: 360px; height: 360px; margin: 5px auto; display: flex; align-items: center; justify-content: center;">
                
                <!-- Extra Large Inner Rounded Box for the QR code -->
                <div style="width: 270px; height: 270px; background: #fff; border: 3px solid #2d3748; border-radius: 20px; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; z-index: 1;">
                    <img src="assets/img/<?= e($qr_image) ?>" alt="Donation QR Code" style="width: 95%; height: 95%; object-fit: contain;">
                </div>
                
                <!-- Outer Art Element scaled to match the extra large box -->
                <img src="assets/img/qrcode_border.jpg" alt="Border Artwork" style="position: absolute; top: -97px; left: -105px; width: 505px; height: 505px; object-fit: contain; pointer-events: none; z-index: 2;">
            </div>

            <p style="color: var(--muted); font-size: 0.9rem; margin: 20px 0;">Open your banking app and scan the code.</p>

            <div style="background: #e2e8f0; color: #334155; padding: 8px 20px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; display: inline-block;">
                Bank transfer only
            </div>
        </div>

        <!-- Right Column: Transfer Details -->
        <div style="background: #fff; border: 1px solid var(--line, #d9dfe9); border-radius: 16px; padding: 30px; box-shadow: 0 4px 16px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h3 style="font-size: 1.2rem; color: var(--navy); margin-bottom: 24px;">Transfer details</h3>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--muted); margin-bottom: 6px;">Account name</label>
                    <div style="background: #f8fafc; border: 1px solid var(--line); border-radius: 8px; padding: 12px; color: var(--navy); font-weight: 500;">
                        <?= e($account_name) ?>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--muted); margin-bottom: 6px;">Bank / e-wallet</label>
                    <div style="background: #f8fafc; border: 1px solid var(--line); border-radius: 8px; padding: 12px; color: var(--navy); font-weight: 500;">
                        <?= e($account_number) ?>
                    </div>
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: var(--muted); margin-bottom: 6px;">Reference</label>
                    <div style="background: #f8fafc; border: 1px solid var(--line); border-radius: 8px; padding: 12px; color: var(--muted); font-size: 0.9rem;">
                        <?= e($instructions) ?>
                    </div>
                </div>
            </div>

            <div>
                <h4 style="font-size: 1rem; color: var(--navy); margin-bottom: 12px;">Your gift helps with</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.9rem; color: var(--muted);">
                    <div>&bull; Food</div>
                    <div>&bull; Medicine</div>
                    <div>&bull; Vet care</div>
                    <div>&bull; Shelter supplies</div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require 'includes/footer.php'; ?>