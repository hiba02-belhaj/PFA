<?php
include 'includes/db.php';

// Live stats from DB
$r = mysqli_query($conn, "SELECT COUNT(*) AS t FROM users"); $total_users = mysqli_fetch_assoc($r)['t'];
$r = mysqli_query($conn, "SELECT COUNT(*) AS t FROM doctors WHERE is_verified=1"); $doctors_count = mysqli_fetch_assoc($r)['t'];
$r = mysqli_query($conn, "SELECT COUNT(*) AS t FROM patients WHERE is_verified=1"); $patients_count = mysqli_fetch_assoc($r)['t'];
$r = mysqli_query($conn, "SELECT COALESCE(SUM(amount_raised),0) AS t FROM prescriptions"); $donations_total = mysqli_fetch_assoc($r)['t'];

// Verified doctors for carousel
$doc_res = mysqli_query($conn,
    "SELECT u.full_name, d.specialty FROM users u
     JOIN doctors d ON u.id = d.user_id
     WHERE d.is_verified = 1 ORDER BY RAND() LIMIT 6"
);
$carousel_doctors = mysqli_fetch_all($doc_res, MYSQLI_ASSOC);

// Fallback mock doctors if DB is empty
if (empty($carousel_doctors)) {
    $carousel_doctors = [
        ['full_name' => 'Dr. Sarah Jenkins',  'specialty' => 'Cardiology'],
        ['full_name' => 'Dr. Ahmed Youssef',  'specialty' => 'Pediatrics'],
        ['full_name' => 'Dr. Emily Chen',     'specialty' => 'Neurology'],
        ['full_name' => 'Dr. Marcus Johnson', 'specialty' => 'General Practice'],
        ['full_name' => 'Dr. Leila Mansour',  'specialty' => 'Dermatology'],
        ['full_name' => 'Dr. James O\'Brien', 'specialty' => 'Orthopedics'],
    ];
}

include 'includes/header.php';
?>

<style>
/* ── Google Font ── */
@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600&display=swap');

:root {
    --blue:  #0d6efd;
    --teal:  #0dcaf0;
    --green: #198754;
    --gold:  #ffc107;
}

/* ── Hero ── */
.hero {
    background: linear-gradient(135deg, #0d1b4b 0%, #1a3a8f 60%, #0d6efd 100%);
    border-radius: 24px;
    padding: 5rem 3rem;
    position: relative;
    overflow: hidden;
    color: #fff;
    margin-bottom: 4rem;
}
.hero::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse at 70% 50%, rgba(13,202,240,.18) 0%, transparent 60%);
}
.hero-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(2.2rem, 5vw, 3.6rem);
    font-weight: 900;
    line-height: 1.15;
}
.hero-badge {
    display: inline-block;
    background: rgba(255,255,255,.15);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 50px;
    padding: .35rem 1rem;
    font-size: .82rem;
    font-weight: 600;
    letter-spacing: .5px;
    margin-bottom: 1.5rem;
    color: #fff;
}
.hero-search {
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 16px;
    padding: 1.5rem;
}
.hero-search input {
    background: rgba(255,255,255,.9);
    border: none;
    border-radius: 10px;
    height: 50px;
}
.hero-search input:focus { box-shadow: 0 0 0 3px rgba(255,255,255,.4); }
.hero-search .btn { height: 50px; border-radius: 10px; font-weight: 600; }

.floating-card {
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 14px;
    padding: .85rem 1.25rem;
    display: inline-flex; align-items: center; gap: .75rem;
    color: #fff; font-size: .9rem; font-weight: 600;
    animation: float 3s ease-in-out infinite;
}
.floating-card:nth-child(2) { animation-delay: 1s; }
.floating-card:nth-child(3) { animation-delay: 2s; }
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50%       { transform: translateY(-8px); }
}

/* ── Animated counters ── */
.stat-card {
    border-radius: 18px;
    padding: 2rem 1.5rem;
    text-align: center;
    transition: transform .25s, box-shadow .25s;
    cursor: default;
}
.stat-card:hover { transform: translateY(-6px); box-shadow: 0 16px 40px rgba(0,0,0,.12); }
.stat-number {
    font-family: 'Playfair Display', serif;
    font-size: 3rem; font-weight: 900;
    line-height: 1;
}

/* ── Section titles ── */
.section-eyebrow {
    font-size: .8rem; font-weight: 700;
    letter-spacing: 2px; text-transform: uppercase;
    color: var(--blue); margin-bottom: .5rem;
}
.section-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.8rem, 3vw, 2.4rem);
    font-weight: 700; line-height: 1.2;
}

/* ── Doctor carousel ── */
.doc-carousel-wrap { overflow: hidden; position: relative; }
.doc-track {
    display: flex; gap: 1.25rem;
    transition: transform .5s cubic-bezier(.4,0,.2,1);
    will-change: transform;
}
.doc-card {
    min-width: 220px; flex: 0 0 220px;
    background: #fff; border: 1.5px solid #e9ecef;
    border-radius: 18px; padding: 1.75rem 1.25rem;
    text-align: center;
    transition: border-color .2s, box-shadow .2s, transform .2s;
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
}
.doc-card:hover {
    border-color: var(--blue);
    box-shadow: 0 8px 28px rgba(13,110,253,.15);
    transform: translateY(-4px);
}
.doc-avatar {
    width: 72px; height: 72px; border-radius: 50%;
    background: linear-gradient(135deg, #e8f4ff, #c9e8ff);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 2rem; margin-bottom: .9rem;
    border: 3px solid #fff;
    box-shadow: 0 4px 12px rgba(13,110,253,.18);
}
.carousel-btn {
    width: 42px; height: 42px; border-radius: 50%;
    border: 2px solid #dee2e6; background: #fff;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all .2s;
    font-size: 1.1rem; color: #495057;
}
.carousel-btn:hover { border-color: var(--blue); color: var(--blue); background: #f0f5ff; }

/* ── Testimonial carousel ── */
.testimonial-card {
    background: #fff; border-radius: 18px;
    border: 1.5px solid #e9ecef;
    padding: 2rem; position: relative;
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
}
.testimonial-card::before {
    content: '\201C';
    font-family: 'Playfair Display', serif;
    font-size: 5rem; color: #e8f0ff;
    position: absolute; top: -10px; left: 16px;
    line-height: 1;
}

/* ── How it works ── */
.step-circle {
    width: 52px; height: 52px; border-radius: 50%;
    background: linear-gradient(135deg, var(--blue), #0a58ca);
    color: #fff; font-weight: 700; font-size: 1.2rem;
    display: inline-flex; align-items: center; justify-content: center;
    box-shadow: 0 6px 18px rgba(13,110,253,.3);
    margin-bottom: 1rem;
}
.step-connector {
    flex: 1; height: 2px;
    background: linear-gradient(90deg, #c9e8ff, #e9ecef);
    margin: 0 .5rem; margin-top: -1.6rem;
}

/* ── Premium plans ── */
.plan-card {
    border-radius: 20px; border: 2px solid #e9ecef;
    padding: 2.25rem 2rem; transition: border-color .2s, box-shadow .2s, transform .2s;
}
.plan-card:hover { transform: translateY(-5px); box-shadow: 0 12px 35px rgba(0,0,0,.1); }
.plan-card.featured { border-color: var(--gold); background: #fffdf0; }
.plan-price {
    font-family: 'Playfair Display', serif;
    font-size: 2.8rem; font-weight: 900;
}
.check-item { display: flex; align-items: center; gap: .6rem; margin-bottom: .6rem; }

/* ── CTA banner ── */
.cta-banner {
    background: linear-gradient(135deg, #0d1b4b, #1a3a8f);
    border-radius: 20px; color: #fff;
    padding: 3.5rem 3rem; text-align: center;
    position: relative; overflow: hidden;
}
.cta-banner::after {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(circle at 80% 20%, rgba(13,202,240,.2), transparent 50%);
}

/* ── Scroll-reveal ── */
.reveal { opacity: 0; transform: translateY(30px); transition: opacity .7s ease, transform .7s ease; }
.reveal.visible { opacity: 1; transform: none; }

/* ── Auto-scroll dots ── */
.carousel-dots { display: flex; gap: 8px; justify-content: center; margin-top: 1rem; }
.carousel-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #dee2e6; cursor: pointer; transition: background .2s, width .2s;
    border: none;
}
.carousel-dot.active { background: var(--blue); width: 24px; border-radius: 4px; }
</style>

<!-- ══════════════════════ HERO ══════════════════════ -->
<div class="hero mb-5 reveal">
    <div class="row align-items-center gy-4">
        <div class="col-lg-6">
            <div class="hero-badge">🌍 Humanitarian Health Platform</div>
            <h1 class="hero-title mb-3">
                Healthcare for<br><span style="color:#7ec8f5;">Everyone,</span><br>Everywhere.
            </h1>
            <p class="mb-4" style="color:rgba(255,255,255,.8);font-size:1.1rem;max-width:480px;">
                Connecting volunteer doctors with patients in need. Donate, volunteer, or request medical assistance today — completely free.
            </p>
            <div class="d-flex flex-wrap gap-3 mb-4">
                <a href="register.php?role=patient" class="btn btn-light btn-lg fw-bold px-4"
                   style="border-radius:12px;">I Need Help</a>
                <a href="register.php?role=doctor" class="btn btn-lg fw-bold px-4"
                   style="border-radius:12px;border:2px solid rgba(255,255,255,.5);color:#fff;">
                    I'm a Doctor <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <div class="floating-card"><span>⚡</span> Instant Matching</div>
                <div class="floating-card"><span>🔒</span> 100% Secure</div>
                <div class="floating-card"><span>❤️</span> Verified Doctors</div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="hero-search">
                <p class="mb-2 fw-bold" style="color:#fff;">Find a Specialist</p>
                <form action="search.php" method="GET" class="d-flex gap-2">
                    <input type="text" name="query" class="form-control flex-grow-1"
                           placeholder="e.g., Cardiology, Pediatrics…">
                    <button type="submit" class="btn btn-warning fw-bold px-4">Search</button>
                </form>
                <small style="color:rgba(255,255,255,.6);" class="d-block mt-2">
                    <i class="bi bi-check-circle me-1"></i> No account required to browse
                </small>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════ LIVE STATS ══════════════════════ -->
<div class="row g-4 mb-5 reveal">
    <div class="col-6 col-md-3">
        <div class="stat-card border-0 shadow-sm bg-primary text-white">
            <div class="stat-number counter" data-target="<?php echo $patients_count ?: 1200; ?>">0</div>
            <p class="mb-0 mt-1 fw-500">Patients Helped</p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card border-0 shadow-sm bg-success text-white">
            <div class="stat-number counter" data-target="<?php echo $doctors_count ?: 350; ?>">0</div>
            <p class="mb-0 mt-1 fw-500">Volunteer Doctors</p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card border-0 shadow-sm bg-warning text-dark">
            <div class="stat-number">${<?php echo number_format($donations_total ?: 45000, 0); ?>}</div>
            <p class="mb-0 mt-1 fw-500">Donations Raised</p>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card border-0 shadow-sm bg-info text-white">
            <div class="stat-number counter" data-target="<?php echo $total_users ?: 1540; ?>">0</div>
            <p class="mb-0 mt-1 fw-500">Registered Users</p>
        </div>
    </div>
</div>

<!-- ══════════════════════ DOCTOR CAROUSEL ══════════════════════ -->
<div class="mb-5 reveal">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <div class="section-eyebrow">Our Network</div>
            <h2 class="section-title mb-0">Meet Our Volunteer Doctors</h2>
        </div>
        <div class="d-flex gap-2">
            <button class="carousel-btn" id="docPrev"><i class="bi bi-chevron-left"></i></button>
            <button class="carousel-btn" id="docNext"><i class="bi bi-chevron-right"></i></button>
        </div>
    </div>
    <div class="doc-carousel-wrap">
        <div class="doc-track" id="docTrack">
            <?php
            $doc_avatars = ['👨‍⚕️','👩‍⚕️','🧑‍⚕️','👨‍⚕️','👩‍⚕️','🧑‍⚕️'];
            $spec_colors = [
                'Cardiology' => '#e8f4ff', 'Pediatrics' => '#e8fff0',
                'Neurology'  => '#f5e8ff', 'General Practice' => '#fff8e8',
                'Dermatology'=> '#ffe8f5', 'Orthopedics' => '#e8fffc',
            ];
            foreach ($carousel_doctors as $i => $doc):
                $bg = $spec_colors[$doc['specialty']] ?? '#f0f5ff';
            ?>
            <div class="doc-card">
                <div class="doc-avatar" style="background:<?php echo $bg; ?>;">
                    <?php echo $doc_avatars[$i % count($doc_avatars)]; ?>
                </div>
                <div class="fw-bold mb-1"><?php echo htmlspecialchars($doc['full_name']); ?></div>
                <div class="text-primary small fw-bold mb-2"><?php echo htmlspecialchars($doc['specialty']); ?></div>
                <span class="badge bg-success-subtle text-success">
                    <i class="bi bi-check-circle me-1"></i>Verified
                </span>
                <div class="mt-3">
                    <a href="search.php" class="btn btn-sm btn-outline-primary w-100 rounded-3">
                        Request
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="carousel-dots" id="docDots"></div>
</div>

<!-- ══════════════════════ HOW IT WORKS ══════════════════════ -->
<div class="mb-5 py-2 reveal">
    <div class="text-center mb-4">
        <div class="section-eyebrow">Simple Process</div>
        <h2 class="section-title">How CareConnect Works</h2>
    </div>
    <div class="row g-4">
        <?php
        $steps = [
            ['icon'=>'bi-person-plus-fill','color'=>'#0d6efd','n'=>1,'title'=>'Create Your Account','desc'=>'Register as a patient or volunteer doctor in under 2 minutes.'],
            ['icon'=>'bi-file-earmark-check-fill','color'=>'#198754','n'=>2,'title'=>'Get Verified','desc'=>'Upload your documents. Our admin team validates your identity quickly.'],
            ['icon'=>'bi-search-heart-fill','color'=>'#6f42c1','n'=>3,'title'=>'Find or Get Matched','desc'=>'Doctors browse patient requests. Premium patients get priority placement.'],
            ['icon'=>'bi-chat-dots-fill','color'=>'#0dcaf0','n'=>4,'title'=>'Receive Care','desc'=>'Connect, consult, and get the medical help you need — at no cost.'],
        ];
        foreach ($steps as $s):
        ?>
        <div class="col-md-3 col-sm-6 text-center">
            <div class="step-circle mx-auto" style="background:linear-gradient(135deg,<?php echo $s['color']; ?>,<?php echo $s['color']; ?>cc);">
                <?php echo $s['n']; ?>
            </div>
            <div class="mb-2 fs-2"><i class="bi <?php echo $s['icon']; ?>" style="color:<?php echo $s['color']; ?>;"></i></div>
            <h5 class="fw-bold mb-1"><?php echo $s['title']; ?></h5>
            <p class="text-muted small"><?php echo $s['desc']; ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ══════════════════════ TESTIMONIALS CAROUSEL ══════════════════════ -->
<div class="mb-5 reveal" style="background:#f8f9fa;border-radius:20px;padding:2.5rem;">
    <div class="text-center mb-4">
        <div class="section-eyebrow">Testimonials</div>
        <h2 class="section-title">What Our Community Says</h2>
    </div>

    <?php
    $testimonials = [
        ['quote'=>"CareConnect connected me with a cardiologist within hours. I can't believe this service is free. It literally saved my life.",
         'name'=>'Mohammed A.','role'=>'Patient, Tunisia','avatar'=>'🤒'],
        ['quote'=>"As a volunteer doctor, this platform lets me help people who truly need it. The system is clean and easy to use.",
         'name'=>'Dr. Sarah J.','role'=>'Cardiologist','avatar'=>'👩‍⚕️'],
        ['quote'=>"I donated to a patient's prescription fund and could track the progress in real time. So transparent and trustworthy.",
         'name'=>'Leila M.','role'=>'Donor','avatar'=>'💝'],
        ['quote'=>"The premium plan was worth it — I got matched with a specialist the same day I registered.",
         'name'=>'Fatima B.','role'=>'Premium Patient','avatar'=>'⭐'],
    ];
    ?>

    <div style="position:relative; overflow:hidden;">
        <div id="testimonialTrack" style="display:flex; transition:transform .5s cubic-bezier(.4,0,.2,1);">
            <?php foreach ($testimonials as $t): ?>
            <div style="min-width:100%; padding: 0 .5rem;">
                <div class="testimonial-card mx-auto" style="max-width:640px;">
                    <p class="mb-3" style="font-size:1.05rem;color:#343a40;position:relative;z-index:1;">
                        <?php echo htmlspecialchars($t['quote']); ?>
                    </p>
                    <div class="d-flex align-items-center gap-3 mt-3">
                        <div style="width:46px;height:46px;border-radius:50%;background:#f0f5ff;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
                            <?php echo $t['avatar']; ?>
                        </div>
                        <div>
                            <div class="fw-bold"><?php echo $t['name']; ?></div>
                            <small class="text-muted"><?php echo $t['role']; ?></small>
                        </div>
                        <div class="ms-auto">
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="carousel-dots mt-3" id="testimonialDots"></div>
</div>

<!-- ══════════════════════ PRICING PLANS ══════════════════════ -->
<div class="mb-5 reveal" id="premium">
    <div class="text-center mb-4">
        <div class="section-eyebrow">Pricing</div>
        <h2 class="section-title">Choose Your Care Plan</h2>
        <p class="text-muted">Get help for free, or upgrade for priority access to doctors.</p>
    </div>
    <div class="row justify-content-center g-4">
        <div class="col-md-5">
            <div class="plan-card border-0 shadow-sm h-100">
                <h4 class="fw-bold mb-1">Standard Access</h4>
                <div class="plan-price text-primary mb-1">Free</div>
                <p class="text-muted small mb-4">For patients needing standard assistance.</p>
                <div class="check-item"><i class="bi bi-check-circle-fill text-success"></i> Standard matching with doctors</div>
                <div class="check-item"><i class="bi bi-check-circle-fill text-success"></i> Upload prescriptions for donations</div>
                <div class="check-item"><i class="bi bi-check-circle-fill text-success"></i> Standard waiting times</div>
                <div class="check-item"><i class="bi bi-x-circle-fill text-muted"></i> No priority placement</div>
                <a href="register.php?role=patient" class="btn btn-outline-primary w-100 mt-4 rounded-3 fw-bold py-2">
                    Get Started Free
                </a>
            </div>
        </div>
        <div class="col-md-5">
            <div class="plan-card featured shadow-sm h-100 position-relative">
                <span class="position-absolute top-0 start-50 translate-middle badge bg-warning text-dark px-3 py-2 rounded-pill fs-6">
                    ⭐ Recommended
                </span>
                <h4 class="fw-bold mb-1 mt-3">Premium Access</h4>
                <div class="plan-price text-warning mb-1">$15<span class="fs-6 text-muted">/mo</span></div>
                <p class="text-muted small mb-4">Skip the line and get matched faster.</p>
                <div class="check-item"><i class="bi bi-check-circle-fill text-warning"></i> <strong>Always #1</strong> in doctor queues</div>
                <div class="check-item"><i class="bi bi-check-circle-fill text-warning"></i> Faster document verification</div>
                <div class="check-item"><i class="bi bi-check-circle-fill text-warning"></i> 24/7 priority messaging</div>
                <div class="check-item"><i class="bi bi-check-circle-fill text-warning"></i> Dedicated support agent</div>
                <button class="btn btn-warning w-100 mt-4 rounded-3 fw-bold py-2 shadow-sm"
                        onclick="alert('Redirecting to secure payment gateway...')">
                    Upgrade to Premium
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════ CTA BANNER ══════════════════════ -->
<div class="cta-banner mb-5 reveal">
    <div style="position:relative;z-index:1;">
        <h2 class="section-title text-white mb-3">Ready to Make a Difference?</h2>
        <p class="mb-4" style="color:rgba(255,255,255,.8);max-width:500px;margin:0 auto 1.5rem;">
            Join thousands of patients and doctors already using CareConnect to transform humanitarian healthcare.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="register.php?role=patient" class="btn btn-light btn-lg fw-bold px-5 rounded-3">
                <i class="bi bi-heart-fill text-danger me-2"></i> Get Help Now
            </a>
            <a href="donate.php" class="btn btn-warning btn-lg fw-bold px-5 rounded-3">
                <i class="bi bi-gift-fill me-2"></i> Donate
            </a>
        </div>
    </div>
</div>

<script>
// ── Scroll reveal ───────────────────────────────────
const reveals = document.querySelectorAll('.reveal');
const revealObs = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); } });
}, { threshold: 0.12 });
reveals.forEach(el => revealObs.observe(el));

// ── Counter animation ───────────────────────────────
const counters = document.querySelectorAll('.counter');
const countObs = new IntersectionObserver(entries => {
    entries.forEach(e => {
        if (!e.isIntersecting) return;
        const el     = e.target;
        const target = +el.dataset.target;
        const dur    = 1800;
        const step   = target / (dur / 16);
        let current  = 0;
        const timer  = setInterval(() => {
            current = Math.min(current + step, target);
            el.textContent = Math.floor(current).toLocaleString();
            if (current >= target) clearInterval(timer);
        }, 16);
        countObs.unobserve(el);
    });
}, { threshold: 0.5 });
counters.forEach(el => countObs.observe(el));

// ── Doctor carousel ─────────────────────────────────
(function () {
    const track   = document.getElementById('docTrack');
    const dotsEl  = document.getElementById('docDots');
    const cards   = track.querySelectorAll('.doc-card');
    const visible = () => window.innerWidth < 576 ? 1 : window.innerWidth < 992 ? 2 : 3;
    let idx = 0;

    function buildDots() {
        dotsEl.innerHTML = '';
        const pages = Math.ceil(cards.length / visible());
        for (let i = 0; i < pages; i++) {
            const d = document.createElement('button');
            d.className = 'carousel-dot' + (i === 0 ? ' active' : '');
            d.onclick = () => goTo(i);
            dotsEl.appendChild(d);
        }
    }
    function goTo(n) {
        const pages = Math.ceil(cards.length / visible());
        idx = Math.max(0, Math.min(n, pages - 1));
        const cardW = cards[0].offsetWidth + 20;
        track.style.transform = `translateX(-${idx * visible() * cardW}px)`;
        dotsEl.querySelectorAll('.carousel-dot').forEach((d, i) => d.classList.toggle('active', i === idx));
    }
    document.getElementById('docNext').onclick = () => goTo(idx + 1);
    document.getElementById('docPrev').onclick = () => goTo(idx - 1);
    buildDots();
    window.addEventListener('resize', () => { buildDots(); goTo(0); });

    // Auto-scroll
    setInterval(() => {
        const pages = Math.ceil(cards.length / visible());
        goTo((idx + 1) % pages);
    }, 4000);
})();

// ── Testimonial carousel ─────────────────────────────
(function () {
    const track  = document.getElementById('testimonialTrack');
    const dotsEl = document.getElementById('testimonialDots');
    const slides = track.children.length;
    let idx = 0;

    for (let i = 0; i < slides; i++) {
        const d = document.createElement('button');
        d.className = 'carousel-dot' + (i === 0 ? ' active' : '');
        d.onclick = () => goTo(i);
        dotsEl.appendChild(d);
    }
    function goTo(n) {
        idx = (n + slides) % slides;
        track.style.transform = `translateX(-${idx * 100}%)`;
        dotsEl.querySelectorAll('.carousel-dot').forEach((d, i) => d.classList.toggle('active', i === idx));
    }
    setInterval(() => goTo(idx + 1), 5000);
})();
</script>

<?php include 'includes/footer.php'; ?>