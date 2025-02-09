<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOCAR Web Application</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="/SOCAR Web Application/css/home.css">
    <script src="js/home.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBc1agMwdDe89vX6yIxsShmqekdjhmOPe4&callback=initMap" async defer></script>
</head>
<body>
    <header>
        <div id="title-logo-container">
            <img src="src/SOCAR logo.png" style="width: 100px;">
            <h1>Car Sharing Service</h1>
        </div>
        <nav>
            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="nav-buttons">
                    <a href="./public/profile.php" id="profile-link">
                        <i class="fas fa-user-circle"></i> View Profile
                    </a>
                    <a href="./public/logout.php" id="logout-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            <?php else: ?>
                <a href="./public/login.php" id="login-link">
                    <i class="fas fa-sign-in-alt"></i> Login/Register
                </a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="search-filter-container">
        <div class="search-box">
            <input type="text" id="loactionSearch" placeholder="Search by location">
            <i class="fas fa-search"></i>
        </div>
    </div>

    <div class="filter-options">
        <select id="vehicleType">
            <option value="all">All Vehicle Types</option>
            <option value="SUV">SUV</option>
            <option value="Sedan">Sedan</option>
            <option value="Compact">Compact</option>
            <option value="MPV">MPV</option>
        </select>

        <select id="priceSort">
            <option value="low">Low to High</option>
            <option value="high">High to Low</option>
        </select>
    </div>

    <div id="map"></div>

    <div class="vehicle-grid" id="vehicle-container">
        <!-- Vehicles will be loaded dynamically -->
    </div>

    <div class="booking-form" id="bookingForm">
        <h2>Book Vehicle</h2>
        <form id="bookingDetails">
            <div class="form-group">
                <label>Vehicle: <span id="selectedVehicle"></span></label>
            </div>
            <div class="form-group">
                <label>Start Time:
                    <input type="datetime-local" id="startTime" required>
                </label>
                <div class="error-message" id="startTimeError"></div>
            </div>

            <div class="form-group">
                <label>End Time:
                    <input type="datetime-local" id="endTime" required>
                </label>
                <div class="error-message" id="endTimeError"></div>
            </div>
            <div class="form-group">
                <label>Name:
                    <input type="text" id="name" required>
                </label>
                <div class="error-message" id="icError"></div>
            </div>
            <div>
                <label>Contact Number:
                    <input type="tel" id="contactNumber" placeholder="01X-XXXXXXXX" required>
                </label>
                <div class="error-message" id="contactNumberError"></div>
            </div>
            <div class="form-group">
                <label>IC Number:
                    <input type="text" id="icNumber" pattern="\d{6}-\d{2}-\d{4}" required>
                </label>
                <div class="error-message" id="icError"></div>
            </div>
            <div class="form-group">
                <button type="submit">Confirm Booking</button>
                <button type="button" onclick="closeForm()">Cancel</button>
            </div>
        </form>
    </div>
</body>
</html>