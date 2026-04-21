<?php
// premium.php
include 'includes/db.php';

// Must be a logged-in patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.php");
    exit;
}

$patient_id = (int) $_SESSION['user_id'];

// Check current premium status
$res     = mysqli_query($conn, "SELECT is_premium, is_verified FROM patients WHERE user_id = $patient_id");
$patient = mysqli_fetch_assoc($res);

$already_premium = $patient['is_premium'] ?? 0;
$is_verified     = $patient['is_verified'] ?? 0;

$success = false;
$error   = '';

// ── Handle form submission ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_premium) {

    // Server-side field validation (card data is NEVER stored)
    $cardholder = trim($_POST['cardholder'] ?? '');
    $card_number = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $expiry      = trim($_POST['expiry'] ?? '');
    $cvc         = trim($_POST['cvc'] ?? '');

    // Basic server-side checks
    if (empty($cardholder) || strlen($cardholder) < 3) {
        $error = "Please enter the cardholder's full name.";
    } elseif (!preg_match('/^\d{13,19}$/', $card_number)) {
        $error = "Please enter a valid card number.";
    } elseif (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $expiry, $m)) {
        $error = "Please enter a valid expiry date (MM/YY).";
    } else {
        // Check expiry is in the future
        $expMonth = (int)$m[1];
        $expYear  = (int)('20' . $m[2]);
        $nowMonth = (int)date('m');
        $nowYear  = (int)date('Y');
        if ($expYear < $nowYear || ($expYear === $nowYear && $expMonth < $nowMonth)) {
            $error = "Your card has expired.";
        } elseif (!preg_match('/^\d{3,4}$/', $cvc)) {
            $error = "Please enter a valid CVC code.";
        }
    }

    if (!$error) {
        // ⚠️  IMPORTANT SECURITY NOTE:
        // Card data is NEVER stored in the database.
        // In production, pass card details to a PCI-compliant processor
        // (e.g. Stripe, PayPal) via their SDK/API and only store the
        // returned token/subscription ID.
        // Here we simply flip the is_premium flag after "processing".

        $upd = mysqli_query($conn,
            "UPDATE patients SET is_premium = 1 WHERE user_id = $patient_id"
        );

        if ($upd) {
            $already_premium = 1;
            $success = true;
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}

include 'includes/header.php';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&display=swap');

:root {
    --gold:       #f5a623;
    --gold-light: #fff8e8;
    --gold-dark:  #c47f00;
    --dark:       #0f1520;
    --card-bg:    #ffffff;
    --radius:     18px;
}

.premium-page { font-family: 'Sora', sans-serif; }

/* ── Hero banner ── */
.premium-hero {
    background: linear-gradient(135deg, #0f1520 0%, #1c2a4a 55%, #243560 100%);
    border-radius: 24px;
    padding: 3.5rem 2.5rem;
    position: relative; overflow: hidden;
    color: #fff; margin-bottom: 2.5rem;
}
.premium-hero::before {
    content: '';
    position: absolute; top: -60px; right: -60px;
    width: 300px; height: 300px; border-radius: 50%;
    background: radial-gradient(circle, rgba(245,166,35,.25), transparent 70%);
}
.premium-hero::after {
    content: '';
    position: absolute; bottom: -80px; left: 20%;
    width: 220px; height: 220px; border-radius: 50%;
    background: radial-gradient(circle, rgba(13,110,253,.2), transparent 70%);
}
.crown { font-size: 3rem; line-height: 1; margin-bottom: .75rem; display: block; }
.hero-title {
    font-size: clamp(1.8rem, 4vw, 2.8rem);
    font-weight: 800; line-height: 1.15;
}
.gold-text { color: var(--gold); }

/* ── Feature pills ── */
.feature-pill {
    display: inline-flex; align-items: center; gap: .5rem;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 50px;
    padding: .4rem 1rem; font-size: .82rem; font-weight: 600;
    color: #fff; margin: .25rem;
    backdrop-filter: blur(6px);
}

/* ── Payment card ── */
.payment-wrap {
    background: var(--card-bg);
    border-radius: var(--radius);
    box-shadow: 0 8px 40px rgba(0,0,0,.1);
    overflow: hidden;
}
.payment-header {
    background: linear-gradient(135deg, #0f1520, #1c2a4a);
    padding: 1.5rem 2rem;
    color: #fff;
}
.payment-body { padding: 2rem; }

/* ── Card preview ── */
.card-preview {
    background: linear-gradient(135deg, #1c2a4a 0%, #2a4080 50%, #1a3a8f 100%);
    border-radius: 16px;
    padding: 1.5rem 1.75rem;
    color: #fff; position: relative; overflow: hidden;
    margin-bottom: 1.75rem;
    box-shadow: 0 8px 30px rgba(13,58,143,.4);
    min-height: 160px;
    transition: transform .3s;
}
.card-preview:hover { transform: scale(1.02); }
.card-preview::before {
    content: '';
    position: absolute; top: -40px; right: -40px;
    width: 160px; height: 160px; border-radius: 50%;
    background: rgba(255,255,255,.06);
}
.card-preview::after {
    content: '';
    position: absolute; bottom: -50px; left: 30%;
    width: 120px; height: 120px; border-radius: 50%;
    background: rgba(255,255,255,.04);
}
.card-chip {
    width: 40px; height: 30px; border-radius: 6px;
    background: linear-gradient(135deg, #f5c842, #d4a017);
    margin-bottom: 1rem;
    box-shadow: inset 0 1px 2px rgba(0,0,0,.2);
}
.card-number-display {
    font-size: 1.15rem; letter-spacing: .18em;
    font-weight: 600; margin-bottom: .75rem;
    font-family: 'Courier New', monospace;
}
.card-meta { display: flex; justify-content: space-between; font-size: .75rem; opacity: .75; }
.card-holder-display { font-size: .9rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }

/* ── Form fields ── */
.field-group { margin-bottom: 1.25rem; }
.field-label {
    font-size: .78rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .08em;
    color: #6c757d; margin-bottom: .4rem; display: block;
}
.secure-input {
    width: 100%; border: 2px solid #e9ecef; border-radius: 12px;
    padding: .75rem 1rem; font-size: .95rem;
    font-family: 'Sora', sans-serif;
    background: #fafbfc;
    transition: border-color .25s, box-shadow .25s, background .25s;
    outline: none;
}
.secure-input:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(245,166,35,.15);
    background: #fff;
}
.secure-input.is-valid   { border-color: #198754; background: #fff; }
.secure-input.is-invalid { border-color: #dc3545; background: #fff8f8; }

.input-icon-wrap { position: relative; }
.input-icon-wrap .secure-input { padding-left: 2.8rem; }
.input-icon {
    position: absolute; left: .9rem; top: 50%;
    transform: translateY(-50%);
    color: #adb5bd; font-size: 1rem; pointer-events: none;
}

.field-msg {
    font-size: .74rem; margin-top: 4px; display: block; min-height: 1rem;
}
.field-msg.ok  { color: #198754; }
.field-msg.err { color: #dc3545; }

/* ── CVC eye toggle ── */
.cvc-wrap { position: relative; }
.cvc-toggle {
    position: absolute; right: .9rem; top: 50%;
    transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: #adb5bd; font-size: 1rem; padding: 0;
}

/* ── Security badges ── */
.security-strip {
    background: #f8f9fa; border-top: 1px solid #e9ecef;
    padding: 1rem 2rem;
    display: flex; flex-wrap: wrap; gap: .75rem; align-items: center;
    justify-content: center;
}
.security-badge {
    display: inline-flex; align-items: center; gap: .4rem;
    font-size: .75rem; color: #6c757d; font-weight: 600;
}

/* ── Submit button ── */
.btn-pay {
    background: linear-gradient(135deg, var(--gold), #e0901a);
    border: none; border-radius: 12px;
    width: 100%; padding: 1rem;
    font-size: 1rem; font-weight: 700;
    color: #fff; letter-spacing: .3px;
    box-shadow: 0 6px 20px rgba(245,166,35,.4);
    transition: transform .2s, box-shadow .2s, opacity .2s;
    cursor: pointer; position: relative; overflow: hidden;
}
.btn-pay:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(245,166,35,.5);
}
.btn-pay:disabled { opacity: .6; cursor: not-allowed; transform: none; }
.btn-pay .spinner {
    display: none; width: 18px; height: 18px;
    border: 2px solid rgba(255,255,255,.4);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin .7s linear infinite;
    margin-right: 8px; vertical-align: middle;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Success state ── */
.success-box {
    text-align: center; padding: 3rem 2rem;
    animation: popIn .5s cubic-bezier(.175,.885,.32,1.275);
}
@keyframes popIn {
    0%   { opacity: 0; transform: scale(.85); }
    100% { opacity: 1; transform: scale(1); }
}
.success-icon {
    width: 80px; height: 80px; border-radius: 50%;
    background: linear-gradient(135deg, #198754, #20c997);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 2.2rem; color: #fff; margin-bottom: 1.25rem;
    box-shadow: 0 8px 25px rgba(25,135,84,.35);
}
.already-premium {
    background: linear-gradient(135deg, var(--gold-light), #fff);
    border: 2px solid var(--gold); border-radius: var(--radius);
    padding: 2.5rem; text-align: center;
}
</style>

<div class="premium-page">

<!-- ── Hero ── -->
<div class="premium-hero reveal mb-4">
    <div class="row align-items-center gy-3" style="position:relative;z-index:1;">
        <div class="col-lg-7">
            <span class="crown">👑</span>
            <h1 class="hero-title mb-3">
                Unlock <span class="gold-text">Premium</span><br>Priority Care
            </h1>
            <p class="mb-4" style="color:rgba(255,255,255,.75);max-width:460px;">
                Get matched with a verified doctor instantly. Premium members skip the queue and receive 24/7 dedicated support — for just $15/month.
            </p>
            <div>
                <span class="feature-pill">⚡ Instant Doctor Matching</span>
                <span class="feature-pill">🏆 Always First in Queue</span>
                <span class="feature-pill">🔔 24/7 Priority Support</span>
                <span class="feature-pill">✅ Faster Verification</span>
            </div>
        </div>
        <div class="col-lg-5 text-center">
            <div style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:18px;padding:1.5rem;backdrop-filter:blur(10px);">
                <div style="font-size:2.5rem;font-weight:800;color:var(--gold);">$15</div>
                <div style="color:rgba(255,255,255,.6);font-size:.9rem;">per month · cancel anytime</div>
                <hr style="border-color:rgba(255,255,255,.15);margin:1rem 0;">
                <?php
                $perks = ['Always #1 in doctor queues','Faster document verification','24/7 priority messaging','Dedicated support agent'];
                foreach ($perks as $p):
                ?>
                <div class="d-flex align-items-center gap-2 mb-2 text-start" style="color:rgba(255,255,255,.85);font-size:.88rem;">
                    <i class="bi bi-check-circle-fill" style="color:var(--gold);flex-shrink:0;"></i> <?php echo $p; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Already premium ── -->
<?php if ($already_premium && !$success): ?>
<div class="already-premium mb-4">
    <div style="font-size:3rem;">⭐</div>
    <h3 class="fw-bold mt-2" style="color:var(--gold-dark);">You're already a Premium member!</h3>
    <p class="text-muted mb-3">You already enjoy all priority benefits. Thank you for your support.</p>
    <a href="patient_dashboard.php" class="btn btn-warning fw-bold px-5 rounded-3">Go to Dashboard</a>
</div>

<!-- ── Success state ── -->
<?php elseif ($success): ?>
<div class="payment-wrap mb-4">
    <div class="success-box">
        <div class="success-icon">✓</div>
        <h3 class="fw-bold mb-2">You're now a Premium Member!</h3>
        <p class="text-muted mb-4">
            Your account has been upgraded. You now have priority access to all verified doctors on CareConnect.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="patient_dashboard.php" class="btn btn-success fw-bold px-5 rounded-3">
                <i class="bi bi-house-fill me-2"></i>Go to Dashboard
            </a>
            <a href="search.php" class="btn btn-outline-primary fw-bold px-5 rounded-3">
                <i class="bi bi-search me-2"></i>Find a Doctor
            </a>
        </div>
    </div>
</div>

<!-- ── Payment form ── -->
<?php else: ?>

<?php if (!$is_verified): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4" style="border-radius:12px;">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div>Your account must be <strong>verified by an admin</strong> before upgrading to Premium.</div>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3" style="border-radius:12px;">
    <i class="bi bi-x-circle-fill fs-5"></i> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <div class="payment-wrap mb-4">

            <!-- Header -->
            <div class="payment-header">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fw-bold fs-5">Secure Payment</div>
                        <div style="color:rgba(255,255,255,.6);font-size:.83rem;">
                            256-bit SSL encrypted · PCI compliant
                        </div>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <i class="bi bi-lock-fill" style="color:var(--gold);"></i>
                        <span style="font-size:.8rem;color:rgba(255,255,255,.7);">Protected</span>
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div class="payment-body">

                <!-- Live card preview -->
                <div class="card-preview" id="cardPreview">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div class="card-chip"></div>
                        <div id="cardTypeIcon" style="font-size:1.5rem; opacity:.9;"></div>
                    </div>
                    <div class="card-number-display" id="previewNumber">•••• •••• •••• ••••</div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div>
                            <div style="font-size:.65rem;opacity:.6;letter-spacing:.1em;">CARD HOLDER</div>
                            <div class="card-holder-display" id="previewName">YOUR NAME</div>
                        </div>
                        <div class="text-end">
                            <div style="font-size:.65rem;opacity:.6;letter-spacing:.1em;">EXPIRES</div>
                            <div style="font-size:.85rem;font-weight:600;" id="previewExpiry">MM/YY</div>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <form id="paymentForm" method="POST" action="premium.php" novalidate
                      autocomplete="off">

                    <!-- Cardholder name -->
                    <div class="field-group">
                        <label class="field-label">Cardholder Name</label>
                        <div class="input-icon-wrap">
                            <i class="bi bi-person input-icon"></i>
                            <input type="text" class="secure-input" id="cardholder" name="cardholder"
                                   placeholder="As it appears on the card"
                                   autocomplete="cc-name" spellcheck="false">
                        </div>
                        <span class="field-msg" id="nameMsg"></span>
                    </div>

                    <!-- Card number -->
                    <div class="field-group">
                        <label class="field-label">Card Number</label>
                        <div class="input-icon-wrap">
                            <i class="bi bi-credit-card input-icon"></i>
                            <input type="text" class="secure-input" id="card_number" name="card_number"
                                   placeholder="1234 5678 9012 3456"
                                   maxlength="19" inputmode="numeric"
                                   autocomplete="cc-number" spellcheck="false">
                        </div>
                        <span class="field-msg" id="cardMsg"></span>
                    </div>

                    <!-- Expiry + CVC -->
                    <div class="row g-3 mb-0">
                        <div class="col-6">
                            <div class="field-group mb-0">
                                <label class="field-label">Expiry Date</label>
                                <div class="input-icon-wrap">
                                    <i class="bi bi-calendar3 input-icon"></i>
                                    <input type="text" class="secure-input" id="expiry" name="expiry"
                                           placeholder="MM/YY" maxlength="5" inputmode="numeric"
                                           autocomplete="cc-exp">
                                </div>
                                <span class="field-msg" id="expiryMsg"></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="field-group mb-0">
                                <label class="field-label" style="display:flex;align-items:center;gap:.4rem;">
                                    CVC
                                    <span tabindex="0" data-bs-toggle="tooltip"
                                          title="3-digit code on the back of your card (4 digits for Amex)"
                                          style="cursor:help;color:#adb5bd;font-size:.85rem;">
                                        <i class="bi bi-question-circle"></i>
                                    </span>
                                </label>
                                <div class="cvc-wrap input-icon-wrap">
                                    <i class="bi bi-shield-lock input-icon"></i>
                                    <input type="password" class="secure-input" id="cvc" name="cvc"
                                           placeholder="•••" maxlength="4" inputmode="numeric"
                                           autocomplete="cc-csc">
                                    <button type="button" class="cvc-toggle" id="cvcToggle"
                                            aria-label="Show CVC">
                                        <i class="bi bi-eye" id="cvcEyeIcon"></i>
                                    </button>
                                </div>
                                <span class="field-msg" id="cvcMsg"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Amount display -->
                    <div class="d-flex justify-content-between align-items-center mt-4 mb-3 p-3"
                         style="background:#f8f9fa;border-radius:12px;border:1.5px solid #e9ecef;">
                        <span class="fw-bold" style="font-size:.9rem;">Premium Plan · Monthly</span>
                        <span class="fw-bold fs-5" style="color:var(--gold-dark);">$15.00</span>
                    </div>

                    <button type="submit" class="btn-pay" id="btnPay"
                            <?php echo !$is_verified ? 'disabled' : ''; ?>>
                        <span class="spinner" id="paySpinner"></span>
                        <i class="bi bi-lock-fill me-2"></i>Pay $15.00 Securely
                    </button>

                    <?php if (!$is_verified): ?>
                    <p class="text-center text-muted small mt-2">
                        Payment is disabled until your account is verified by an admin.
                    </p>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Security strip -->
            <div class="security-strip">
                <div class="security-badge"><i class="bi bi-lock-fill text-success"></i> SSL Encrypted</div>
                <div class="security-badge"><i class="bi bi-shield-check text-primary"></i> PCI DSS Compliant</div>
                <div class="security-badge"><i class="bi bi-eye-slash-fill text-secondary"></i> Card data never stored</div>
                <div class="security-badge"><i class="bi bi-arrow-counterclockwise text-warning"></i> Cancel anytime</div>
            </div>

        </div><!-- /payment-wrap -->

        <p class="text-center text-muted small">
            <i class="bi bi-info-circle me-1"></i>
            Your card details are never saved on our servers. Payments are processed via a PCI-compliant gateway.
        </p>

    </div>
</div>

<?php endif; ?>
</div><!-- /premium-page -->

<script>
// ── Tooltips ─────────────────────────────────────────────
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
});

// ── Helpers ──────────────────────────────────────────────
function setMsg(id, text, ok) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = text;
    el.className   = 'field-msg ' + (ok ? 'ok' : 'err');
}
function markField(id, ok) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.toggle('is-valid',   ok);
    el.classList.toggle('is-invalid', !ok && el.value.length > 0);
}

// ── Luhn algorithm (card number validation) ───────────────
function luhn(n) {
    let sum = 0, alt = false;
    for (let i = n.length - 1; i >= 0; i--) {
        let d = +n[i];
        if (alt) { d *= 2; if (d > 9) d -= 9; }
        sum += d; alt = !alt;
    }
    return sum % 10 === 0;
}

// ── Detect card type ──────────────────────────────────────
function cardType(n) {
    if (/^4/.test(n))                        return { name: 'Visa',       icon: '💳' };
    if (/^5[1-5]|^2[2-7]/.test(n))          return { name: 'Mastercard', icon: '💳' };
    if (/^3[47]/.test(n))                    return { name: 'Amex',       icon: '💳' };
    if (/^6(?:011|5)/.test(n))               return { name: 'Discover',   icon: '💳' };
    return { name: '', icon: '' };
}

// ── Live card preview ─────────────────────────────────────
const previewNumber = document.getElementById('previewNumber');
const previewName   = document.getElementById('previewName');
const previewExpiry = document.getElementById('previewExpiry');
const cardTypeIcon  = document.getElementById('cardTypeIcon');

// Cardholder
const cardholderEl = document.getElementById('cardholder');
if (cardholderEl) {
    cardholderEl.addEventListener('input', function () {
        const val = this.value.trim().toUpperCase();
        previewName.textContent = val || 'YOUR NAME';

        const ok = val.length >= 3 && /^[A-ZÀ-Ÿ\s.\-']+$/.test(val);
        markField('cardholder', ok);
        if (!val)           setMsg('nameMsg', '', true);
        else if (val.length < 3) setMsg('nameMsg', '⚠ At least 3 characters required', false);
        else if (!ok)       setMsg('nameMsg', '⚠ Letters only please', false);
        else                setMsg('nameMsg', '✓ Good', true);
    });
}

// Card number — format as groups of 4
const cardNumEl = document.getElementById('card_number');
if (cardNumEl) {
    cardNumEl.addEventListener('input', function () {
        let raw     = this.value.replace(/\D/g, '').slice(0, 16);
        let groups  = raw.match(/.{1,4}/g) || [];
        this.value  = groups.join(' ');

        // Preview: show digits and mask middle groups
        let display = raw.padEnd(16, '•');
        let parts   = display.match(/.{1,4}/g) || [];
        // Mask groups 2 & 3 when more than 4 digits entered
        if (raw.length > 4) {
            parts = parts.map((g, i) =>
                (i === 1 || i === 2) && raw.length > 4 + (i - 1) * 4
                    ? '••••' : g
            );
        }
        previewNumber.textContent = parts.join(' ');

        const type = cardType(raw);
        cardTypeIcon.textContent = raw.length >= 1 ? type.icon : '';

        const ok = raw.length >= 13 && luhn(raw);
        markField('card_number', ok);
        if (!raw)             setMsg('cardMsg', '', true);
        else if (raw.length < 13) setMsg('cardMsg', '⚠ Card number is too short', false);
        else if (!luhn(raw))  setMsg('cardMsg', '⚠ Invalid card number', false);
        else                  setMsg('cardMsg', '✓ Card number valid', true);
    });
}

// Expiry — auto-insert slash
const expiryEl = document.getElementById('expiry');
if (expiryEl) {
    expiryEl.addEventListener('input', function (e) {
        let v = this.value.replace(/\D/g, '');
        if (v.length >= 2) v = v.slice(0,2) + '/' + v.slice(2,4);
        this.value = v;
        previewExpiry.textContent = this.value || 'MM/YY';

        const m = this.value.match(/^(0[1-9]|1[0-2])\/(\d{2})$/);
        let ok = false;
        if (m) {
            const expYear  = 2000 + +m[2];
            const expMonth = +m[1];
            const now = new Date();
            ok = expYear > now.getFullYear()
              || (expYear === now.getFullYear() && expMonth >= now.getMonth() + 1);
        }
        markField('expiry', ok);
        if (!this.value)    setMsg('expiryMsg', '', true);
        else if (!m)        setMsg('expiryMsg', '⚠ Use MM/YY format', false);
        else if (!ok)       setMsg('expiryMsg', '⚠ This card has expired', false);
        else                setMsg('expiryMsg', '✓ Valid', true);
    });
}

// CVC
const cvcEl = document.getElementById('cvc');
if (cvcEl) {
    cvcEl.addEventListener('input', function () {
        const val = this.value.replace(/\D/g, '').slice(0, 4);
        this.value = val;
        const ok  = val.length >= 3;
        markField('cvc', ok);
        if (!val)      setMsg('cvcMsg', '', true);
        else if (!ok)  setMsg('cvcMsg', '⚠ CVC must be 3-4 digits', false);
        else           setMsg('cvcMsg', '✓ Good', true);
    });
}

// CVC show/hide toggle
const cvcToggle = document.getElementById('cvcToggle');
if (cvcToggle && cvcEl) {
    cvcToggle.addEventListener('click', () => {
        const isHidden = cvcEl.type === 'password';
        cvcEl.type = isHidden ? 'text' : 'password';
        document.getElementById('cvcEyeIcon').className =
            isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
}

// ── Form submit validation + spinner ─────────────────────
const payForm = document.getElementById('paymentForm');
if (payForm) {
    payForm.addEventListener('submit', function (e) {
        const name = (document.getElementById('cardholder')?.value.trim() || '');
        const raw  = (document.getElementById('card_number')?.value.replace(/\s/g,'') || '');
        const exp  = (document.getElementById('expiry')?.value || '');
        const cvc  = (document.getElementById('cvc')?.value || '');

        let ok = true;

        if (name.length < 3) {
            setMsg('nameMsg', '⚠ Please enter the cardholder name', false);
            markField('cardholder', false); ok = false;
        }
        if (raw.length < 13 || !luhn(raw)) {
            setMsg('cardMsg', '⚠ Please enter a valid card number', false);
            markField('card_number', false); ok = false;
        }
        if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(exp)) {
            setMsg('expiryMsg', '⚠ Please enter a valid expiry date', false);
            markField('expiry', false); ok = false;
        }
        if (cvc.length < 3) {
            setMsg('cvcMsg', '⚠ Please enter a valid CVC', false);
            markField('cvc', false); ok = false;
        }

        if (!ok) { e.preventDefault(); return; }

        // Show spinner
        const btn = document.getElementById('btnPay');
        const sp  = document.getElementById('paySpinner');
        if (btn && sp) {
            btn.disabled       = true;
            sp.style.display   = 'inline-block';
            btn.querySelector('i').style.display = 'none';
            btn.childNodes[btn.childNodes.length - 1].textContent = ' Processing…';
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
