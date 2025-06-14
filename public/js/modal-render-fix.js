/**
 * Modal Render Fix - Forces correct rendering order
 */

(function() {
    console.log('Modal Render Fix Loaded');
    
    function moveModalToEndOfBody() {
        console.log('Moving modals to end of body...');
        
        // Get all modals and backdrops
        const modals = document.querySelectorAll('.modal');
        const backdrops = document.querySelectorAll('.modal-backdrop');
        
        // Move backdrops first (they should be behind)
        backdrops.forEach((backdrop, index) => {
            console.log(`Moving backdrop ${index} to end of body`);
            document.body.appendChild(backdrop);
        });
        
        // Then move modals (they should be on top)
        modals.forEach((modal, index) => {
            console.log(`Moving modal ${index} (${modal.id}) to end of body`);
            document.body.appendChild(modal);
        });
        
        // Force z-index after moving
        backdrops.forEach(backdrop => {
            backdrop.style.cssText = 'position: fixed !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; z-index: 1040 !important;';
        });
        
        modals.forEach(modal => {
            if (modal.classList.contains('show')) {
                modal.style.cssText = 'position: fixed !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; z-index: 9999 !important; display: block !important;';
            }
        });
    }
    
    // Check DOM order
    function checkDOMOrder() {
        console.log('\n=== DOM ORDER CHECK ===');
        const bodyChildren = Array.from(document.body.children);
        let modalIndex = -1;
        let backdropIndex = -1;
        
        bodyChildren.forEach((child, index) => {
            if (child.classList.contains('modal-backdrop')) {
                backdropIndex = index;
                console.log(`Backdrop found at position ${index}`);
            }
            if (child.classList.contains('modal') && child.classList.contains('show')) {
                modalIndex = index;
                console.log(`Active modal found at position ${index}`);
            }
        });
        
        if (modalIndex > -1 && backdropIndex > -1) {
            if (modalIndex < backdropIndex) {
                console.error('ERROR: Modal appears BEFORE backdrop in DOM! This is why it\'s behind.');
                return false;
            } else {
                console.log('✓ Modal appears after backdrop in DOM (correct order)');
                return true;
            }
        }
        
        return true;
    }
    
    // Apply fixes on modal show
    document.addEventListener('show.bs.modal', function(e) {
        console.log('Modal show event - applying render fix');
        
        setTimeout(() => {
            const orderCorrect = checkDOMOrder();
            if (!orderCorrect) {
                moveModalToEndOfBody();
            }
        }, 100);
    });
    
    document.addEventListener('shown.bs.modal', function(e) {
        console.log('Modal shown event - checking render order');
        
        const orderCorrect = checkDOMOrder();
        if (!orderCorrect) {
            moveModalToEndOfBody();
        }
        
        // Also log computed styles
        const modal = document.querySelector('.modal.show');
        const backdrop = document.querySelector('.modal-backdrop');
        
        if (modal && backdrop) {
            console.log('\n=== COMPUTED STYLES ===');
            const modalStyle = getComputedStyle(modal);
            const backdropStyle = getComputedStyle(backdrop);
            
            console.log('Modal computed z-index:', modalStyle.zIndex);
            console.log('Backdrop computed z-index:', backdropStyle.zIndex);
            console.log('Modal position:', modalStyle.position);
            console.log('Backdrop position:', backdropStyle.position);
            
            // Check if they're in the same stacking context
            let modalParent = modal.parentElement;
            let backdropParent = backdrop.parentElement;
            
            console.log('Modal parent:', modalParent.tagName, modalParent.className);
            console.log('Backdrop parent:', backdropParent.tagName, backdropParent.className);
            
            if (modalParent !== backdropParent) {
                console.error('ERROR: Modal and backdrop have different parents! This can cause stacking issues.');
            }
        }
    });
    
    // Expose functions
    window.moveModalToEndOfBody = moveModalToEndOfBody;
    window.checkDOMOrder = checkDOMOrder;
    
    console.log('Modal Render Fix initialized');
    console.log('Run checkDOMOrder() to see current state');
    console.log('Run moveModalToEndOfBody() to force correct order');
})();