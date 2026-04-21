<?php
include 'includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.php");
    exit;
}

$doctor_id = (int) $_SESSION['user_id'];
$message   = '';

// Accept a case
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_request_id'])) {
    $req_id = (int) $_POST['accept_request_id'];
    mysqli_query($conn,
        "UPDATE consultation_requests
         SET status = 'active', doctor_id = $doctor_id
         WHERE id = $req_id AND status = 'pending'"
    );
    $message = "<div class='alert alert-success'>You have accepted the case. The patient will be notified.</div>";
}

// Fetch pending requests (premium first, then oldest)
$result = mysqli_query($conn,
    "SELECT cr.id, u.full_name AS patient_name, cr.issue_description, cr.is_premium, cr.created_at
     FROM consultation_requests cr
     JOIN users u ON cr.patient_id = u.id
     WHERE cr.status = 'pending'
     ORDER BY cr.is_premium DESC, cr.created_at ASC"
);

$requests = mysqli_fetch_all($result, MYSQLI_ASSOC);

include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="list-group shadow-sm">
            <a href="doctor_dashboard.php" class="list-group-item list-group-item-action active">Available Patients</a>
            <a href="logout.php" class="list-group-item list-group-item-action text-danger fw-bold">Logout</a>
        </div>
    </div>

    <div class="col-md-9">
        <?php echo $message; ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-2">
                <h4 class="fw-bold">Patient Requests Queue</h4>
                <p class="text-muted mb-0">Browse patients who need your help. Priority is given to Premium patients.</p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle border">
                        <thead class="table-light">
                            <tr>
                                <th>Patient Name</th>
                                <th>Medical Issue</th>
                                <th>Requested On</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($requests) > 0): ?>
                                <?php foreach ($requests as $req): ?>
                                <tr class="<?php echo $req['is_premium'] ? 'table-warning' : ''; ?>">
                                    <td class="fw-bold">
                                        <?php echo htmlspecialchars($req['patient_name']); ?>
                                        <?php if ($req['is_premium']): ?>
                                            <br><span class="badge bg-warning text-dark mt-1">⭐ Priority Premium</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($req['issue_description']); ?></td>
                                    <td class="text-muted small"><?php echo $req['created_at']; ?></td>
                                    <td>
                                        <form method="POST">
                                            <input type="hidden" name="accept_request_id" value="<?php echo $req['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-primary">Accept Case</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No patients waiting for assistance.</td>
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