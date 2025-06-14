document.addEventListener('DOMContentLoaded', function() {
    var megaMenuButton = document.getElementById('megaMenuButton');
    var megaMenuToggle = document.getElementById('megaMenuToggle');
    var megaMenuGroup = document.getElementById('megaMenuGroup');
    var megaMenu = document.querySelector('.bg_mega-menu');
    var caretIcon = document.getElementById('caretIcon');

    function toggleMegaMenu(e) {
        e.preventDefault();
        megaMenu.style.display = (megaMenu.style.display === 'block') ? 'none' : 'block';
        megaMenuGroup.classList.toggle('dropup', megaMenu.style.display === 'block');
        megaMenuGroup.classList.toggle('dropdown', megaMenu.style.display !== 'block');
    }

    megaMenuButton.addEventListener('click', toggleMegaMenu);
    megaMenuToggle.addEventListener('click', toggleMegaMenu);

    document.addEventListener('click', function(e) {
        if (!megaMenu.contains(e.target) && e.target !== megaMenuButton && e.target !== megaMenuToggle) {
            megaMenu.style.display = 'none';
            megaMenuGroup.classList.remove('dropup');
            megaMenuGroup.classList.add('dropdown');
        }
    });

    // Variables to handle touch events
    let startX = 0;
    let startY = 0;

    // Function to handle touch start
    function handleTouchStart(event) {
        const touch = event.touches[0];
        startX = touch.clientX;
        startY = touch.clientY;
    }

    // Function to handle touch move
    function handleTouchMove(event) {
        if (!startX || !startY) {
            return;
        }

        const touch = event.touches[0];
        const diffX = touch.clientX - startX;
        const diffY = touch.clientY - startY;

        // Detect swipe left to close the mega menu
        if (Math.abs(diffX) > Math.abs(diffY) && diffX < -30) {
            megaMenu.style.display = 'none';
            megaMenuGroup.classList.remove('dropup');
            megaMenuGroup.classList.add('dropdown');
            startX = 0;
            startY = 0;
        }
    }

    // Add touch event listeners to the mega menu
    megaMenu.addEventListener('touchstart', handleTouchStart, false);
    megaMenu.addEventListener('touchmove', handleTouchMove, false);

    // Add touch event listeners to the document for opening the menu
    document.addEventListener('touchstart', function(event) {
        const touch = event.touches[0];
        startX = touch.clientX;
        startY = touch.clientY;
    }, false);

    document.addEventListener('touchmove', function(event) {
        if (!startX || !startY) {
            return;
        }

        const touch = event.touches[0];
        const diffX = touch.clientX - startX;
        const diffY = touch.clientY - startY;

        // Detect swipe right to open the mega menu
        if (Math.abs(diffX) > Math.abs(diffY) && diffX > 30) {
            megaMenu.style.display = 'block';
            megaMenuGroup.classList.add('dropup');
            megaMenuGroup.classList.remove('dropdown');
            startX = 0;
            startY = 0;
        }
    }, false);
});
