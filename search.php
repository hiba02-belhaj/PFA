<?php
include 'includes/db.php';
include 'includes/header.php';

$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
$safe_query   = mysqli_real_escape_string($conn, $search_query);

if ($search_query !== '') {
    $sql = "SELECT u.full_name, d.specialty
            FROM users u
            JOIN doctors d ON u.id = d.user_id
            WHERE u.role = 'doctor'
              AND d.is_verified = 1
              AND (d.specialty LIKE '%$safe_query%' OR u.full_name LIKE '%$safe_query%')
            ORDER BY u.full_name ASC";
} else {
    $sql = "SELECT u.full_name, d.specialty
            FROM users u
            JOIN doctors d ON u.id = d.user_id
            WHERE u.role = 'doctor' AND d.is_verified = 1
            ORDER BY u.full_name ASC";
}

$result  = mysqli_query($conn, $sql);
$doctors = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold text-dark">Find a Doctor</h2>
        <p class="text-muted">Search our network of volunteer medical professionals.</p>

        <div class="card border-0 shadow-sm p-3 mb-4">
            <form action="search.php" method="GET" class="d-flex">
                <input type="text" name="query" class="form-control form-control-lg me-2"
                       placeholder="Search by specialty (e.g., Cardiology) or name"
                       value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="btn btn-primary btn-lg px-4">Search</button>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <?php if ($search_query !== ''): ?>
            <h5 class="mb-4">Results for "<strong><?php echo htmlspecialchars($search_query); ?></strong>"
                (<?php echo count($doctors); ?> found)</h5>
        <?php else: ?>
            <h5 class="mb-4">All Verified Volunteer Doctors</h5>
        <?php endif; ?>
    </div>

    <?php if (count($doctors) > 0): ?>
        <?php foreach ($doctors as $doc): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center p-4">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                         style="width:80px;height:80px;">
                        <span class="fs-1">👨‍⚕️</span>
                    </div>
                    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($doc['full_name']); ?></h5>
                    <p class="text-primary fw-bold mb-3"><?php echo htmlspecialchars($doc['specialty']); ?></p>
                    <div class="d-grid mt-auto">
                        <a href="<?php echo isset($_SESSION['user_id']) ? 'patient_dashboard.php' : 'login.php'; ?>"
                           class="btn btn-outline-primary">Request Consultation</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5">
            <h4 class="text-muted">No doctors found matching your criteria.</h4>
            <p>Try searching for another specialty like "Pediatrics" or "Cardiology".</p>
            <a href="search.php" class="btn btn-primary mt-2">View All Doctors</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>