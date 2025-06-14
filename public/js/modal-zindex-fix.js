/**
 * Modal Z-Index Fix
 * Forces correct z-index values on Bootstrap modals
 */

(function() {
    console.log('Modal Z-Index Fix Loaded');
    
    // Fix function to ensure proper z-index
    function fixModalZIndex() {
        // Fix all modals
        const modals = document.querySelectorAll('.modal');
        const backdrops = document.querySelectorAll('.modal-backdrop');
        
        // Set backdrop z-index
        backdrops.forEach(backdrop => {
            backdrop.style.zIndex = '1040';
            backdrop.style.position = 'fixed';
        });
        
        // Set modal z-index
        modals.forEach(modal => {
            if (modal.classList.contains('show')) {
                modal.style.zIndex = '1050';
                modal.style.position = 'fixed';
                
                // Ensure dialog is positioned correctly
                const dialog = modal.querySelector('.modal-dialog');
                if (dialog) {
                    dialog.style.position = 'relative';
                    dialog.style.zIndex = '1';
                }
            }
        });
    }
    
    // Override Bootstrap modal show method
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const originalShow = bootstrap.Modal.prototype.show;
        
        bootstrap.Modal.prototype.show = function() {
            console.log('Modal show intercepted');
            
            // Call original show
            originalShow.call(this);
            
            // Fix z-index after a delay to ensure DOM is updated
            setTimeout(fixModalZIndex, 10);
            setTimeout(fixModalZIndex, 100);
            setTimeout(fixModalZIndex, 300);
        };
    }
    
    // Listen for modal events
    document.addEventListener('shown.bs.modal', function(e) {
        console.log('Modal shown event - applying z-index fix');
        fixModalZIndex();
    });
    
    document.addEventListener('show.bs.modal', function(e) {
        console.log('Modal show event - preparing z-index fix');
        setTimeout(fixModalZIndex, 50);
    });
    
    // Fix on DOM mutations
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                // Check if modal or backdrop was added
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) { // Element node
                        if (node.classList && (node.classList.contains('modal-backdrop') || node.classList.contains('modal'))) {
                            console.log('Modal element added to DOM - fixing z-index');
                            setTimeout(fixModalZIndex, 10);
                        }
                    }
                });
            } else if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                // Check if show class was added
                if (mutation.target.classList && mutation.target.classList.contains('modal') && mutation.target.classList.contains('show')) {
                    console.log('Modal show class added - fixing z-index');
                    setTimeout(fixModalZIndex, 10);
                }
            }
        });
    });
    
    // Start observing
    observer.observe(document.body, {
        childList: true,
        attributes: true,
        subtree: true,
        attributeFilter: ['class']
    });
    
    // Manual fix function for testing
    window.fixModalZIndex = fixModalZIndex;
    
    console.log('Modal Z-Index Fix initialized');
})();