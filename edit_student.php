<?php
include 'includes/header.php';
requireAdmin(); // Ensure only admins can access this page

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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_dashboard.php?action=validated");
    exit();
}

$student_id = $_GET['id'];

try {
    $conn = getConnection();
    
    $stmt = $conn->prepare("
        SELECT e.*, u.email, f.filiere 
        FROM etudiants e 
        JOIN users u ON e.user_id = u.id 
        JOIN filiere f ON e.id_filiere = f.id_filiere
        WHERE e.id = ? AND e.is_validated = 1
    ");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        header("Location: admin_dashboard.php?action=validated");
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
    
    // Validate input
    if (empty($nom_complet) || empty($email) || empty($date_naissance) || empty($filiere)) {
        $error = "All fields are required.";
    } else {
        try {
            $conn = getConnection();
            
            // Check if email already exists for other users
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $student['user_id']]);
            
            if ($stmt->rowCount() > 0) {
                $error = "This email is already used by another user.";
            } else {
                // Update users table
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                $stmt->execute([$nom_complet, $email, $student['user_id']]);
                
                // Update etudiants table
                $stmt = $conn->prepare("UPDATE etudiants SET nom_complet = ?, date_naissance = ?, id_filiere = ? WHERE id = ?");
                $stmt->execute([$nom_complet, $date_naissance, $filiere, $student_id]);
                
                $success = "The student's information has been successfully updated.";
                
                // Refresh student data
                $stmt = $conn->prepare("
                    SELECT e.*, u.email, f.filiere 
                    FROM etudiants e 
                    JOIN users u ON e.user_id = u.id 
                    JOIN filiere f ON e.id_filiere = f.id_filiere
                    WHERE e.id = ?
                ");
                $stmt->execute([$student_id]);
                $student = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $error = "Error updating data: " . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2>Edit Student</h2>
                <a href="admin_dashboard.php?action=validated" class="btn btn-secondary">Back</a>
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
