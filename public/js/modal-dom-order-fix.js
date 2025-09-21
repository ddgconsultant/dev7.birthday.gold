/**
 * Modal DOM Order Fix
 * Ensures modals always appear after their backdrops in the DOM
 * This is the permanent fix for the modal z-index issue
 */

(function() {
    console.log('Modal DOM Order Fix Active');
    
    // Function to ensure correct DOM order
    function ensureModalDOMOrder() {
        const modals = document.querySelectorAll('.modal');
        const backdrops = document.querySelectorAll('.modal-backdrop');
        
        // Move all backdrops to end of body first
        backdrops.forEach(backdrop => {
            document.body.appendChild(backdrop);
        });
        
        // Then move all modals after backdrops
        modals.forEach(modal => {
            document.body.appendChild(modal);
        });
    }
    
    // Override Bootstrap Modal show method to fix DOM order
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const originalShow = bootstrap.Modal.prototype.show;
        
        bootstrap.Modal.prototype.show = function() {
            // Call original show method
            originalShow.call(this);
            
            // Fix DOM order after modal is shown
            setTimeout(ensureModalDOMOrder, 50);
        };
    }
    
    // Watch for new modals or backdrops being added
    const observer = new MutationObserver(function(mutations) {
        let needsFix = false;
        
        mutations.forEach(mutation => {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1 && node.classList) {
                        if (node.classList.contains('modal-backdrop') || node.classList.contains('modal')) {
                            needsFix = true;
                        }
                    }
                });
            }
        });
        
        if (needsFix) {
            setTimeout(ensureModalDOMOrder, 10);
        }
    });
    
    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: false
    });
    
    // Also fix on modal events as backup
    document.addEventListener('show.bs.modal', function() {
        setTimeout(ensureModalDOMOrder, 100);
    });
    
    // Fix any existing modals on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureModalDOMOrder);
    } else {
        ensureModalDOMOrder();
    }
    
    console.log('Modal DOM Order Fix initialized - modals will now always appear above backdrops');
})();