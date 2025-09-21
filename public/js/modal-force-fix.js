/**
 * Force Modal Fix - Direct Style Manipulation
 * This directly sets inline styles to override everything
 */

(function() {
    console.log('Modal Force Fix Loaded');
    
    // Force fix function
    function forceModalFix() {
        console.log('Forcing modal fix...');
        
        // Find all backdrops and force them to lower z-index
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach((backdrop, index) => {
            console.log(`Setting backdrop ${index} z-index to 1040`);
            backdrop.style.setProperty('z-index', '1040', 'important');
            backdrop.style.setProperty('position', 'fixed', 'important');
        });
        
        // Find all modals and force them to higher z-index
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach((modal, index) => {
            console.log(`Setting modal ${index} (${modal.id}) z-index to 1060`);
            modal.style.setProperty('z-index', '1060', 'important');
            modal.style.setProperty('position', 'fixed', 'important');
            
            // Also ensure the dialog is properly positioned
            const dialog = modal.querySelector('.modal-dialog');
            if (dialog) {
                dialog.style.setProperty('position', 'relative', 'important');
                dialog.style.setProperty('z-index', '1', 'important');
            }
        });
        
        // Special fix for the specific modal
        const otherAccountsModal = document.getElementById('otherAccountsModal');
        if (otherAccountsModal && otherAccountsModal.classList.contains('show')) {
            console.log('Applying special fix to otherAccountsModal');
            otherAccountsModal.style.setProperty('z-index', '2000', 'important');
        }
    }
    
    // Apply fix on any modal show event
    document.addEventListener('show.bs.modal', function(e) {
        console.log('Modal show event - scheduling force fix');
        // Run multiple times to catch all timing issues
        setTimeout(forceModalFix, 0);
        setTimeout(forceModalFix, 50);
        setTimeout(forceModalFix, 100);
        setTimeout(forceModalFix, 200);
        setTimeout(forceModalFix, 300);
    });
    
    document.addEventListener('shown.bs.modal', function(e) {
        console.log('Modal shown event - applying force fix');
        forceModalFix();
    });
    
    // Override Bootstrap Modal completely
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const OriginalModal = bootstrap.Modal;
        
        // Create new Modal class that extends the original
        class FixedModal extends OriginalModal {
            show() {
                console.log('FixedModal show called');
                super.show();
                
                // Force fix after showing
                setTimeout(() => {
                    if (this._element) {
                        this._element.style.setProperty('z-index', '2000', 'important');
                    }
                    forceModalFix();
                }, 50);
            }
        }
        
        // Replace Bootstrap Modal with our fixed version
        bootstrap.Modal = FixedModal;
        
        // Fix existing modal instances
        document.querySelectorAll('[data-bs-toggle="modal"]').forEach(trigger => {
            const targetSelector = trigger.getAttribute('data-bs-target');
            if (targetSelector) {
                const modalElement = document.querySelector(targetSelector);
                if (modalElement) {
                    // Dispose existing instance
                    const existingInstance = bootstrap.Modal.getInstance(modalElement);
                    if (existingInstance) {
                        existingInstance.dispose();
                    }
                    // Create new instance with fixed class
                    new FixedModal(modalElement);
                }
            }
        });
    }
    
    // Watch for style changes and revert them
    const styleObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                const target = mutation.target;
                
                // If it's a backdrop, ensure it stays at 1040
                if (target.classList && target.classList.contains('modal-backdrop')) {
                    const currentZ = target.style.zIndex;
                    if (currentZ && parseInt(currentZ) > 1040) {
                        console.log('Backdrop z-index changed to', currentZ, '- reverting to 1040');
                        target.style.setProperty('z-index', '1040', 'important');
                    }
                }
                
                // If it's a modal, ensure it stays above backdrop
                if (target.classList && target.classList.contains('modal') && target.classList.contains('show')) {
                    const currentZ = target.style.zIndex;
                    if (currentZ && parseInt(currentZ) < 1050) {
                        console.log('Modal z-index changed to', currentZ, '- setting to 2000');
                        target.style.setProperty('z-index', '2000', 'important');
                    }
                }
            }
        });
    });
    
    // Start observing
    styleObserver.observe(document.body, {
        attributes: true,
        attributeFilter: ['style'],
        subtree: true
    });
    
    // Expose function globally
    window.forceModalFix = forceModalFix;
    
    console.log('Modal Force Fix initialized - run forceModalFix() to manually fix');
})();