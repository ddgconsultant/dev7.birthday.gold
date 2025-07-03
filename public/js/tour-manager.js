/**
 * Tour Manager - Handles all tour page functionality
 * Following Birthday Gold JavaScript patterns
 */

class TourManager {
    constructor(config) {
        this.config = config;
        this.map = null;
        this.directionsService = null;
        this.directionsRenderer = null;
        this.markers = [];
        this.lastSmsSentTime = 0;
        this.smsInProgress = false;
        
        this.init();
    }
    
    init() {
        // Initialize sortable
        this.initSortable();
        
        // Load Google Maps
        this.loadGoogleMaps();
        
        // Set up event handlers
        this.bindEvents();
        
        // Set print title
        this.setupPrintTitle();
    }
    
    initSortable() {
        $('#sortable-businesses').sortable({
            handle: '.drag-handle',
            axis: 'y',
            items: '.sortable-item:not(.out-of-range)',
            update: () => {
                $('#update-map-btn').show();
            }
        });
        
        // Touch support for iPad
        if ('ontouchend' in document) {
            this.addTouchSupport();
        }
    }
    
    addTouchSupport() {
        // Touch-to-mouse event mapping for jQuery UI
        $('#sortable-businesses').on('touchstart', '.drag-handle', (e) => {
            const touch = e.originalEvent.touches[0];
            const mouseEvent = new MouseEvent('mousedown', {
                bubbles: true,
                cancelable: true,
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            e.target.dispatchEvent(mouseEvent);
            e.preventDefault();
        });
    }
    
    loadGoogleMaps() {
        if (typeof google === 'undefined') {
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${this.config.apiKey}&libraries=places&callback=tourManagerMapCallback`;
            document.head.appendChild(script);
            window.tourManagerMapCallback = () => this.initMap();
        } else {
            this.initMap();
        }
    }
    
    initMap() {
        // Initialize main map
        this.map = new google.maps.Map(document.getElementById('tour-map'), {
            zoom: 10,
            center: { lat: 39.571822, lng: -104.87961 }
        });
        
        this.directionsService = new google.maps.DirectionsService();
        this.directionsRenderer = new google.maps.DirectionsRenderer({
            map: this.map,
            panel: document.getElementById('tour-directions'),
            suppressMarkers: true
        });
        
        this.calculateRoute();
    }
    
    calculateRoute() {
        if (this.config.locations.length < 2) return;
        
        const waypoints = [];
        for (let i = 1; i < this.config.locations.length - 1; i++) {
            waypoints.push({
                location: this.config.locations[i].address,
                stopover: true
            });
        }
        
        const request = {
            origin: this.config.locations[0].address,
            destination: this.config.locations[this.config.locations.length - 1].address,
            waypoints: waypoints,
            travelMode: google.maps.TravelMode.DRIVING,
            optimizeWaypoints: false
        };
        
        this.directionsService.route(request, (result, status) => {
            if (status === 'OK') {
                this.directionsRenderer.setDirections(result);
                this.addCustomMarkers(result);
            }
        });
    }
    
    addCustomMarkers(result) {
        // Clear existing markers
        this.markers.forEach(marker => marker.setMap(null));
        this.markers = [];
        
        // Add numbered markers
        this.config.locations.forEach((location, index) => {
            const marker = new google.maps.Marker({
                position: result.routes[0].overview_path[0], // Simplified
                map: this.map,
                label: (index + 1).toString(),
                title: location.name
            });
            this.markers.push(marker);
        });
    }
    
    updateMap() {
        // Get new order from DOM
        const newLocations = [this.config.locations[0]]; // Keep home first
        
        $('#sortable-businesses .sortable-item').each((index, element) => {
            const companyId = $(element).data('company-id');
            const location = this.config.locations.find(loc => 
                loc.company_id === companyId
            );
            if (location) {
                newLocations.push(location);
            }
        });
        
        this.config.locations = newLocations;
        this.calculateRoute();
        $('#update-map-btn').hide();
    }
    
    sendToPhone() {
        // Rate limiting
        if (this.smsInProgress) {
            this.showAlert('Please wait, processing...', 'warning');
            return;
        }
        
        const timeSinceLastSms = (Date.now() - this.lastSmsSentTime) / 1000;
        if (this.lastSmsSentTime > 0 && timeSinceLastSms < 60) {
            const remaining = Math.ceil(60 - timeSinceLastSms);
            this.showAlert(`Please wait ${remaining} seconds before sending again.`, 'warning');
            return;
        }
        
        // Build navigation URL
        const locations = this.config.locations.filter(loc => !loc.isOutOfRange);
        if (locations.length < 2) {
            this.showAlert('No businesses to navigate to.', 'danger');
            return;
        }
        
        // Disable button
        this.smsInProgress = true;
        const $btn = $('button[onclick="tourManager.sendToPhone()"]');
        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        
        // Prepare navigation URL
        const waypoints = locations.map(loc => encodeURIComponent(loc.address));
        const navigationUrl = `https://www.google.com/maps/dir/${waypoints.join('/')}`;
        
        // Send AJAX request
        $.post('/myaccount/tour-compressed.php', {
            action: 'send_to_phone',
            navigation_url: navigationUrl,
            tour_date: this.config.date,
            _token: window.csrfToken || ''
        })
        .done((response) => {
            if (response.success) {
                this.showAlert(response.message, 'success');
                this.lastSmsSentTime = Date.now();
            } else {
                this.showAlert(response.message || 'Failed to send.', 'danger');
            }
        })
        .fail(() => {
            this.showAlert('Network error. Please try again.', 'danger');
        })
        .always(() => {
            this.smsInProgress = false;
            $btn.prop('disabled', false).html(originalHtml);
        });
    }
    
    pickLocation(companyId) {
        // Open location picker modal
        const modal = new bootstrap.Modal(document.getElementById('locationPickerModal'));
        modal.show();
        
        // Store current company
        this.currentCompanyId = companyId;
        
        // Initialize picker map if needed
        if (!this.pickerMap) {
            this.initPickerMap();
        }
    }
    
    initPickerMap() {
        this.pickerMap = new google.maps.Map(document.getElementById('picker-map'), {
            zoom: 12,
            center: { lat: 39.571822, lng: -104.87961 }
        });
    }
    
    searchLocations() {
        const radius = $('#radius-select').val();
        
        $.post('/myaccount/tour-compressed.php', {
            action: 'search_locations',
            company_id: this.currentCompanyId,
            radius: radius,
            _token: window.csrfToken || ''
        })
        .done((response) => {
            if (response.success) {
                this.displayLocationResults(response.locations);
            } else {
                this.showAlert('Search failed.', 'danger');
            }
        });
    }
    
    displayLocationResults(locations) {
        const $results = $('#location-results');
        $results.empty();
        
        locations.forEach(location => {
            const $item = $(`
                <a href="#" class="list-group-item list-group-item-action">
                    <strong>${location.name}</strong><br>
                    <small>${location.address}</small>
                    <span class="badge bg-secondary float-end">${location.distance} mi</span>
                </a>
            `);
            
            $item.on('click', (e) => {
                e.preventDefault();
                this.selectLocation(location);
            });
            
            $results.append($item);
        });
    }
    
    selectLocation(location) {
        $.post('/myaccount/tour-compressed.php', {
            action: 'pick_location',
            company_id: this.currentCompanyId,
            location_id: location.id,
            _token: window.csrfToken || ''
        })
        .done((response) => {
            if (response.success) {
                location.reload();
            } else {
                this.showAlert('Failed to update location.', 'danger');
            }
        });
    }
    
    showAlert(message, type = 'info', duration = 5000) {
        // Use existing alert system or create simple one
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        const $container = $('#alert-container');
        if ($container.length === 0) {
            $('body').append('<div id="alert-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>');
        }
        
        const $alert = $(alertHtml);
        $('#alert-container').append($alert);
        
        if (duration > 0) {
            setTimeout(() => $alert.alert('close'), duration);
        }
    }
    
    setupPrintTitle() {
        const originalTitle = document.title;
        
        window.addEventListener('beforeprint', () => {
            document.title = `Birthday.Gold - My Tour ${this.config.formattedDate}.pdf`;
        });
        
        window.addEventListener('afterprint', () => {
            document.title = originalTitle;
        });
    }
    
    bindEvents() {
        // Handle window resize
        $(window).on('resize', () => {
            if (this.map) {
                google.maps.event.trigger(this.map, 'resize');
            }
        });
    }
}

// Initialize when ready
$(document).ready(() => {
    if (typeof tourConfig !== 'undefined') {
        window.tourManager = new TourManager(tourConfig);
    }
});