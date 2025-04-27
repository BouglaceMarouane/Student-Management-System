<?php
include 'includes/header.php';
?>

<div>
    <h1 class="display-4">Welcome to the Student Management System</h1>
    <p class="lead">This application allows you to manage student registrations.</p>
    <hr class="my-4">
    
    <?php if (!isLoggedIn()): ?>
        <p>Please log in or register to access the application.</p>
        <a class="btn btn-primary btn-lg" href="login.php" role="button">Login</a>
        <a class="btn btn-secondary btn-lg" href="register.php" role="button">Register</a>
    <?php elseif (isAdmin()): ?>
        <p>Access the admin dashboard to manage registrations.</p>
        <a class="btn btn-primary btn-lg" href="admin_dashboard.php" role="button">Dashboard</a>
    <?php elseif (isStudent()): ?>
        <p>Access your student profile to view your information.</p>
        <a class="btn btn-primary btn-lg" href="etudiant_profile.php" role="button">My Profile</a>
    <?php endif; ?>
</div>

<?php
include 'includes/footer.php';
?>
