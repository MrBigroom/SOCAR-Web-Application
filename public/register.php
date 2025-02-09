<!DOCTYPE html>
<html>
<head>
    <title>Register - SOCAR</title>
    <link rel="stylesheet" href="../css/public.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="form-container">
        <h2>Create Account</h2>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <form action="../auth/register.php" method="POST">
            <div class="form-group">
                <label>Full Name:</label>
                <input type="text" id="name" name="full_name" required>
            </div>
            <div class="form-group">
                <label>Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Contact Number:</label>
                <input type="tel" id="contactNumber" name="contact_number"
                        placeholder="01X-XXXXXXXX" required>
            </div>
            <div class="form-group">
                <label>Password (min 8 characters):</label>
                <input type="password" id="password" name="password" minlength="8" required>
            </div>
            <div class="form-group">
                <label>IC Number:</label>
                <input type="text" name="ic_number" id="icNumber" pattern="\d{6}-\d{2}-\d{4}" 
                       placeholder="XXXXXX-XX-XXXX" required>
            </div>
            <div class="form-group">
                <label>Driver License Number:</label>
                <input type="text" name="driver_license" id="driverLicense"
                       placeholder="S12345678" required>
            </div>
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="login.html">Login here</a></p>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>