<?php
include 'includes/db.php';

$success_msg = '';
$error_msg   = '';
$default_role = isset($_GET['role']) ? $_GET['role'] : 'patient';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email     = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role      = mysqli_real_escape_string($conn, $_POST['role']);

    $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        $error_msg = "An account with this email already exists.";
    } else {
        $sql = "INSERT INTO users (role, full_name, email, password_hash)
                VALUES ('$role', '$full_name', '$email', '$password')";

        if (mysqli_query($conn, $sql)) {
            $user_id = mysqli_insert_id($conn);

            if ($role === 'doctor') {
                $specialty = mysqli_real_escape_string($conn, trim($_POST['specialty']));
                $license   = mysqli_real_escape_string($conn, trim($_POST['license']));
                mysqli_query($conn, "INSERT INTO doctors (user_id, specialty, license_number)
                                     VALUES ('$user_id', '$specialty', '$license')");
            } elseif ($role === 'patient') {
                $proof_path = 'pending_upload.pdf';
                if (isset($_FILES['financial_proof']) && $_FILES['financial_proof']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'uploads/proofs/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    $ext     = strtolower(pathinfo($_FILES['financial_proof']['name'], PATHINFO_EXTENSION));
                    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
                    if (in_array($ext, $allowed)) {
                        $filename   = 'proof_' . $user_id . '_' . time() . '.' . $ext;
                        $proof_path = $upload_dir . $filename;
                        move_uploaded_file($_FILES['financial_proof']['tmp_name'], $proof_path);
                    }
                }
                $safe_path = mysqli_real_escape_string($conn, $proof_path);
                mysqli_query($conn, "INSERT INTO patients (user_id, financial_proof_path)
                                     VALUES ('$user_id', '$safe_path')");
            }

            $success_msg = "Account created successfully! <a href='login.php' class='alert-link'>Click here to log in</a>.";
        } else {
            $error_msg = "Database Error: " . mysqli_error($conn);
        }
    }
}

include 'includes/header.php';
?>

<style>
/* ── Field wrappers ── */
.field-wrapper { position: relative; margin-bottom: 1.2rem; }
.field-icon {
    position: absolute; left: 14px; top: 50%;
    transform: translateY(-50%);
    color: #adb5bd; font-size: 1rem; pointer-events: none;
}
.field-wrapper input,
.field-wrapper select { padding-left: 2.6rem; }

.form-control, .form-select {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    transition: border-color .25s, box-shadow .25s;
}
.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13,110,253,.12);
}
.form-control.is-valid   { border-color: #198754 !important; background-image: none; }
.form-control.is-invalid { border-color: #dc3545 !important; background-image: none; }

/* ── Validation messages ── */
.validation-msg {
    font-size: .78rem; margin-top: 4px;
    min-height: 1rem; display: block; padding-left: 2px;
}
.validation-msg.ok  { color: #198754; }
.validation-msg.err { color: #dc3545; }

/* ── Password strength bar ── */
.strength-track { height: 5px; background: #e9ecef; border-radius: 4px; margin-top: 6px; }
.strength-bar   { height: 5px; border-radius: 4px; width: 0; transition: width .4s, background .4s; }

/* ── Role cards ── */
.role-card {
    border: 2px solid #e9ecef; border-radius: 14px;
    padding: 1rem 1.25rem; cursor: pointer; text-align: center;
    transition: border-color .2s, background .2s, transform .15s;
    user-select: none; flex: 1;
}
.role-card:hover { border-color: #0d6efd; transform: translateY(-2px); }
.role-card.selected { border-color: #0d6efd; background: #f0f5ff; }
.role-card input[type=radio] { display: none; }

/* ── Section boxes ── */
.section-box {
    background: #f8f9fa; border: 1.5px solid #dee2e6;
    border-radius: 12px; padding: 1.25rem;
}

/* ── File drop zone ── */
.file-drop {
    border: 2px dashed #ced4da; border-radius: 12px;
    padding: 1.5rem; text-align: center;
    cursor: pointer; transition: border-color .2s, background .2s;
    background: #fff;
}
.file-drop:hover, .file-drop.dragover { border-color: #0d6efd; background: #f0f5ff; }
.file-drop input[type=file] { display: none; }

/* ── Submit button ── */
.btn-submit {
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    border: none; border-radius: 12px;
    padding: .85rem; font-size: 1.05rem; font-weight: 600;
    transition: opacity .2s, transform .15s, box-shadow .2s;
    box-shadow: 0 4px 15px rgba(13,110,253,.35);
}
.btn-submit:hover:not(:disabled) { opacity: .9; transform: translateY(-1px); }

/* ── Step indicator ── */
.progress-step {
    width: 32px; height: 32px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .85rem;
    border: 2px solid #dee2e6; background: #fff; color: #adb5bd;
    transition: all .3s;
}
.progress-step.active { border-color: #0d6efd; color: #0d6efd; }
.progress-step.done   { border-color: #198754; background: #198754; color: #fff; }
.progress-line { flex: 1; height: 2px; background: #dee2e6; transition: background .3s; }
.progress-line.done { background: #198754; }
</style>

<div class="row justify-content-center mt-4 mb-5">
    <div class="col-md-7 col-lg-6">

        <!-- Step indicator -->
        <div class="d-flex align-items-center mb-4 px-2">
            <div class="progress-step active" id="step1ind">1</div>
            <div class="progress-line" id="line1"></div>
            <div class="progress-step" id="step2ind">2</div>
            <div class="progress-line" id="line2"></div>
            <div class="progress-step" id="step3ind">3</div>
        </div>

        <div class="card border-0 shadow p-4" style="border-radius:18px;">
            <div class="card-body p-1">
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-dark mb-1">Join CareConnect</h2>
                    <p class="text-muted">Create an account to get or give help.</p>
                </div>

                <?php if ($success_msg): ?>
                    <div class="alert alert-success"><?php echo $success_msg; ?></div>
                <?php endif; ?>
                <?php if ($error_msg): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>

                <form id="registerForm" action="register.php" method="POST" enctype="multipart/form-data" novalidate>

                    <!-- ── STEP 1: Role ─────────────────────────────── -->
                    <div id="formStep1">
                        <p class="fw-bold mb-3">Step 1 — I am a...</p>
                        <div class="d-flex gap-3 mb-4">
                            <label class="role-card selected" id="cardPatient">
                                <input type="radio" name="role" value="patient" checked>
                                <div class="fs-2 mb-1">🤒</div>
                                <div class="fw-bold">Patient</div>
                                <small class="text-muted">I need medical help</small>
                            </label>
                            <label class="role-card" id="cardDoctor">
                                <input type="radio" name="role" value="doctor">
                                <div class="fs-2 mb-1">👨‍⚕️</div>
                                <div class="fw-bold">Doctor</div>
                                <small class="text-muted">I want to volunteer</small>
                            </label>
                        </div>
                        <button type="button" class="btn btn-primary w-100 rounded-3 py-2 fw-bold" onclick="goStep(2)">
                            Continue <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>

                    <!-- ── STEP 2: Account info ─────────────────────── -->
                    <div id="formStep2" style="display:none;">
                        <p class="fw-bold mb-3">Step 2 — Account Information</p>

                        <div class="field-wrapper">
                            <i class="bi bi-person field-icon"></i>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                   placeholder="Full Name" autocomplete="name">
                            <span class="validation-msg" id="nameMsg"></span>
                        </div>

                        <div class="field-wrapper">
                            <i class="bi bi-envelope field-icon"></i>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="Email address" autocomplete="email">
                            <span class="validation-msg" id="emailMsg"></span>
                        </div>

                        <div class="field-wrapper">
                            <i class="bi bi-lock field-icon"></i>
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Password (min. 6 characters)" autocomplete="new-password">
                            <div class="strength-track">
                                <div class="strength-bar" id="strengthBar"></div>
                            </div>
                            <span class="validation-msg" id="strengthMsg"></span>
                        </div>

                        <div class="field-wrapper">
                            <i class="bi bi-shield-lock field-icon"></i>
                            <input type="password" class="form-control" id="confirm_password"
                                   placeholder="Confirm Password" autocomplete="new-password">
                            <span class="validation-msg" id="confirmMsg"></span>
                        </div>

                        <div class="d-flex gap-2 mt-2">
                            <button type="button" class="btn btn-outline-secondary w-50 rounded-3 fw-bold" onclick="goStep(1)">
                                <i class="bi bi-arrow-left me-1"></i> Back
                            </button>
                            <button type="button" class="btn btn-primary w-50 rounded-3 fw-bold" onclick="validateAndGoStep3()">
                                Continue <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- ── STEP 3: Role-specific ────────────────────── -->
                    <div id="formStep3" style="display:none;">
                        <p class="fw-bold mb-3">Step 3 — <span id="step3Label">Verification</span></p>

                        <!-- Patient: file upload -->
                        <div id="patient_fields" class="section-box mb-3">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="bi bi-file-earmark-check me-1"></i> Proof of Financial Need
                            </h6>
                            <div class="file-drop" id="fileDrop"
                                 onclick="document.getElementById('financial_proof').click()">
                                <i class="bi bi-cloud-upload fs-2 text-muted d-block mb-2"></i>
                                <span id="fileDropText" class="text-muted">Click or drag & drop your document here</span>
                                <small class="text-muted d-block mt-1">PDF, JPG, PNG — max 5 MB</small>
                                <input type="file" id="financial_proof" name="financial_proof"
                                       accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <span class="validation-msg" id="fileMsg"></span>
                        </div>

                        <!-- Doctor: credentials -->
                        <div id="doctor_fields" class="section-box mb-3" style="display:none;">
                            <h6 class="fw-bold text-success mb-3">
                                <i class="bi bi-hospital me-1"></i> Medical Credentials
                            </h6>
                            <div class="field-wrapper">
                                <i class="bi bi-heart-pulse field-icon"></i>
                                <input type="text" class="form-control" id="specialty" name="specialty"
                                       placeholder="Medical Specialty (e.g., Cardiology)">
                                <span class="validation-msg" id="specialtyMsg"></span>
                            </div>
                            <div class="field-wrapper mb-0">
                                <i class="bi bi-card-text field-icon"></i>
                                <input type="text" class="form-control" id="license" name="license"
                                       placeholder="Medical License Number">
                                <span class="validation-msg" id="licenseMsg"></span>
                            </div>
                        </div>

                        <!-- Terms -->
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="terms">
                            <label class="form-check-label text-muted small" for="terms">
                                I agree to the <a href="#" class="text-primary">Terms of Service</a>
                                and <a href="#" class="text-primary">Privacy Policy</a>
                            </label>
                            <span class="validation-msg" id="termsMsg"></span>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary w-50 rounded-3 fw-bold" onclick="goStep(2)">
                                <i class="bi bi-arrow-left me-1"></i> Back
                            </button>
                            <button type="submit" class="btn-submit btn w-50 text-white fw-bold"
                                    onclick="return validateStep3()">
                                Create Account
                            </button>
                        </div>
                    </div>

                </form>

                <div class="text-center mt-4">
                    <p class="text-muted mb-0">Already have an account?
                        <a href="login.php" class="text-primary fw-bold text-decoration-none">Log in here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ─── STATE ──────────────────────────────────────────
let currentRole = '<?php echo $default_role; ?>';
if (currentRole === 'doctor') {
    document.getElementById('cardDoctor').classList.add('selected');
    document.getElementById('cardPatient').classList.remove('selected');
    document.querySelector('#cardDoctor input').checked = true;
}

// ─── STEP NAVIGATION ────────────────────────────────
function goStep(n) {
    document.getElementById('formStep1').style.display = n === 1 ? '' : 'none';
    document.getElementById('formStep2').style.display = n === 2 ? '' : 'none';
    document.getElementById('formStep3').style.display = n === 3 ? '' : 'none';

    [1, 2, 3].forEach(i => {
        const el = document.getElementById('step' + i + 'ind');
        el.classList.remove('active', 'done');
        if (i < n)      el.classList.add('done');
        else if (i === n) el.classList.add('active');
    });
    [1, 2].forEach(i => {
        document.getElementById('line' + i).classList.toggle('done', i < n);
    });

    if (n === 3) {
        document.getElementById('step3Label').textContent =
            currentRole === 'doctor' ? 'Medical Credentials' : 'Verification Documents';
        document.getElementById('doctor_fields').style.display  = currentRole === 'doctor'  ? '' : 'none';
        document.getElementById('patient_fields').style.display = currentRole === 'patient' ? '' : 'none';
    }
}

// ─── ROLE CARDS ─────────────────────────────────────
document.getElementById('cardPatient').addEventListener('click', () => {
    currentRole = 'patient';
    document.getElementById('cardPatient').classList.add('selected');
    document.getElementById('cardDoctor').classList.remove('selected');
});
document.getElementById('cardDoctor').addEventListener('click', () => {
    currentRole = 'doctor';
    document.getElementById('cardDoctor').classList.add('selected');
    document.getElementById('cardPatient').classList.remove('selected');
});

// ─── HELPERS ────────────────────────────────────────
function setMsg(id, text, ok) {
    const el = document.getElementById(id);
    el.textContent = text;
    el.className   = 'validation-msg ' + (ok ? 'ok' : 'err');
}
function markField(id, ok) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.toggle('is-valid',   ok);
    el.classList.toggle('is-invalid', !ok);
}

// ─── REAL-TIME: FULL NAME ───────────────────────────
document.getElementById('full_name').addEventListener('input', function () {
    const val     = this.value.trim();
    const letters = /^[a-zA-ZÀ-ÿ\s.\-']+$/.test(val);
    const ok      = val.length >= 3 && letters;
    markField('full_name', ok);
    if (!val)              setMsg('nameMsg', '', true);
    else if (val.length < 3) setMsg('nameMsg', '⚠ At least 3 characters required', false);
    else if (!letters)     setMsg('nameMsg', '⚠ Letters, spaces, dots and hyphens only', false);
    else                   setMsg('nameMsg', '✓ Looks good!', true);
});

// ─── REAL-TIME: EMAIL ───────────────────────────────
document.getElementById('email').addEventListener('input', function () {
    const val = this.value.trim();
    const ok  = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val);
    markField('email', ok);
    if (!val)     setMsg('emailMsg', '', true);
    else if (!ok) setMsg('emailMsg', '⚠ Please enter a valid email address', false);
    else          setMsg('emailMsg', '✓ Valid email', true);
});

// ─── REAL-TIME: PASSWORD STRENGTH ───────────────────
document.getElementById('password').addEventListener('input', function () {
    const v = this.value;
    const checks = [
        v.length >= 6,
        v.length >= 10,
        /[A-Z]/.test(v),
        /[0-9]/.test(v),
        /[^A-Za-z0-9]/.test(v)
    ];
    const score  = checks.filter(Boolean).length;
    const colors = ['#dc3545', '#fd7e14', '#ffc107', '#20c997', '#198754'];
    const labels = ['Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong'];

    const bar = document.getElementById('strengthBar');
    bar.style.width      = (score * 20) + '%';
    bar.style.background = colors[score - 1] || '#e9ecef';

    const ok = score >= 3;
    markField('password', v.length > 0 && ok);
    setMsg('strengthMsg',
        v.length === 0 ? '' : (labels[score - 1] || 'Very Weak') + ' password',
        ok);

    if (document.getElementById('confirm_password').value) checkConfirm();
});

// ─── REAL-TIME: CONFIRM PASSWORD ────────────────────
document.getElementById('confirm_password').addEventListener('input', checkConfirm);
function checkConfirm() {
    const pw  = document.getElementById('password').value;
    const cpw = document.getElementById('confirm_password').value;
    const ok  = cpw.length > 0 && pw === cpw;
    markField('confirm_password', ok);
    if (!cpw)    setMsg('confirmMsg', '', true);
    else if (!ok) setMsg('confirmMsg', '⚠ Passwords do not match', false);
    else         setMsg('confirmMsg', '✓ Passwords match', true);
}

// ─── REAL-TIME: SPECIALTY ───────────────────────────
document.getElementById('specialty').addEventListener('input', function () {
    const val = this.value.trim(), ok = val.length >= 3;
    markField('specialty', ok);
    setMsg('specialtyMsg', val && !ok ? '⚠ Please enter a valid specialty' : (ok ? '✓ Good' : ''), ok);
});

// ─── REAL-TIME: LICENSE ─────────────────────────────
document.getElementById('license').addEventListener('input', function () {
    const val = this.value.trim(), ok = val.length >= 4;
    markField('license', ok);
    setMsg('licenseMsg', val && !ok ? '⚠ License number seems too short' : (ok ? '✓ Good' : ''), ok);
});

// ─── FILE DROP ZONE ─────────────────────────────────
const fileDrop  = document.getElementById('fileDrop');
const fileInput = document.getElementById('financial_proof');

fileDrop.addEventListener('dragover',  e => { e.preventDefault(); fileDrop.classList.add('dragover'); });
fileDrop.addEventListener('dragleave', ()  => fileDrop.classList.remove('dragover'));
fileDrop.addEventListener('drop', e => {
    e.preventDefault(); fileDrop.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        handleFile(e.dataTransfer.files[0]);
    }
});
fileInput.addEventListener('change', () => { if (fileInput.files.length) handleFile(fileInput.files[0]); });

function handleFile(file) {
    const ext     = file.name.split('.').pop().toLowerCase();
    const allowed = ['pdf', 'jpg', 'jpeg', 'png'];
    const ok      = allowed.includes(ext) && file.size <= 5 * 1024 * 1024;
    document.getElementById('fileDropText').innerHTML = ok
        ? '<span style="color:#198754">✓ ' + file.name + '</span>'
        : '<span style="color:#dc3545">⚠ Invalid file</span>';
    setMsg('fileMsg', ok ? '' : '⚠ Please upload a valid file (PDF, JPG, PNG, max 5 MB)', ok);
}

// ─── STEP 2 VALIDATE + PROCEED ──────────────────────
function validateAndGoStep3() {
    const name = document.getElementById('full_name').value.trim();
    const mail = document.getElementById('email').value.trim();
    const pw   = document.getElementById('password').value;
    const cpw  = document.getElementById('confirm_password').value;
    let ok = true;

    if (name.length < 3 || !/^[a-zA-ZÀ-ÿ\s.\-']+$/.test(name)) {
        setMsg('nameMsg', '⚠ Please enter your full name (letters only, min 3 chars)', false);
        markField('full_name', false); ok = false;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(mail)) {
        setMsg('emailMsg', '⚠ Please enter a valid email address', false);
        markField('email', false); ok = false;
    }
    const score = [pw.length >= 6, /[A-Z]/.test(pw), /[0-9]/.test(pw)].filter(Boolean).length;
    if (pw.length < 6 || score < 2) {
        setMsg('strengthMsg', '⚠ Password too weak — use 6+ characters with letters and numbers', false);
        markField('password', false); ok = false;
    }
    if (!cpw || pw !== cpw) {
        setMsg('confirmMsg', '⚠ Passwords do not match', false);
        markField('confirm_password', false); ok = false;
    }
    if (ok) goStep(3);
}

// ─── STEP 3 VALIDATE ON SUBMIT ──────────────────────
function validateStep3() {
    let ok = true;

    if (currentRole === 'doctor') {
        const spec = document.getElementById('specialty').value.trim();
        const lic  = document.getElementById('license').value.trim();
        if (spec.length < 3) {
            setMsg('specialtyMsg', '⚠ Please enter your medical specialty', false);
            markField('specialty', false); ok = false;
        }
        if (lic.length < 4) {
            setMsg('licenseMsg', '⚠ Please enter a valid license number', false);
            markField('license', false); ok = false;
        }
    }

    if (!document.getElementById('terms').checked) {
        setMsg('termsMsg', '⚠ You must agree to the Terms of Service to continue', false);
        ok = false;
    } else {
        setMsg('termsMsg', '', true);
    }

    return ok;
}
</script>

<?php include 'includes/footer.php'; ?>