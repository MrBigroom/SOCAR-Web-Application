<!DOCTYPE html>
<html>
<head>
    <title>Login - Car Sharing</title>
    <link rel="stylesheet" href="/SOCAR Web Application/css/public.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="form-container">
        <h2>Login</h2>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <form action="../auth/login.php" method="POST">
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="register.html">Register here</a></p>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>