<?php
include 'includes/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$message = '';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $del_id = (int) $_POST['delete_user_id'];
    if ($del_id !== (int) $_SESSION['user_id']) {
        mysqli_query($conn, "DELETE FROM users WHERE id = $del_id");
        $message = "<div class='alert alert-warning'>User successfully deleted.</div>";
    } else {
        $message = "<div class='alert alert-danger'>You cannot delete your own admin account.</div>";
    }
}

// Role filter
$filter_role = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : '';
$where = $filter_role ? "WHERE role = '$filter_role'" : '';

$users = mysqli_query($conn,
    "SELECT id, full_name, email, role, created_at FROM users $where ORDER BY created_at DESC"
);

include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="list-group shadow-sm">
            <a href="admin_dashboard.php" class="list-group-item list-group-item-action">Dashboard Overview</a>
            <a href="admin_validations.php" class="list-group-item list-group-item-action">Pending Validations</a>
            <a href="admin_users.php" class="list-group-item list-group-item-action active">Manage Users</a>
            <a href="admin_stats.php" class="list-group-item list-group-item-action">Platform Statistics</a>
            <a href="logout.php" class="list-group-item list-group-item-action text-danger fw-bold">Logout</a>
        </div>
    </div>

    <div class="col-md-9">
        <?php echo $message; ?>

        <!-- Filter bar -->
        <div class="d-flex gap-2 mb-3">
            <a href="admin_users.php" class="btn btn-sm <?php echo !$filter_role ? 'btn-primary' : 'btn-outline-secondary'; ?>">All</a>
            <a href="admin_users.php?role=admin"   class="btn btn-sm <?php echo $filter_role === 'admin'   ? 'btn-primary' : 'btn-outline-secondary'; ?>">Admins</a>
            <a href="admin_users.php?role=doctor"  class="btn btn-sm <?php echo $filter_role === 'doctor'  ? 'btn-primary' : 'btn-outline-secondary'; ?>">Doctors</a>
            <a href="admin_users.php?role=patient" class="btn btn-sm <?php echo $filter_role === 'patient' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Patients</a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h4 class="fw-bold mb-4">All Registered Users</h4>
                <div class="table-responsive">
                    <table class="table table-hover align-middle border">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($users)): ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><span class="badge bg-primary text-uppercase"><?php echo $row['role']; ?></span></td>
                                <td class="text-muted small"><?php echo $row['created_at']; ?></td>
                                <td>
                                    <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        <input type="hidden" name="delete_user_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                    <?php else: ?>
                                        <span class="text-muted small">You</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>