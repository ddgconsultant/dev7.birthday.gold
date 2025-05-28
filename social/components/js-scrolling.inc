<script>
let touchStartY = 0;
let touchEndY = 0;
let screenHeight = window.innerHeight;
let middleTop = screenHeight * 0.25; // Define top boundary of the middle section (25% of the screen)
let middleBottom = screenHeight * 0.75; // Define bottom boundary of the middle section (75% of the screen)

// Detect if touch/mouse events are in the middle section of the screen
function isTouchInMiddleSection(touchY) {
    return touchY > middleTop && touchY < middleBottom;
}

// Check if the event is within the .right-panel element
function isHoveringOverRightPanel(event) {
    const rightPanel = document.querySelector('.right-panel');
    const rect = rightPanel.getBoundingClientRect();
    return event.clientX >= rect.left && event.clientX <= rect.right &&
           event.clientY >= rect.top && event.clientY <= rect.bottom;
}

// Detect mouse scroll or touch swipe only if it's in the middle section and no comment-overlay
document.addEventListener('wheel', function(event) {
    let mouseY = event.clientY;
    if (isTouchInMiddleSection(mouseY) && 
        !document.querySelector('.comment-overlay').classList.contains('active') &&
        isHoveringOverRightPanel(event)) {
        if (event.deltaY > 0) {
            moveToNextPost();
        } else {
            moveToPreviousPost();
        }
    }
});

// Detect touch start/end and only trigger scroll if touch is in the middle section and over .right-panel
document.addEventListener('touchstart', function(event) {
    touchStartY = event.changedTouches[0].screenY;
}, false);

document.addEventListener('touchend', function(event) {
    touchEndY = event.changedTouches[0].screenY;
    if (isTouchInMiddleSection(touchStartY) && 
        !document.querySelector('.comment-overlay').classList.contains('active') && 
        isHoveringOverRightPanel(event.changedTouches[0])) {
        handleGesture();
    }
}, false);

function handleGesture() {
    if (touchEndY < touchStartY) {
        moveToNextPost();
    }
    if (touchEndY > touchStartY) {
        moveToPreviousPost();
    }
}

function moveToNextPost() {
    window.location.href = '/social/navigate?a=next';
}

function moveToPreviousPost() {
    window.location.href = '/social/navigate?a=prev';
}

</script>