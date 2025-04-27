<?php
include 'includes/header.php';

requireLogin();

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'config/database.php';

// Add this after requiring database.php:
$filieres = getAllFilieres();

$error = '';
$success = '';
$student = null;

try {
    $conn = getConnection();
    
    // Get student data
    $stmt = $conn->prepare("
        SELECT e.*, u.email, f.filiere, e.id_filiere
        FROM etudiants e 
        JOIN users u ON e.user_id = u.id 
        JOIN filiere f ON e.id_filiere = f.id_filiere
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $student = $stmt->fetch();
    
    if (!$student) {
        header("Location: logout.php");
        exit();
    }
} catch (PDOException $e) {
    $error = "Error retrieving data: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_complet = trim($_POST['nom_complet']);
    $email = trim($_POST['email']);
    $date_naissance = $_POST['date_naissance'];
    $filiere = trim($_POST['filiere']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($nom_complet) || empty($email) || empty($date_naissance) || empty($filiere)) {
        $error = "The fields Name, Email, Date of Birth, and Field of Study are required.";
    } else {
        try {
            $conn = getConnection();
            
            // Check if email already exists for other users
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            
            if ($stmt->rowCount() > 0) {
                $error = "This email is already used by another user.";
            } else {
                // Check if password change is requested
                if (!empty($current_password)) {
                    // Verify current password
                    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();
                    
                    if (!password_verify($current_password, $user['password'])) {
                        $error = "The current password is incorrect.";
                    } elseif (empty($new_password) || empty($confirm_password)) {
                        $error = "Please enter and confirm the new password.";
                    } elseif ($new_password !== $confirm_password) {
                        $error = "The new passwords do not match.";
                    } else {
                        // Update password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                        $stmt->execute([$nom_complet, $email, $hashed_password, $_SESSION['user_id']]);
                    }
                } else {
                    // Update without changing password
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                    $stmt->execute([$nom_complet, $email, $_SESSION['user_id']]);
                }
                
                if (empty($error)) {
                    // Update etudiants table
                    $stmt = $conn->prepare("UPDATE etudiants SET nom_complet = ?, date_naissance = ?, id_filiere = ? WHERE user_id = ?");
                    $stmt->execute([$nom_complet, $date_naissance, $filiere, $_SESSION['user_id']]);
                    
                    // Update session
                    $_SESSION['name'] = $nom_complet;
                    $_SESSION['email'] = $email;
                    
                    $success = "Your information has been successfully updated.";
                    
                    // Refresh student data
                    $stmt = $conn->prepare("
                        SELECT e.*, u.email, f.filiere, e.id_filiere
                        FROM etudiants e 
                        JOIN users u ON e.user_id = u.id 
                        JOIN filiere f ON e.id_filiere = f.id_filiere
                        WHERE u.id = ?
                    ");
                    $stmt->execute([$_SESSION['user_id']]);
                    $student = $stmt->fetch();
                }
            }
        } catch (PDOException $e) {
            $error = "Error updating data: " . $e->getMessage();
        }
    }
}
?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Student Profile</h3>
            </div>
            <div class="card-body">
                <?php if ($student): ?>
                    <div class="mb-3">
                        <h5>Status</h5>
                        <?php if ($student['is_validated']): ?>
                            <span class="badge bg-success">Validated</span>
                        <?php else: ?>
                            <span class="badge bg-warning">Pending Validation</span>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <h5>Full Name</h5>
                        <p><?php echo htmlspecialchars($student['nom_complet']); ?></p>
                    </div>
                    <div class="mb-3">
                        <h5>Email</h5>
                        <p><?php echo htmlspecialchars($student['email']); ?></p>
                    </div>
                    <div class="mb-3">
                        <h5>Date of Birth</h5>
                        <p><?php echo $student['date_naissance']; ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <h5>Field of Study</h5>
                        <p><?php echo htmlspecialchars($student['filiere']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <h5>Registration Date</h5>
                        <p><?php echo $student['created_at']; ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3>Edit My Information</h3>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($student): ?>
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="nom_complet" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="nom_complet" name="nom_complet" value="<?php echo htmlspecialchars($student['nom_complet']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="date_naissance" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="date_naissance" name="date_naissance" value="<?php echo $student['date_naissance']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="filiere" class="form-label">Field of Study</label>
                            <select class="form-select" id="filiere" name="filiere" required>
                                <option value="">Select a field of study</option>
                                <?php foreach ($filieres as $f): ?>
                                    <option value="<?php echo $f['id_filiere']; ?>" <?php echo $student['id_filiere'] == $f['id_filiere'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($f['filiere']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <h4 class="mt-4 mb-3">Change Password</h4>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                            <div class="form-text">Leave blank if you do not want to change your password.</div>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
?>
