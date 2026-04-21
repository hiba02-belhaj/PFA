<?php
include 'includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';

// Handle approval / rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['user_id'], $_POST['user_role'])) {
    $user_id   = (int) $_POST['user_id'];
    $user_role = $_POST['user_role'];
    $action    = $_POST['action'];

    if ($action === 'approve') {
        if ($user_role === 'doctor') {
            mysqli_query($conn, "UPDATE doctors SET is_verified = 1 WHERE user_id = $user_id");
        } elseif ($user_role === 'patient') {
            mysqli_query($conn, "UPDATE patients SET is_verified = 1 WHERE user_id = $user_id");
        }
        $message = "<div class='alert alert-success'>User successfully verified!</div>";

    } elseif ($action === 'reject') {
        // Delete the user entirely (cascades to doctors/patients via FK)
        mysqli_query($conn, "DELETE FROM users WHERE id = $user_id");
        $message = "<div class='alert alert-warning'>User rejected and removed from the system.</div>";
    }
}

// Fetch pending doctors
$pending_doctors = mysqli_query($conn,
    "SELECT u.id, u.full_name, d.license_number AS document, u.created_at
     FROM users u
     JOIN doctors d ON u.id = d.user_id
     WHERE d.is_verified = 0
     ORDER BY u.created_at ASC"
);

// Fetch pending patients
$pending_patients = mysqli_query($conn,
    "SELECT u.id, u.full_name, p.financial_proof_path AS document, u.created_at
     FROM users u
     JOIN patients p ON u.id = p.user_id
     WHERE p.is_verified = 0
     ORDER BY u.created_at ASC"
);

include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="list-group shadow-sm">
            <a href="admin_dashboard.php" class="list-group-item list-group-item-action">Dashboard Overview</a>
            <a href="admin_validations.php" class="list-group-item list-group-item-action active">Pending Validations</a>
            <a href="admin_users.php" class="list-group-item list-group-item-action">Manage Users</a>
            <a href="admin_stats.php" class="list-group-item list-group-item-action">Platform Statistics</a>
            <a href="logout.php" class="list-group-item list-group-item-action text-danger fw-bold">Logout</a>
        </div>
    </div>

    <div class="col-md-9">
        <?php echo $message; ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h4 class="fw-bold mb-4">Pending Document Validations</h4>
                <div class="table-responsive">
                    <table class="table table-hover align-middle border">
                        <thead class="table-light">
                            <tr>
                                <th>User Type</th>
                                <th>Name</th>
                                <th>Submitted Document</th>
                                <th>Date Submitted</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $has_records = false;

                            // Doctors
                            while ($doc = mysqli_fetch_assoc($pending_doctors)):
                                $has_records = true;
                            ?>
                            <tr>
                                <td><span class="badge bg-success">Doctor</span></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($doc['full_name']); ?></td>
                                <td>📄 License: <?php echo htmlspecialchars($doc['document']); ?></td>
                                <td class="text-muted small"><?php echo $doc['created_at']; ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id"   value="<?php echo $doc['id']; ?>">
                                        <input type="hidden" name="user_role" value="doctor">
                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success me-1">Approve</button>
                                        <button type="submit" name="action" value="reject"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Reject and delete this user?')">Reject</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>

                            <?php
                            // Patients
                            while ($pat = mysqli_fetch_assoc($pending_patients)):
                                $has_records = true;
                            ?>
                            <tr>
                                <td><span class="badge bg-secondary">Patient</span></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($pat['full_name']); ?></td>
                                <td>📄 Proof: <?php echo htmlspecialchars($pat['document']); ?></td>
                                <td class="text-muted small"><?php echo $pat['created_at']; ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="user_id"   value="<?php echo $pat['id']; ?>">
                                        <input type="hidden" name="user_role" value="patient">
                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success me-1">Approve</button>
                                        <button type="submit" name="action" value="reject"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Reject and delete this user?')">Reject</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>

                            <?php if (!$has_records): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No pending documents to review.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>