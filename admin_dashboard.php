<?php
include 'includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Live platform overview counts
$res         = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
$total_users = mysqli_fetch_assoc($res)['total'];

$res             = mysqli_query($conn, "SELECT COUNT(*) AS total FROM doctors WHERE is_verified = 1");
$verified_doctors = mysqli_fetch_assoc($res)['total'];

$res               = mysqli_query($conn, "SELECT COUNT(*) AS total FROM consultation_requests WHERE status = 'active'");
$active_consults   = mysqli_fetch_assoc($res)['total'];

// Pending validations count for badge
$res_pd = mysqli_query($conn, "SELECT COUNT(*) AS total FROM doctors WHERE is_verified = 0");
$res_pp = mysqli_query($conn, "SELECT COUNT(*) AS total FROM patients WHERE is_verified = 0");
$pending_count = mysqli_fetch_assoc($res_pd)['total'] + mysqli_fetch_assoc($res_pp)['total'];

// Recent pending validations preview (up to 5)
$pending_docs = mysqli_query($conn,
    "SELECT u.id, 'Doctor' AS type, u.full_name, d.license_number AS doc, u.created_at
     FROM users u JOIN doctors d ON u.id = d.user_id
     WHERE d.is_verified = 0
     UNION
     SELECT u.id, 'Patient' AS type, u.full_name, p.financial_proof_path AS doc, u.created_at
     FROM users u JOIN patients p ON u.id = p.user_id
     WHERE p.is_verified = 0
     ORDER BY created_at DESC LIMIT 5"
);

include 'includes/header.php';
?>

<div class="row">
    <!-- Sidebar -->
    <div class="col-md-3 mb-4">
        <div class="list-group shadow-sm">
            <a href="admin_dashboard.php" class="list-group-item list-group-item-action active">Dashboard Overview</a>
            <a href="admin_validations.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                Pending Validations
                <?php if ($pending_count > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?php echo $pending_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="admin_users.php" class="list-group-item list-group-item-action">Manage Users</a>
            <a href="admin_stats.php" class="list-group-item list-group-item-action">Platform Statistics</a>
            <a href="logout.php" class="list-group-item list-group-item-action text-danger fw-bold">Logout</a>
        </div>
    </div>

    <!-- Main content -->
    <div class="col-md-9">
        <h4 class="fw-bold mb-3">Platform Overview</h4>
        <div class="row text-center mb-5">
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm bg-primary text-white p-3 h-100">
                    <h3 class="fw-bold"><?php echo $total_users; ?></h3>
                    <p class="mb-0">Total Registered Users</p>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm bg-success text-white p-3 h-100">
                    <h3 class="fw-bold"><?php echo $verified_doctors; ?></h3>
                    <p class="mb-0">Verified Doctors</p>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card border-0 shadow-sm bg-info text-white p-3 h-100">
                    <h3 class="fw-bold"><?php echo $active_consults; ?></h3>
                    <p class="mb-0">Active Consultations</p>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold mb-0">Recent Pending Validations</h4>
                    <a href="admin_validations.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle border">
                        <thead class="table-light">
                            <tr>
                                <th>User Type</th>
                                <th>Name</th>
                                <th>Submitted Document</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $has = false;
                            while ($row = mysqli_fetch_assoc($pending_docs)):
                                $has = true;
                            ?>
                            <tr>
                                <td>
                                    <?php if ($row['type'] === 'Doctor'): ?>
                                        <span class="badge bg-success">Doctor</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Patient</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td>📄 <?php echo htmlspecialchars($row['doc']); ?></td>
                                <td class="text-muted small"><?php echo $row['created_at']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if (!$has): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No pending documents to review.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>