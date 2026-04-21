<?php
include 'includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
$total_users = mysqli_fetch_assoc($res)['total'];

$res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM doctors WHERE is_verified = 1");
$verified_doctors = mysqli_fetch_assoc($res)['total'];

$res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM patients WHERE is_verified = 1");
$verified_patients = mysqli_fetch_assoc($res)['total'];

$res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM patients WHERE is_premium = 1");
$premium_patients = mysqli_fetch_assoc($res)['total'];

$res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM consultation_requests");
$total_requests = mysqli_fetch_assoc($res)['total'];

include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="list-group shadow-sm">
            <a href="admin_dashboard.php" class="list-group-item list-group-item-action">Dashboard Overview</a>
            <a href="admin_validations.php" class="list-group-item list-group-item-action">Pending Validations</a>
            <a href="admin_users.php" class="list-group-item list-group-item-action">Manage Users</a>
            <a href="admin_stats.php" class="list-group-item list-group-item-action active">Platform Statistics</a>
            <a href="logout.php" class="list-group-item list-group-item-action text-danger fw-bold">Logout</a>
        </div>
    </div>

    <div class="col-md-9">
        <h4 class="fw-bold mb-4">Platform Statistics</h4>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm bg-primary text-white p-4 h-100 text-center">
                    <h1 class="display-4 fw-bold"><?php echo $total_users; ?></h1>
                    <h5 class="mb-0">Total Registered Users</h5>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm bg-success text-white p-4 h-100 text-center">
                    <h1 class="display-4 fw-bold"><?php echo $verified_doctors; ?></h1>
                    <h5 class="mb-0">Verified Doctors</h5>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card border-0 shadow-sm bg-info text-white p-4 h-100 text-center">
                    <h1 class="display-4 fw-bold"><?php echo $verified_patients; ?></h1>
                    <h5 class="mb-0">Verified Patients</h5>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm bg-warning text-dark p-4 h-100 d-flex flex-row align-items-center">
                    <h1 class="display-4 fw-bold mb-0 me-4"><?php echo $premium_patients; ?></h1>
                    <div>
                        <h4 class="fw-bold mb-1">Premium Subscriptions</h4>
                        <p class="mb-0">Patients with priority access.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm bg-secondary text-white p-4 h-100 d-flex flex-row align-items-center">
                    <h1 class="display-4 fw-bold mb-0 me-4"><?php echo $total_requests; ?></h1>
                    <div>
                        <h4 class="fw-bold mb-1">Total Medical Requests</h4>
                        <p class="mb-0">Consultations requested via the platform.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>