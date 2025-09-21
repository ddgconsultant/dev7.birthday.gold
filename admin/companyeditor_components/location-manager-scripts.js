// Business hours templates
const hourTemplates = {
    standard: {
        monday: { open: '09:00', close: '17:00' },
        tuesday: { open: '09:00', close: '17:00' },
        wednesday: { open: '09:00', close: '17:00' },
        thursday: { open: '09:00', close: '17:00' },
        friday: { open: '09:00', close: '17:00' },
        saturday: { open: '', close: '' },
        sunday: { open: '', close: '' }
    },
    retail: {
        monday: { open: '10:00', close: '21:00' },
        tuesday: { open: '10:00', close: '21:00' },
        wednesday: { open: '10:00', close: '21:00' },
        thursday: { open: '10:00', close: '21:00' },
        friday: { open: '10:00', close: '21:00' },
        saturday: { open: '10:00', close: '21:00' },
        sunday: { open: '11:00', close: '18:00' }
    },
    restaurant: {
        monday: { open: '11:00', close: '22:00' },
        tuesday: { open: '11:00', close: '22:00' },
        wednesday: { open: '11:00', close: '22:00' },
        thursday: { open: '11:00', close: '22:00' },
        friday: { open: '11:00', close: '23:00' },
        saturday: { open: '11:00', close: '23:00' },
        sunday: { open: '11:00', close: '22:00' }
    }
};

// Location validation rules
const validateLocation = (data) => {
    const errors = {};
    
    // Basic field validation
    if (!data.location_name?.trim()) {
        errors.location_name = 'Location name is required';
    }
    
    if (!data.address?.trim()) {
        errors.address = 'Address is required';
    }
    
    if (!data.city?.trim()) {
        errors.city = 'City is required';
    }
    
    if (!data.state?.trim()) {
        errors.state = 'State is required';
    }
    
    if (!data.zip_code?.trim()) {
        errors.zip_code = 'ZIP code is required';
    } else if (!/^\d{5}(-\d{4})?$/.test(data.zip_code)) {
        errors.zip_code = 'Invalid ZIP code format';
    }
    
    // Phone validation
    if (data.phone_number && !/^\+?[\d\s-()