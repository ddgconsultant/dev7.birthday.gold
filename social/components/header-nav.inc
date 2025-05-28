
<?PHP
$additionalstyles .= '
<style>
    .angled-nav {
         padding: 5px;
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding-top: 15px;
        width: 600px; /* Set width */
        clip-path: polygon(0 0, 100% 0, 96.5% 100%, 3.5% 100%);
        margin: 0 auto; /* Horizontally center the nav */
    }

   .angled-nav  .icon-container {
        display: flex;
        gap: 30px;
    }

   .angled-nav  .icon-container a {
        text-align: center;
        text-decoration: none;
        padding: 5px 15px 5px 15px; /* Add padding for hover box */
        border-radius: 10px; /* Rounded box corners */
        transition: background-color 0.3s ease; /* Smooth transition for hover effect */
    }

   .angled-nav  .icon-title {
        font-size: 1rem;
        margin-top: 5px;
        color: #000;
    }

    .angled-nav .icon-container i {
        font-size: 2rem; /* Increase icon size */
        color: #000;
    }

   .angled-nav  .icon-container a:hover {
        background-color: rgba(0, 0, 0, 0.1); /* Add hover box */
    }

    /* Optional: Add hover effect for icons */
   .angled-nav  .icon-container a:hover i {
        color: #333; /* Darker color on hover */
    }

    /* Media query for screens smaller than 590px */
    @media (max-width: 590px) {
        .angled-nav {
            width: 85%; /* Full screen width */
            padding: 5px; /* Smaller padding */
        }

        .angled-nav .icon-container {
            gap: 15px; /* Reduce gap between icons */
        }

        .angled-nav .icon-container a {
            padding: 3px 10px; /* Smaller padding */
        }

        .angled-nav .icon-title {
            font-size: 0.8rem; /* Smaller font size */
        }

        .angled-nav .icon-container i {
            font-size: 1.5rem; /* Smaller icon size */
        }
    }
</style>
';



echo '
<nav class="angled-nav bg-secondary-subtle">
    <div class="icon-container">
        <a href="/social/" title="Home">
            <i class="bi bi-house-door-fill text-dark"></i>
            <div class="icon-title">Home</div>
        </a>
        <a href="/social/search" title="Search">
            <i class="bi bi-search text-dark"></i>
            <div class="icon-title">Search</div>
        </a>
        <a href="/social/create" title="Create Post">
            <i class="bi bi-plus-circle-fill text-dark"></i>
            <div class="icon-title">Create</div>
        </a>
        <a href="/social/activity" title="Bookmarks & Activity">
            <i class="bi bi-bookmark-fill text-dark"></i>
            <div class="icon-title">Activity</div>
        </a>
        <a href="/social/settings" title="Settings">
            <i class="bi bi-gear-fill text-dark"></i>
            <div class="icon-title">Settings</div>
        </a>
    </div>
</nav>
';