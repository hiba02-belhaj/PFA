<?php
// process_donation.php
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['prescription_id'], $_POST['amount'])) {
    header("Location: donate.php");
    exit;
}

$prescription_id = (int) $_POST['prescription_id'];
$amount          = (float) $_POST['amount'];
$donor_name      = isset($_SESSION['full_name']) ? mysqli_real_escape_string($conn, $_SESSION['full_name']) : 'Anonymous';

if ($amount <= 0) {
    header("Location: donate.php?error=invalid_amount");
    exit;
}

// Verify prescription exists and is still open
$res = mysqli_query($conn, "SELECT id, amount_needed, amount_raised FROM prescriptions WHERE id = $prescription_id");
$rx  = mysqli_fetch_assoc($res);

if (!$rx) {
    header("Location: donate.php?error=not_found");
    exit;
}

// Cap donation so we don't overfund
$remaining = $rx['amount_needed'] - $rx['amount_raised'];
$actual    = min($amount, $remaining);
$actual    = round($actual, 2);

// Insert donation record
mysqli_query($conn,
    "INSERT INTO donations (prescription_id, donor_name, amount)
     VALUES ($prescription_id, '$donor_name', $actual)"
);

// Update raised amount on prescription
mysqli_query($conn,
    "UPDATE prescriptions
     SET amount_raised = amount_raised + $actual
     WHERE id = $prescription_id"
);

header("Location: donate.php?success=1");
exit;
?>