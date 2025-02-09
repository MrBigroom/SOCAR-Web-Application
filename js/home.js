let map;
let markers = [];
let availabilityCheckInterval;
let userLocation = null;
let userMarker = null;

const locations = {
    "Ipoh Parade": { lat: 4.5957, lng: 101.0899 },
    "Sultan Azlan Shah Airport": { lat: 4.5700, lng: 101.0986 },
    "Aeon Kinta City": { lat: 4.6139, lng: 101.1180 },
    "Aeon Station 18": { lat: 4.5458, lng: 101.0706 },
    "Terminal Amanjaya": { lat: 4.6697, lng: 101.0734 }
    };

const vehicles = [
    {
        id: 1,
        model: "Perodua Axia",
        type: "Compact",
        price: 9,
        location: "Ipoh Parade",
        image: "src/Perodua Axia.png"
    },
    {
        id: 2,
        model: "Honda City",
        type: "Sedan",
        price: 15,
        location: "Sultan Azlan Shah Airport",
        image: "src/Honda City.png"
    },
    {
        id: 3,
        model: "Toyota Vellfire",
        type: "MPV",
        price: 28,
        location: "Aeon Kinta City",
        image: "src/Toyota Vellfire.png"
    },
    {
        id: 4,
        model: "Proton Saga",
        type: "Sedan",
        price: 12,
        location: "Aeon Station 18",
        image: "src/Proton Saga.png"
    },
    {
        id: 5,
        model: "Mazda CX-5",
        type: "SUV",
        price: 25,
        location: "Terminal Amanjaya",
        image: "src/Mazda CX-5.png"
    }
];

document.addEventListener('DOMContentLoaded', () => {
    renderVehicles();
    setupEventListeners();
});

async function loadVehicles() {
    try {
        const response = await fetch('api/get_vehicles.php');
        const vehicles = await response.json();
        renderVehicles(vehicles);
    } catch(error) {
        console.error('Error loading vehicles.', error);
    }
}

async function loadWeatherData(locationId) {
    try {
        const response = await fetch(`api/get_weather.php?location_id=${locationId}`);
        const weatherData = await response.json();
        return weatherData;
    } catch(error) {
        console.error('Error loading weather data.', error);
        return null;
    }
}

async function initializePayment(bookingId) {
    try {
        const response = await fetch('api/create_payment_intent.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ booking_id: bookingId })
        });
        const data = await response.json();

        const stripe = Stripe('');
        const elements = stripe.elements();

        const card = elements.create('card');
        card.mount('#card-element');

        card.addEventListener('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });
        return { stripe, clientSecret: data.clientSecret };
    } catch(error) {
        console.error('Error initializing payment.', error);
        throw error;
    }
}

function startAvailabilityCheck() {
    checkVehicleAvailability();
    availabilityCheckInterval = setInterval(checkVehicleAvailability, 30000);
}

async function checkVehicleAvailability() {
    try {
        const response = await fetch('../api/get_vehicle_status.php');
        if(!response.ok) {
            throw new Error('Failed to fetch vehicle status');
        }

        const data = await response.json();
        updateVehicleAvailability(data.available_vehicles);
    } catch(error) {
        console.error('Error checking vehicle availability.', error);
    }
}

function updateVehicleAvailability(availableVehicles) {
    const availableIds = availableVehicles.map(v => v.vehicle_id);
    const vehicleCards = document.querySelectorAll('.vehicle-card');
    vehicleCards.forEach(card => {
        const vehicleId = card.dataset.vehicleId;
        const availabilityBadge = card.querySelector('.availability-badge');
        const bookButton = card.querySelector('button');

        if(availableIds.includes(parseInt(vehicleId))) {
            availabilityBadge.textContent = 'Available';
            availabilityBadge.className = 'availability-badge available';
            bookButton.disabled = false;
        } else {
            availabilityBadge.textContent = 'Currently Booked';
            availabilityBadge.className = 'availability-badge booked';
            bookButton.disabled = true;
        }
    });

    markers.forEach(marker => {
        const vehicleId = marker.vehicleId;
        if (availableIds.includes(vehicle_id)) {
            marker.marker.setIcon({
                path: google.maps.SymbolPath.CIRCLE,
                scale: 8,
                fillColor: "#2ecc71",
                fillOpacity: 0.8,
                strokeWeight: 2,
                strokeColor: "#FFFFFF"
            });
        } else {
            marker.marker.setIcon({
                path: google.maps.SymbolPath.CIRCLE,
                scale: 8,
                fillColor: "#e74c3c",
                fillOpacity: 0.8,
                strokeWeight: 2,
                strokeColor: "#FFFFFF"
            });
        }
    });
}

function renderVehicles() {
    const container = document.getElementById('vehicle-container');
    Promise.all(vehicles.map(async vehicle => {
        const weatherData = await loadWeatherData(vehicle.location_id);
        return `
        <div class="vehicle-card"
            <h3>${vehicle.model}</h3>
            <img src="${vehicle.image}"
                alt="${vehicle.model}"
                class="vehicle-image">
            <p>RM${vehicle.price}/hour</p>
            <p>Location: ${vehicle.location}</p>
            <div class="availability-badge available">Available</div>
            ${weatherData ? `
                <div class="weather-info">
                    <p>Temperature: ${weatherData.temperature}°C</p>
                    <p>Weather: ${weatherData.weather_condition}</p>
                </div>
                `: ''}
            <button onclick="showBookingForm(${vehicle.id})">Book Now</button>
        </div>
        `;
    })).then(vehicleCards => {
        container.innerHTML = vehicleCards.join('');
        checkVehicleAvailability();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    renderVehicles();
    setupEventListeners();
    setupFilterListeners();
    startAvailabilityCheck();
});

function highlightMarker(vehicleId) {
    const marker = markers.find(marker => marker.vehicleId === vehicleId);
    marker.marker.setAnimation(google.maps.Animation.BOUNCE);
    if(marker) {
        marker.marker.setIcon({
            url: 'https://maps.google.com/mapfiles/kml/shapes/marker_red.png',
            scaledSize: new google.maps.Size(40, 40)
        });
    }
}

function unhighlightMarker(vehicleId) {
    markers.forEach(marker => {
        marker.marker.setAnimation(null);
        marker.marker.setIcon({
            url: 'https://maps.google.com/mapfiles/kml/shapes/marker.png',
            scaledSize: new google.maps.Size(40, 40)
        });
    })
}

function centerMapOnVehicle(vehicleId) {
    const vehicle = vehicles.find(vehicle => vehicle.id === vehicleId);
    const location = locations[vehicle.location];
    if(position) {
        map.setCenter(location);
        map.setZoom(16);
    }
}

function showBookingForm(vehicleId) {
    const vehicle = vehicles.find(v => v.id === vehicleId);
    document.getElementById('selectedVehicle').textContent = vehicle.model;
    document.getElementById('bookingForm').style.display = 'block';
}

function closeForm() {
    document.getElementById('bookingForm').style.display = 'none';
    document.getElementById('bookingDetails').reset();
}

function setupFilterListeners() {
    document.getElementById('locationSearch').addEventListener('input', filterVehicles);
    document.getElementById('vehicleType').addEventListener('change', filterVehicles);
    document.getElementById('priceSort').addEventListener('change', filterVehicles);
}

function filterVehicles() {
    const searchPrompt = document.getElementById('locationSearch').value.toLowerCase();
    const selectedType = document.getElementById('vehicleType').value;
    const sortOrder = document.getElementById('priceSort').value;

    let filteredVehicles = vehicles.filter(vehicle => {
        const matchSearch = vehicle.location.toLowerCase().includes(searchPrompt);
        const matchType = !selectedType || vehicle.type === selectedType;
        return matchSearch && matchType;
    });

    if(sortOrder) {
        filteredVehicles.sort((a, b) => {
            if(sortOrder === 'low') {
                return a.price - b.price;
            } else {
                return b.price - a.price;
            }
        });
    }

    markers.forEach(marker => {
        const vehicle = vehicles.find(v => v.id === marker.vehicleId);
        const isVisible = filteredVehicles.some(v => v.id === marker.vehicleId);
        marker.marker.setVisible(isVisible);

        if(isVisible && searchPrompt && vehicle.location.toLowerCase().includes(searchPrompt)) {
            const position = locations[vehicle.location];
            if(position) {
                marker.setPosition(position);
                marker.setZoom(14);
            }
        }
    });
    renderFilteredVehicles(filteredVehicles);
}

function renderFilteredVehicles(filteredVehicles) {
    const container = document.getElementById('vehicle-container');

    if(filteredVehicles.length === 0) {
        container.innerHTML = `
            <div class="no-results">
                <i class="fas fa-search"></i>
                <p>No vehicles found matching your search.</p>
            </div>
        `;
        return;
    }

    Promise.all(filteredVehicles.map(async vehicle => {
        const weatherData = await loadWeatherData(vehicle.location_id);
        return `
            <div class="vehicle-card" data-vehicle-id="${vehicle.id}">
                <h3>${vehicle.model}</h3>
                <img src="${vehicle.image}"
                    alt="${vehicle.model}"
                    class="vehicle-image">
                <p>RM${vehicle.price}/hour</p>
                <p>Location: ${vehicle.location}</p>
                <div class="vehicle-type">${vehicle.type}</div>
                ${weatherData ? `
                    <div class="weather-info">
                        <p>Temperature: ${weatherData.temperature}°C</p>
                        <p>Weather: ${weatherData.weather_condition}</p>
                    </div>
                `: ''}
                <button onclick="showBookingForm(${vehicle.id})">Book Now</button>
            </div>
        `;
    })).then(vehicleCards => {
        container.innerHTML = vehicleCards.join('');
    });
}

function setupEventListeners() {
    document.getElementById('bookingDetails').addEventListener('submit', handleSubmit);
    document.getElementById('contactNumber').addEventListener('input', validateContactNumber);
    document.getElementById('icNumber').addEventListener('input', validateIC);
    document.getElementById('startTime').addEventListener('change', validateTimes);
    document.getElementById('endTime').addEventListener('change', validateTimes);
}

function validateName() {
    const name = document.getElementById('name').value;
    const errorField = document.getElementById('nameError');
    const nameRegex = /^[A-Za-z ]+$/;

    if(!name.trim()) {
        errorField.textContent = 'Please enter your name';
        return false;
    }
    if(!nameRegex.test(name)) {
        errorField.textContent = 'Name should only contain letters and spaces';
        return false;
    }
    errorField.textContent = '';
    return true;
}

function validateContactNumber() {
    const contactNumber = document.getElementById('contactNumber').value;
    const errorField = document.getElementById('contactNumberError');
    const contactNumberRegex = /^(\+?6?01)[0-46-9]-*[0-9]{7,8}$/;

    if(!contactNumber.trim()) {
        errorField.textContent = 'Please enter your contact number';
        return false;
    }
    if(!contactNumberRegex.test(contactNumber)) {
        errorField.textContent = 'Invalid contact number format (e.g., 012-34567890';
        return false;
    }
    errorField.textContent = '';
    return true;
}

function validateIC(e) {
    const icRegex = /^\d{6}-\d{2}-\d{4}$/;
    const errorField = document.getElementById('icError');
            
    if (!icRegex.test(e.target.value)) {
        errorField.textContent = 'Invalid IC format (YYYYMM-DD-XXXX)';
        return false;
    }
    errorField.textContent = '';
    return true;
}

function validateTimes() {
    const start = document.getElementById('startTime').value;
    const end = document.getElementById('endTime').value;
    const errorField = document.getElementById('endTimeError');

    if(start && end && new Date(start) >= new Date(end)) {
        errorField.textContent = 'End time must be after start time';
        return false;
    }
    errorField.textContent = '';
    return true;
}

async function handleSubmit(e) {
    e.preventDefault();
            
    if (!validateName() || !validateIC({ target: document.getElementById('icNumber') }) || !validateTimes() || !validateContactNumber()) {
        return;
    }

    const formData = {
        vehicleId: vehicles.find(v => v.model === document.getElementById('selectedVehicle').textContent).id,
        startTime: document.getElementById('startTime').value,
        endTime: document.getElementById('endTime').value,
        name: document.getElementById('name').value,
        contactNumber: document.getElementById('contactNumber').value,
        icNumber: document.getElementById('icNumber').value
    };

    try {
        const bookingResponse = await fetch('../api/create_booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        if(!bookingResponse.ok) {
            throw new Error('Failed to create booking');
        }

        const bookingData = await bookingResponse.json();
        closeForm();
        window.location.href = `../public/payment.php?booking_id=${bookingData.bookingId}`;
    } catch (error) {
        console.error('Booking/payment failed: ', error);
        alert('Booking failed: ' + error.message);
    }
}

function initMap() {
    map = new google.maps.Map(document.getElementById('map'), {
        center: { lat: 4.6005, lng: 101.0758 },
        zoom: 12,
        mapTypeControl: true,
        streetViewControl: true,
        fullscreenControl: true,
        mapTypeControlOptions: {
            style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
            position: google.maps.ControlPosition.TOP_RIGHT
        }
    });

    addCustomControls();
    createVehicleMarkers();
    initLocationTracking()
}

function addCustomControls() {
    const locationButton = document.createElement('button');
    locationButton.innerHTML = '<i class="fas fa-location-arrow"></i> Find Nearest Cars';
    locationButton.className = 'custom-map-control-button';
    map.controls[google.maps.ControlPosition.LEFT_BOTTOM].push(locationButton);

    const refreshButton = document.createElement('button');
    refreshButton.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Map';
    refreshButton.className = 'custom-map-control-button';
    map.controls[google.maps.ControlPosition.LEFT_BOTTOM].push(refreshButton);

    locationButton.addEventListener('click', findNearestVehicles);
    refreshButton.addEventListener('click', refreshMapData);
}

function initLocationTracking() {
    if(navigator.geolocation) {
        navigator.geolocation.watchPosition(
            position => {
                userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                updateUserMarker();
                updateDistance();
            },
            () => {
                console.log('Unable to retrieve your location');
            }
        );
    }
}

function updateUserMarker() {
    if(!userLocation) {
        return;
    }

    if(!userMarker) {
        userMarker = new google.maps.Marker({
            map: map,
            position: userLocation,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 10,
                fillColor: '#4285F4',
                fillOpacity: 1,
                strokeColor: '#FFFFFF',
                strokeWeight: 2
            },
            title: "Your Location"
        });
    } else {
        userMarker.setPosition(userLocation);
    }
}

function calculateDistance(lat1, lng1, lat2, lng2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLng/2) * Math.sin(dLng/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

function updateDistance() {
    if(!userLocation) {
        return;
    }

    markers.forEach(marker => {
        const vehicle = vehicles.find(v => v.id === marker.vehicleId);
        const vehicleLocation = locations[vehicle.location];
        const distance = calculateDistance(userLocation.lat, userLocation.lng, vehicleLocation.lat, vehicleLocation.lng);
        
        const infoContent = marker.marker.getMap() ?
            marker.marker.getMap().get('infoWindow') : null;
        if (infoContent) {
            infoContent.setContent(createInfoWindowContent(vehicle, distance));
        }

        const card = document.querySelector(`[data-vehicle-id="${vehicle.id}"]`);
        if(card) {
            const distanceElement = card.querySelector('.distance-info');
            if(distanceElement) {
                distanceElement.textContent = `Distance: ${distance.toFixed(2)} km away`;
            }
        }
    });
}

function findNearestVehicles() {
    if(!userLocation) {
        alert('Please enable location service to find nearest vehicles');
        return;
    }

    const vehiclesWithDistance = vehicles.map(vehicle => {
        const vehicleLocation = locations[vehicle.location];
        const distance = calculateDistance(userLocation.lat, userLocation.lng, vehicleLocation.lat, vehicleLocation.lng);
        return { ...vehicle, distance };
    }).sort((a, b) => a.distance - b.distance);

    const nearestVehicles = vehiclesWithDistance.slice(0, 3);
    const bounds = new google.maps.LatLngBounds();
    bounds.extend(new google.maps.LatLng(userLocation.lat, userLocation.lng));

    nearestVehicles.forEach(vehicle => {
        const location = locations[vehicle.location];
        bounds.extend(new google.maps.LatLng(location.lat, location.lng));

        const marker = markers.find(m => m.vehicleId === vehicle.id);
        if(marker) {
            marker.marker.setAnimation(google.maps.Animation.BOUNCE);
            setTimeout(() => {
                marker.marker.setAnimation(null);
            }, 2100);
        }
    });
    map.fitBounds(bounds);
}

async function refreshMapData() {
    const refreshButton = document.querySelector('.custom-map-control=button');
    refreshButton.classList.add('fa-spin');

    try {
        await checkVehicleAvailability();
        updateDistance();
    } finally {
        setTimeout(() => {
            refreshButton.classList.remove('fa-spin');
        }, 1000);
    }
}

window.addEventListener('beforeunload', () => {
    if(availabilityCheckInterval) {
        clearInterval(availabilityCheckInterval);
    }
});