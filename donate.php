<?php
include 'includes/db.php';
include 'includes/header.php';

// Fetch prescriptions that still need funding, joining patient name
$result = mysqli_query($conn,
    "SELECT p.id, p.medication_desc, p.amount_needed, p.amount_raised,
            u.full_name AS patient_name
     FROM prescriptions p
     JOIN users u ON p.patient_id = u.id
     WHERE p.amount_raised < p.amount_needed
     ORDER BY (p.amount_needed - p.amount_raised) DESC"
);

$prescriptions = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<div class="bg-primary text-white rounded-4 p-5 text-center shadow-sm mb-5">
    <h1 class="display-5 fw-bold">Support a Patient in Need</h1>
    <p class="lead mb-4">Your donation goes directly to funding life-saving prescriptions for those who cannot afford them.</p>
    <a href="#urgent-cases" class="btn btn-light btn-lg text-primary fw-bold">View Urgent Cases</a>
</div>

<?php if (count($prescriptions) === 0): ?>
    <div class="text-center py-5">
        <h4 class="text-muted">All prescriptions are fully funded! Check back soon.</h4>
    </div>
<?php else: ?>
<div class="row" id="urgent-cases">
    <div class="col-12 mb-4">
        <h3 class="fw-bold">Urgent Prescription Requests</h3>
        <p class="text-muted">Help these patients reach their goals to purchase required medications.</p>
    </div>

    <?php foreach ($prescriptions as $rx):
        $percent = min(100, ($rx['amount_raised'] / $rx['amount_needed']) * 100);
    ?>
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-light text-dark border">Case #<?php echo $rx['id']; ?></span>
                    <small class="text-muted">Verified ✅</small>
                </div>
                <h5 class="fw-bold mt-3"><?php echo htmlspecialchars($rx['medication_desc']); ?></h5>
                <p class="text-muted small">Requested by: Anonymous Patient</p>

                <div class="mt-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-bold text-success">$<?php echo number_format($rx['amount_raised'], 2); ?> raised</span>
                        <span class="text-muted">Goal: $<?php echo number_format($rx['amount_needed'], 2); ?></span>
                    </div>
                    <div class="progress" style="height:10px;">
                        <div class="progress-bar bg-success" style="width:<?php echo $percent; ?>%"
                             aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <hr class="my-4">

                <form method="POST" action="process_donation.php">
                    <input type="hidden" name="prescription_id" value="<?php echo $rx['id']; ?>">
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-white">$</span>
                        <input type="number" name="amount" class="form-control" placeholder="Amount" min="1" step="0.01" required>
                    </div>
                    <button type="button" class="btn btn-primary w-100"
                            data-bs-toggle="modal" data-bs-target="#donationModal"
                            onclick="document.getElementById('modal_prescription_id').value='<?php echo $rx['id']; ?>'">
                        Donate Now
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Donation Modal -->
<div class="modal fade" id="donationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Complete Your Donation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="alert alert-info">Thank you for choosing to help. This is a simulated checkout.</div>
                <form id="donationForm" method="POST" action="process_donation.php">
                    <input type="hidden" name="prescription_id" id="modal_prescription_id">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Cardholder Name</label>
                        <input type="text" class="form-control" name="cardholder" placeholder="John Doe">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small">Card Number</label>
                        <input type="text" class="form-control" placeholder="•••• •••• •••• ••••" maxlength="19">
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <label class="form-label text-muted small">Expiry (MM/YY)</label>
                            <input type="text" class="form-control" placeholder="12/28" maxlength="5">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small">CVC</label>
                            <input type="text" class="form-control" placeholder="123" maxlength="3">
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label text-muted small">Amount ($)</label>
                        <input type="number" class="form-control" name="amount" min="1" step="0.01" placeholder="5.00" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="submit" form="donationForm" class="btn btn-success w-100 mt-2">Confirm Payment</button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>