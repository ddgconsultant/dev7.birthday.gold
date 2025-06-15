<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Mega Menu with Bootstrap 5.3</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/bg_megamenu.css">
   
</head>

<body>
    
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script src="/pubic/js/v3/bg_megamenu.js"></script>
      <!--
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var megaMenuButton = document.getElementById('megaMenuButton');
            var megaMenuToggle = document.getElementById('megaMenuToggle');
            var megaMenuGroup = document.getElementById('megaMenuGroup');
            var megaMenu = document.querySelector('.mega-menu');
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
        });
    </script>  -->

</body>

</html>