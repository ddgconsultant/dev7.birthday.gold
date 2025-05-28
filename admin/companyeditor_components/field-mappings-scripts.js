// Format definitions
const formatDefinitions = {
    'date': {
        description: 'Date formatting',
        formats: [
            { value: 'j', label: 'Day of month without leading zeros (1-31)' },
            { value: 'd', label: 'Day of month with leading zeros (01-31)' },
            { value: 'D', label: 'Day of week abbreviated name (Mon-Sun)' },
            { value: 'm', label: 'Month with leading zeros (01-12)' },
            { value: 'n', label: 'Month without leading zeros (1-12)' },
            { value: 'M', label: 'Month abbreviated name (Jan-Dec)' },
            { value: 'Y', label: 'Full year (2024)' },
            { value: 'y', label: 'Two digit year (24)' }
        ]
    },
    'phone': {
        description: 'Phone number formatting',
        formats: [
            { value: '(###) ###-####', label: 'US format with area code' },
            { value: '###-###-####', label: 'US format without parentheses' },
            { value: '+#-###-###-####', label: 'International format' }
        ]
    },
    'email': {
        description: 'Email formatting',
        formats: [
            { value: 'lowercase', label: 'Convert to lowercase' },
            { value: 'trim', label: 'Remove whitespace' },
            { value: 'validate', label: 'Validate email format' }
        ]
    }
};

// Common field mappings for quick reference
const commonMappings = {
    'profile_username': 'username||lowercase',
    'profile_email': 'email||validate',
    'profile_password': 'password||hash',
    'profile_first_name': 'fname||capitalize',
    'profile_last_name': 'lname||capitalize',
    'birthdate': 'birth_date||date||Y-m-d'
};

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('fieldMappingForm');
    const mappingsTable = document.getElementById('mappingsTable');
    const addMappingBtn = document.getElementById('addMapping');
    const resetFormBtn = document.getElementById('resetForm');
    const quickTemplates = document.getElementById('quickTemplates');

    // Initialize tooltips