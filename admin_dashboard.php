<?php
include 'includes/header.php';
requireAdmin(); // Ensure only admins can access this page

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'config/database.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'pending';
$success = '';
$error = '';

// Handle student validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['validate'])) {
        try {
            $conn = getConnection();
            $stmt = $conn->prepare("UPDATE etudiants SET is_validated = 1 WHERE id = ?");
            $stmt->execute([$_POST['student_id']]);
            $success = "The student has been successfully validated.";
        } catch (PDOException $e) {
            $error = "Error during validation: " . $e->getMessage();
        }
    } elseif (isset($_POST['reject']) || isset($_POST['delete'])) {
        try {
            $conn = getConnection();

            // Get user_id from etudiants table
            $stmt = $conn->prepare("SELECT user_id FROM etudiants WHERE id = ?");
            $stmt->execute([$_POST['student_id']]);
            $student = $stmt->fetch();

            if ($student) {
                // Delete from etudiants table
                $stmt = $conn->prepare("DELETE FROM etudiants WHERE id = ?");
                $stmt->execute([$_POST['student_id']]);

                // Delete from users table
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$student['user_id']]);

                $success = isset($_POST['reject']) 
                    ? "The registration has been rejected and deleted." 
                    : "The student has been successfully deleted.";
            } else {
                $error = "Student not found.";
            }
        } catch (PDOException $e) {
            $error = "Error during the operation: " . $e->getMessage();
        }
    }
}

// Get students based on action
try {
    $conn = getConnection();
    
    if ($action === 'pending') {
        $stmt = $conn->prepare("
    SELECT e.*, u.email, f.filiere 
    FROM etudiants e 
    JOIN users u ON e.user_id = u.id 
    JOIN filiere f ON e.id_filiere = f.id_filiere
    WHERE e.is_validated = 0
    ORDER BY e.created_at DESC
");
    } else {
        $stmt = $conn->prepare("
    SELECT e.*, u.email, f.filiere 
    FROM etudiants e 
    JOIN users u ON e.user_id = u.id 
    JOIN filiere f ON e.id_filiere = f.id_filiere
    WHERE e.is_validated = 1
    ORDER BY e.nom_complet
");
    }
    
    $stmt->execute();
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error retrieving data: " . $e->getMessage();
    $students = [];
}
?>

<h2 class="mb-4">Admin Dashboard</h2>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?php echo $action === 'pending' ? 'active' : ''; ?>" href="?action=pending">Pending Registrations</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $action === 'validated' ? 'active' : ''; ?>" href="?action=validated">Validated Students</a>
    </li>
</ul>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (empty($students)): ?>
    <div class="alert alert-info">
        <?php echo $action === 'pending' ? 'No pending registrations.' : 'No validated students.'; ?>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Date of Birth</th>
                    <th>Field of Study</th>
                    <th>Registration Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo $student['id']; ?></td>
                        <td><?php echo htmlspecialchars($student['nom_complet']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo $student['date_naissance']; ?></td>
                        <td><?php echo htmlspecialchars($student['filiere']); ?></td>
                        <td><?php echo $student['created_at']; ?></td>
                        <td>
                            <?php if ($action === 'pending'): ?>
                                <form method="post" action="" class="d-inline">
                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                    <button type="submit" name="validate" class="btn btn-success btn-sm">Validate</button>
                                </form>
                                <form method="post" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to reject this registration?');">
                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                    <button type="submit" name="reject" class="btn btn-danger btn-sm">Reject</button>
                                </form>
                            <?php else: ?>
                                <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                <form method="post" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this student?');">
                                    <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                    <button type="submit" name="delete" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
include 'includes/footer.php';
?>
