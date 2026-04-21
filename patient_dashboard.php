<?php
include 'includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.php");
    exit;
}

$patient_id = (int) $_SESSION['user_id'];

// Fetch patient's own requests
$result = mysqli_query($conn,
    "SELECT cr.id, cr.issue_description, cr.status, cr.created_at,
            u.full_name AS doctor_name
     FROM consultation_requests cr
     LEFT JOIN users u ON cr.doctor_id = u.id
     WHERE cr.patient_id = $patient_id
     ORDER BY cr.created_at DESC"
);
$my_requests = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Check premium status
$res     = mysqli_query($conn, "SELECT is_premium, is_verified FROM patients WHERE user_id = $patient_id");
$patient = mysqli_fetch_assoc($res);
$is_premium  = $patient['is_premium']  ?? 0;
$is_verified = $patient['is_verified'] ?? 0;

// Handle new request submission
$submit_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_description'])) {
    if (!$is_verified) {
        $submit_msg = "<div class='alert alert-danger'>Your account must be verified before submitting a request.</div>";
    } else {
        $issue = mysqli_real_escape_string($conn, trim($_POST['issue_description']));
        mysqli_query($conn,
            "INSERT INTO consultation_requests (patient_id, issue_description, is_premium)
             VALUES ($patient_id, '$issue', $is_premium)"
        );
        $submit_msg = "<div class='alert alert-success'>Your request has been submitted!</div>";
        // Refresh list
        $result = mysqli_query($conn,
            "SELECT cr.id, cr.issue_description, cr.status, cr.created_at,
                    u.full_name AS doctor_name
             FROM consultation_requests cr
             LEFT JOIN users u ON cr.doctor_id = u.id
             WHERE cr.patient_id = $patient_id
             ORDER BY cr.created_at DESC"
        );
        $my_requests = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}

$status_colors = [
    'pending'   => 'secondary',
    'active'    => 'primary',
    'completed' => 'success',
    'cancelled' => 'danger',
];

include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="list-group shadow-sm">
            <a href="patient_dashboard.php" class="list-group-item list-group-item-action active">My Dashboard</a>
            <a href="index.php#premium" class="list-group-item list-group-item-action text-warning fw-bold">Go Premium ⭐</a>
            <a href="logout.php" class="list-group-item list-group-item-action text-danger fw-bold">Logout</a>
        </div>

        <?php if (!$is_verified): ?>
        <div class="alert alert-info mt-3 small">
            ⏳ Your account is <strong>pending verification</strong> by an admin.
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-9">
        <?php if (!$is_premium): ?>
        <div class="alert alert-warning d-flex align-items-center rounded-3 mb-4">
            <div class="me-auto">
                <h5 class="alert-heading mb-1 fw-bold">Need help faster?</h5>
                <p class="mb-0">Upgrade to <strong>Premium</strong> for priority matching with available doctors 24/7.</p>
            </div>
            <a href="index.php#premium" class="btn btn-warning fw-bold ms-3">Upgrade Now</a>
        </div>
        <?php endif; ?>

        <!-- Submit new request -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Submit a New Request</h5>
                <?php echo $submit_msg; ?>
                <form method="POST">
                    <div class="mb-3">
                        <textarea class="form-control" name="issue_description" rows="3"
                                  placeholder="Describe your medical issue..." required
                                  <?php echo !$is_verified ? 'disabled' : ''; ?>></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"
                            <?php echo !$is_verified ? 'disabled' : ''; ?>>Submit Request</button>
                </form>
            </div>
        </div>

        <!-- Active requests table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Your Requests</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle border">
                        <thead class="table-light">
                            <tr>
                                <th>Request ID</th>
                                <th>Issue / Description</th>
                                <th>Status</th>
                                <th>Assigned Doctor</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($my_requests) > 0): ?>
                                <?php foreach ($my_requests as $req): ?>
                                <tr>
                                    <td class="fw-bold">#<?php echo $req['id']; ?></td>
                                    <td><?php echo htmlspecialchars($req['issue_description']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $status_colors[$req['status']] ?? 'secondary'; ?>">
                                            <?php echo ucfirst($req['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $req['doctor_name'] ? htmlspecialchars($req['doctor_name']) : '<span class="text-muted">Unassigned</span>'; ?></td>
                                    <td class="text-muted small"><?php echo $req['created_at']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">You have no requests yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>