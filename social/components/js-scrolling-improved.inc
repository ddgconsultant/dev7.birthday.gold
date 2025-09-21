<script>
// Improved swipe and scroll navigation for social module
(function() {
    let touchStartY = 0;
    let touchEndY = 0;
    let touchStartX = 0;
    let touchEndX = 0;
    let isNavigating = false;
    
    // Check if we're on a comments element
    function isOnCommentsElement(target) {
        return target.closest('.comments-panel-desktop') || 
               target.closest('.comments-panel-mobile') ||
               target.closest('.comments-list-desktop') ||
               target.closest('.comments-list-mobile') ||
               target.closest('.mobile-comments-button') ||
               target.closest('.comment-overlay');
    }
    
    // Check if in the main content area
    function isInContentArea(event) {
        // For touch events
        if (event.changedTouches) {
            const touch = event.changedTouches[0];
            const element = document.elementFromPoint(touch.clientX, touch.clientY);
            return element && (element.closest('.social-feed') || element.closest('.post-content-area'));
        }
        // For mouse events
        const element = event.target;
        return element && (element.closest('.social-feed') || element.closest('.post-content-area'));
    }
    
    // Navigation functions
    function moveToNextPost() {
        if (!isNavigating) {
            isNavigating = true;
            window.location.href = '/social/navigate?a=next';
        }
    }
    
    function moveToPreviousPost() {
        if (!isNavigating) {
            isNavigating = true;
            window.location.href = '/social/navigate?a=prev';
        }
    }
    
    // Touch handling for mobile swipe
    document.addEventListener('touchstart', function(e) {
        if (!isOnCommentsElement(e.target) && isInContentArea(e)) {
            touchStartY = e.changedTouches[0].screenY;
            touchStartX = e.changedTouches[0].screenX;
        }
    }, { passive: true });
    
    document.addEventListener('touchend', function(e) {
        if (!isOnCommentsElement(e.target) && isInContentArea(e)) {
            touchEndY = e.changedTouches[0].screenY;
            touchEndX = e.changedTouches[0].screenX;
            handleSwipeGesture();
        }
    }, { passive: true });
    
    function handleSwipeGesture() {
        const minSwipeDistance = 50;
        const verticalDistance = touchStartY - touchEndY;
        const horizontalDistance = Math.abs(touchStartX - touchEndX);
        
        // Check if it's more vertical than horizontal (to distinguish from sideways swipes)
        if (Math.abs(verticalDistance) > minSwipeDistance && Math.abs(verticalDistance) > horizontalDistance) {
            if (verticalDistance > 0) {
                // Swiped up - next post
                moveToNextPost();
            } else {
                // Swiped down - previous post
                moveToPreviousPost();
            }
        }
    }
    
    // Mouse wheel navigation for desktop
    let wheelTimeout;
    let lastWheelTime = 0;
    
    document.addEventListener('wheel', function(e) {
        const now = Date.now();
        
        // Only process if in content area and not on comments
        if (!isOnCommentsElement(e.target) && isInContentArea(e)) {
            // Throttle wheel events (min 500ms between navigations)
            if (now - lastWheelTime > 500) {
                e.preventDefault();
                
                if (e.deltaY > 50) {
                    moveToNextPost();
                    lastWheelTime = now;
                } else if (e.deltaY < -50) {
                    moveToPreviousPost();
                    lastWheelTime = now;
                }
            }
        }
    }, { passive: false });
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        // Don't navigate if typing in input/textarea
        if (e.target.matches('input, textarea')) return;
        
        switch(e.key) {
            case 'ArrowDown':
            case 'ArrowRight':
                e.preventDefault();
                moveToNextPost();
                break;
                
            case 'ArrowUp':
            case 'ArrowLeft':
                e.preventDefault();
                moveToPreviousPost();
                break;
                
            case 'l':
            case 'L':
                // Like post
                e.preventDefault();
                const likeBtn = document.querySelector('[data-action="like"]');
                if (likeBtn) likeBtn.click();
                break;
                
            case 'm':
            case 'M':
                // Mute/unmute
                e.preventDefault();
                const muteBtn = document.querySelector('[data-action="mute"]');
                if (muteBtn) muteBtn.click();
                break;
        }
    });
    
    // Visual feedback for navigation
    function showNavigationFeedback(direction) {
        const indicator = document.createElement('div');
        indicator.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 1rem 2rem;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border-radius: 50px;
            z-index: 9999;
            pointer-events: none;
            animation: fadeOut 0.5s ease-out;
        `;
        indicator.textContent = direction === 'next' ? '↓ Next' : '↑ Previous';
        document.body.appendChild(indicator);
        
        setTimeout(() => indicator.remove(), 500);
    }
    
    // Add CSS for animation
    if (!document.querySelector('#swipe-nav-styles')) {
        const style = document.createElement('style');
        style.id = 'swipe-nav-styles';
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
})();
</script>