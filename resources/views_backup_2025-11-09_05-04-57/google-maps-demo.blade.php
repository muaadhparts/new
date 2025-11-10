<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>اختيار الموقع - Google Maps</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .content {
            padding: 30px;
        }

        .map-section {
            margin-bottom: 30px;
        }

        .map-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .map-header h2 {
            font-size: 18px;
            color: #333;
        }

        #map-container {
            position: relative;
            height: 500px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e0e0e0;
        }

        #map {
            width: 100%;
            height: 100%;
        }

        .map-search {
            position: absolute;
            top: 10px;
            right: 10px;
            left: 10px;
            z-index: 10;
        }

        #map-search-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #667eea;
            border-radius: 8px;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            background: white;
        }

        #map-search-input:focus {
            outline: none;
            border-color: #764ba2;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 20;
        }

        .loading-overlay.active {
            display: flex;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .buttons-container {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }

        .btn-secondary:hover {
            background: #d0d0d0;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .location-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .location-info h3 {
            font-size: 16px;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .info-item {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border-right: 3px solid #667eea;
        }

        .info-item label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .info-item .value {
            font-size: 14px;
            color: #333;
            word-break: break-word;
        }

        .coordinates {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }

        .coordinate-item {
            flex: 1;
            background: white;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }

        .coordinate-item label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .coordinate-item .value {
            font-size: 16px;
            font-weight: 600;
            color: #667eea;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>اختيار الموقع على الخريطة</h1>
            <p>انقر على الخريطة أو اسحب العلامة لتحديد موقعك، أو استخدم البحث للعثور على عنوان</p>
        </div>

        <div class="content">
            <div id="alert-container"></div>

            <div class="map-section">
                <div class="map-header">
                    <h2>الخريطة التفاعلية</h2>
                </div>

                <div id="map-container">
                    <div class="map-search">
                        <input type="text" id="map-search-input" placeholder="ابحث عن عنوان...">
                    </div>
                    <div id="map"></div>
                    <div class="loading-overlay" id="loading-overlay">
                        <div class="spinner"></div>
                    </div>
                </div>

                <div class="buttons-container">
                    <button class="btn btn-primary" id="use-location-btn" disabled>
                        استخدم هذا الموقع
                    </button>
                    <button class="btn btn-secondary" id="reset-btn">
                        إعادة تحديد
                    </button>
                    <button class="btn btn-secondary" id="current-location-btn">
                        موقعي الحالي
                    </button>
                </div>
            </div>

            <div class="location-info hidden" id="location-info">
                <h3>معلومات الموقع المحدد</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>الدولة (عربي)</label>
                        <div class="value" id="country-ar">-</div>
                    </div>
                    <div class="info-item">
                        <label>الدولة (إنجليزي)</label>
                        <div class="value" id="country-en">-</div>
                    </div>
                    <div class="info-item">
                        <label>المنطقة (عربي)</label>
                        <div class="value" id="state-ar">-</div>
                    </div>
                    <div class="info-item">
                        <label>المنطقة (إنجليزي)</label>
                        <div class="value" id="state-en">-</div>
                    </div>
                    <div class="info-item">
                        <label>المدينة (عربي)</label>
                        <div class="value" id="city-ar">-</div>
                    </div>
                    <div class="info-item">
                        <label>المدينة (إنجليزي)</label>
                        <div class="value" id="city-en">-</div>
                    </div>
                </div>

                <div class="coordinates">
                    <div class="coordinate-item">
                        <label>خط العرض</label>
                        <div class="value" id="latitude-value">-</div>
                    </div>
                    <div class="coordinate-item">
                        <label>خط الطول</label>
                        <div class="value" id="longitude-value">-</div>
                    </div>
                </div>

                <div class="info-grid" style="margin-top: 15px;">
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <label>العنوان الكامل</label>
                        <div class="value" id="full-address">-</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let map;
        let marker;
        let geocoder;
        let searchBox;
        let debounceTimer;
        let selectedLocation = null;

        const DEBOUNCE_DELAY = 400;
        const DEFAULT_CENTER = { lat: 24.7136, lng: 46.6753 }; // Riyadh, Saudi Arabia

        // Initialize map
        function initMap() {
            geocoder = new google.maps.Geocoder();

            map = new google.maps.Map(document.getElementById('map'), {
                center: DEFAULT_CENTER,
                zoom: 12,
                mapTypeControl: true,
                streetViewControl: false,
                fullscreenControl: true,
            });

            marker = new google.maps.Marker({
                map: map,
                draggable: true,
                animation: google.maps.Animation.DROP,
            });

            // Setup search box
            const searchInput = document.getElementById('map-search-input');
            searchBox = new google.maps.places.SearchBox(searchInput);

            // Bias search results to map viewport
            map.addListener('bounds_changed', () => {
                searchBox.setBounds(map.getBounds());
            });

            // Handle search selection
            searchBox.addListener('places_changed', () => {
                const places = searchBox.getPlaces();
                if (places.length === 0) return;

                const place = places[0];
                if (!place.geometry || !place.geometry.location) return;

                map.setCenter(place.geometry.location);
                marker.setPosition(place.geometry.location);
                marker.setVisible(true);

                handleLocationChange(place.geometry.location.lat(), place.geometry.location.lng());
            });

            // Map click event
            map.addListener('click', (event) => {
                marker.setPosition(event.latLng);
                marker.setVisible(true);
                handleLocationChange(event.latLng.lat(), event.latLng.lng());
            });

            // Marker drag event
            marker.addListener('dragend', () => {
                const position = marker.getPosition();
                handleLocationChange(position.lat(), position.lng());
            });

            // Button events
            document.getElementById('use-location-btn').addEventListener('click', useLocation);
            document.getElementById('reset-btn').addEventListener('click', resetSelection);
            document.getElementById('current-location-btn').addEventListener('click', getCurrentLocation);
        }

        // Handle location change with debouncing
        function handleLocationChange(lat, lng) {
            clearTimeout(debounceTimer);

            debounceTimer = setTimeout(() => {
                reverseGeocode(lat, lng);
            }, DEBOUNCE_DELAY);
        }

        // Reverse geocode coordinates
        async function reverseGeocode(lat, lng) {
            showLoading(true);

            try {
                const response = await fetch('/api/geocoding/reverse', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        latitude: lat,
                        longitude: lng
                    })
                });

                const result = await response.json();

                if (result.success) {
                    selectedLocation = result.data;
                    displayLocationInfo(result.data);
                    document.getElementById('use-location-btn').disabled = false;
                    showAlert('تم تحديد الموقع بنجاح', 'success');
                } else {
                    showAlert('فشل في الحصول على معلومات الموقع: ' + (result.error || 'خطأ غير معروف'), 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('حدث خطأ في الاتصال بالخادم', 'error');
            } finally {
                showLoading(false);
            }
        }

        // Display location information
        function displayLocationInfo(data) {
            document.getElementById('country-ar').textContent = data.country?.name_ar || '-';
            document.getElementById('country-en').textContent = data.country?.name || '-';
            document.getElementById('state-ar').textContent = data.state?.name_ar || '-';
            document.getElementById('state-en').textContent = data.state?.name || '-';
            document.getElementById('city-ar').textContent = data.city?.name_ar || '-';
            document.getElementById('city-en').textContent = data.city?.name || '-';
            document.getElementById('latitude-value').textContent = data.coordinates?.latitude.toFixed(6) || '-';
            document.getElementById('longitude-value').textContent = data.coordinates?.longitude.toFixed(6) || '-';
            document.getElementById('full-address').textContent = data.address?.ar || data.address?.en || '-';

            document.getElementById('location-info').classList.remove('hidden');
        }

        // Use selected location
        function useLocation() {
            if (!selectedLocation) return;

            console.log('Selected Location:', selectedLocation);
            showAlert('تم حفظ الموقع بنجاح! يمكنك الآن استخدام هذه البيانات في نظامك', 'success');

            // Here you can send the data to your backend or use it in your application
            // Example: Save to user profile, order, etc.
        }

        // Reset selection
        function resetSelection() {
            marker.setVisible(false);
            selectedLocation = null;
            document.getElementById('use-location-btn').disabled = true;
            document.getElementById('location-info').classList.add('hidden');
            document.getElementById('map-search-input').value = '';
            map.setCenter(DEFAULT_CENTER);
            map.setZoom(12);
            clearAlert();
        }

        // Get current location
        // function getCurrentLocation() {
        //     if (navigator.geolocation) {
        //         showLoading(true);
        //         navigator.geolocation.getCurrentPosition(
        //             (position) => {
        //                 const pos = {
        //                     lat: position.coords.latitude,
        //                     lng: position.coords.longitude
        //                 };
        //                 map.setCenter(pos);
        //                 marker.setPosition(pos);
        //                 marker.setVisible(true);
        //                 handleLocationChange(pos.lat, pos.lng);
        //             },
        //             () => {
        //                 showLoading(false);
        //                 showAlert('فشل في الحصول على موقعك الحالي', 'error');
        //             }
        //         );
        //     } else {
        //         showAlert('المتصفح لا يدعم خدمة تحديد الموقع', 'error');
        //     }
        // }
            function getCurrentLocation() {
                if (navigator.geolocation) {
                    showLoading(true);
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const pos = {
                                lat: position.coords.latitude,
                                lng: position.coords.longitude
                            };
                            map.setCenter(pos);
                            marker.setPosition(pos);
                            marker.setVisible(true);
                            handleLocationChange(pos.lat, pos.lng);
                        },
                        () => {
                            showLoading(false);
                            showAlert('فشل في الحصول على موقعك الحالي', 'error');
                        },
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 } // ← هذا السطر الجديد
                    );
                } else {
                    showAlert('المتصفح لا يدعم خدمة تحديد الموقع', 'error');
                }
            }

        // Show/hide loading overlay
        function showLoading(show) {
            const overlay = document.getElementById('loading-overlay');
            if (show) {
                overlay.classList.add('active');
            } else {
                overlay.classList.remove('active');
            }
        }

        // Show alert message
        function showAlert(message, type) {
            const container = document.getElementById('alert-container');
            container.innerHTML = `
                <div class="alert alert-${type}">
                    ${message}
                </div>
            `;
        }

        // Clear alert
        function clearAlert() {
            document.getElementById('alert-container').innerHTML = '';
        }

        // Load Google Maps script dynamically
        function loadGoogleMaps() {
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&callback=initMap&language=ar`;
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        }

        // Initialize on page load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadGoogleMaps);
        } else {
            loadGoogleMaps();
        }
    </script>
</body>
</html>
