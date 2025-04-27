<?php
include 'includes/header.php';
require_once 'config/database.php';

$filieres = getAllFilieres();

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: etudiant_profile.php");
    }
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_complet = trim($_POST['nom_complet']);
    $email = trim($_POST['email']);
    $date_naissance = $_POST['date_naissance'];
    $filiere = trim($_POST['filiere']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($nom_complet) || empty($email) || empty($date_naissance) || empty($filiere) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            $conn = getConnection();

            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                $error = "This email is already in use.";
            } else {
                // Insert into users table
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'etudiant', NOW())");
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt->execute([$nom_complet, $email, $hashed_password]);

                $user_id = $conn->lastInsertId();

                // Insert into etudiants table
                $stmt = $conn->prepare("INSERT INTO etudiants (user_id, nom_complet, date_naissance, id_filiere, is_validated, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
                $stmt->execute([$user_id, $nom_complet, $date_naissance, $filiere]);

                $success = "Registration successful! Your account is pending validation by the administrator.";
            }
        } catch (PDOException $e) {
            $error = "Registration error: " . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h2 class="text-center">Student Registration</h2>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                    <div class="text-center mt-3">
                        <a href="login.php" class="btn btn-primary">Login</a>
                    </div>
                <?php else: ?>
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="nom_complet" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="nom_complet" name="nom_complet" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="date_naissance" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="date_naissance" name="date_naissance" required>
                        </div>
                        <div class="mb-3">
                            <label for="filiere" class="form-label">Field of Study</label>
                            <select class="form-select" id="filiere" name="filiere" required>
                                <option value="">Select a field of study</option>
                                <?php foreach ($filieres as $filiere): ?>
                                    <option value="<?php echo $filiere['id_filiere']; ?>"><?php echo htmlspecialchars($filiere['filiere']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Register</button>
                        </div>
                    </form>
                    <div class="mt-3 text-center">
                        <p>Already registered? <a href="login.php">Login</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
?>
