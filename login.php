<?php
include 'includes/header.php';
require_once 'config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: etudiant_profile.php");
    }
    exit();
}

$email = '';
$error = '';

// Check for remember_email cookie
if (isset($_COOKIE['remember_email'])) {
    $email = $_COOKIE['remember_email'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    if ($remember) {
        setcookie('remember_email', $email, time() + (7 * 24 * 60 * 60), '/');
    } else {
        setcookie('remember_email', '', time() - 3600, '/'); // Delete cookie
    }
    
    try {
        $conn = getConnection();
        
        // Get user by email
        $stmt = $conn->prepare("
            SELECT u.*, e.is_validated, e.id as student_id 
            FROM users u 
            LEFT JOIN etudiants e ON u.id = e.user_id 
            WHERE u.email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = "User not found with this email.";
        } 
        // Check if password is stored as plain text (temporary fix)
        elseif ($user['password'] === $password) {
            // Plain text password match - update to hashed version
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->execute([$hashed_password, $user['id']]);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: etudiant_profile.php");
            }
            exit();
        }
        elseif (!password_verify($password, $user['password'])) {
            $error = "Incorrect password.";
        } elseif ($user['role'] === 'etudiant' && !$user['is_validated']) {
            $error = "Your account is pending validation by the administrator.";
        } else {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: etudiant_profile.php");
            }
            exit();
        }
    } catch (PDOException $e) {
        $error = "Connection error: " . $e->getMessage();
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h2 class="text-center">Login</h2>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember" <?php echo $email ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
                <div class="mt-3 text-center">
                    <p>Not registered yet? <a href="register.php">Create an account</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
?>
