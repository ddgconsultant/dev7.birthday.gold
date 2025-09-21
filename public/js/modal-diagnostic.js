/**
 * Modal Diagnostic Script
 * This script helps diagnose modal z-index issues
 */

(function() {
    console.log('Modal Diagnostic Script Loaded');
    
    // Monitor for modal events
    document.addEventListener('show.bs.modal', function(e) {
        console.log('=== MODAL SHOW EVENT TRIGGERED ===');
        console.log('Modal element:', e.target);
        
        setTimeout(function() {
            diagnoseModal();
        }, 100);
    });
    
    document.addEventListener('shown.bs.modal', function(e) {
        console.log('=== MODAL SHOWN EVENT TRIGGERED ===');
        diagnoseModal();
    });
    
    function diagnoseModal() {
        console.log('\n=== MODAL DIAGNOSTIC REPORT ===');
        
        // Find all modals and backdrops
        const modals = document.querySelectorAll('.modal');
        const backdrops = document.querySelectorAll('.modal-backdrop');
        
        console.log('Number of modals found:', modals.length);
        console.log('Number of backdrops found:', backdrops.length);
        
        // Check each modal
        modals.forEach(function(modal, index) {
            console.log(`\nModal ${index + 1}:`, modal.id || 'no-id');
            console.log('- Classes:', modal.className);
            console.log('- Display:', getComputedStyle(modal).display);
            console.log('- Z-index:', getComputedStyle(modal).zIndex);
            console.log('- Position:', getComputedStyle(modal).position);
            console.log('- Transform:', getComputedStyle(modal).transform);
            
            // Check if modal has show class
            if (modal.classList.contains('show')) {
                console.log('- Status: VISIBLE (has .show class)');
                
                // Find modal dialog
                const dialog = modal.querySelector('.modal-dialog');
                if (dialog) {
                    console.log('- Dialog z-index:', getComputedStyle(dialog).zIndex);
                    console.log('- Dialog transform:', getComputedStyle(dialog).transform);
                }
            }
        });
        
        // Check each backdrop
        backdrops.forEach(function(backdrop, index) {
            console.log(`\nBackdrop ${index + 1}:`);
            console.log('- Classes:', backdrop.className);
            console.log('- Z-index:', getComputedStyle(backdrop).zIndex);
            console.log('- Opacity:', getComputedStyle(backdrop).opacity);
            console.log('- Display:', getComputedStyle(backdrop).display);
            console.log('- Background:', getComputedStyle(backdrop).backgroundColor);
        });
        
        // Check for problematic elements
        console.log('\n=== CHECKING FOR HIGH Z-INDEX ELEMENTS ===');
        const allElements = document.querySelectorAll('*');
        const highZIndexElements = [];
        
        allElements.forEach(function(el) {
            const zIndex = parseInt(getComputedStyle(el).zIndex);
            if (zIndex > 1050) {
                highZIndexElements.push({
                    element: el,
                    tagName: el.tagName,
                    id: el.id,
                    className: el.className,
                    zIndex: zIndex
                });
            }
        });
        
        highZIndexElements.sort((a, b) => b.zIndex - a.zIndex);
        highZIndexElements.forEach(function(item) {
            console.log(`- ${item.tagName}#${item.id || 'no-id'}.${item.className || 'no-class'}: z-index ${item.zIndex}`);
        });
        
        // Check what element is at the center of the viewport
        const centerX = window.innerWidth / 2;
        const centerY = window.innerHeight / 2;
        const elementAtCenter = document.elementFromPoint(centerX, centerY);
        
        console.log('\n=== ELEMENT AT VIEWPORT CENTER ===');
        if (elementAtCenter) {
            console.log('Element:', elementAtCenter.tagName);
            console.log('ID:', elementAtCenter.id || 'no-id');
            console.log('Classes:', elementAtCenter.className || 'no-class');
            console.log('Z-index:', getComputedStyle(elementAtCenter).zIndex);
            
            // Check if it's part of modal
            const isModalElement = elementAtCenter.closest('.modal');
            const isBackdropElement = elementAtCenter.classList.contains('modal-backdrop');
            
            if (isModalElement) {
                console.log('✓ Element is part of modal');
            } else if (isBackdropElement) {
                console.log('⚠ Element is the backdrop - modal might be behind it!');
            } else {
                console.log('✗ Element is NOT part of modal - something is blocking!');
            }
        }
        
        console.log('\n=== END DIAGNOSTIC REPORT ===\n');
    }
    
    // Add manual trigger button
    window.runModalDiagnostic = diagnoseModal;
    
    // Monitor for dynamic style changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                const target = mutation.target;
                if (target.classList && (target.classList.contains('modal') || target.classList.contains('modal-backdrop'))) {
                    console.log('Style changed on:', target.className);
                    console.log('New style:', target.getAttribute('style'));
                }
            }
        });
    });
    
    // Start observing body for changes
    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ['style'],
        subtree: true
    });
    
})();