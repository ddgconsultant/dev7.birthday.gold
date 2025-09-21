<script>
let touchStartY = 0;
let touchEndY = 0;
let screenHeight = window.innerHeight;
let middleTop = screenHeight * 0.25; // Define top boundary of the middle section (25% of the screen)
let middleBottom = screenHeight * 0.75; // Define bottom boundary of the middle section (75% of the screen)

// Detect if touch/mouse events are NOT in the middle section (in top/bottom areas)
function isTouchInSwipeArea(touchY) {
    return touchY <= middleTop || touchY >= middleBottom;
}

// Check if the event is within the .right-panel element
function isHoveringOverRightPanel(event) {
    const rightPanel = document.querySelector('.right-panel');
    if (!rightPanel) return false;
    const rect = rightPanel.getBoundingClientRect();
    
    // For touch events
    if (event.changedTouches) {
        const touch = event.changedTouches[0];
        return touch.clientX >= rect.left && touch.clientX <= rect.right &&
               touch.clientY >= rect.top && touch.clientY <= rect.bottom;
    }
    
    // For mouse events
    return event.clientX >= rect.left && event.clientX <= rect.right &&
           event.clientY >= rect.top && event.clientY <= rect.bottom;
}

// Detect mouse scroll or touch swipe only if it's in the swipe areas (top/bottom) and no comment-overlay
document.addEventListener('wheel', function(event) {
    let mouseY = event.clientY;
    if (isTouchInSwipeArea(mouseY) && 
        !document.querySelector('.comment-overlay').classList.contains('active') &&
        isHoveringOverRightPanel(event)) {
        if (event.deltaY > 0) {
            moveToNextPost();
        } else {
            moveToPreviousPost();
        }
    }
});

// Detect touch start/end and only trigger scroll if touch is in the swipe areas and over .right-panel
document.addEventListener('touchstart', function(event) {
    touchStartY = event.changedTouches[0].screenY;
}, false);

document.addEventListener('touchend', function(event) {
    touchEndY = event.changedTouches[0].screenY;
    if (isTouchInSwipeArea(touchStartY) && 
        !document.querySelector('.comment-overlay').classList.contains('active') && 
        isHoveringOverRightPanel(event)) {
        handleGesture();
    }
}, false);

function handleGesture() {
    const swipeDistance = Math.abs(touchEndY - touchStartY);
    // Only navigate if swipe is significant (at least 30 pixels)
    if (swipeDistance > 30) {
        if (touchEndY < touchStartY) {
            moveToNextPost();
        } else if (touchEndY > touchStartY) {
            moveToPreviousPost();
        }
    }
}

function moveToNextPost() {
    window.location.href = '/social/navigate?a=next';
}

function moveToPreviousPost() {
    window.location.href = '/social/navigate?a=prev';
}

</script>