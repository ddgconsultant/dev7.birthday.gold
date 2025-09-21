<?PHP
$nosessiontracking = true;
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

include('x_pagestart.inc');
include('x_header.inc');



$additionalstyles .= "
<style>
    .sidebar-left {
        height: 100vh;
        overflow-y: auto;
        position: fixed;
        width: 250px;
        transition: width 0.3s;
    }
    .main-content {
        overflow-y: auto;
        height: calc(100vh - 56px);
        transition: margin-left 0.3s;
    }
    .sidebar-collapse-button {
        position: sticky;
        bottom: 0;
        width: 100%;
        text-align: left;
        background: none;
        border: none;
        padding: 10px;
    }
    .navbar-main img {
        height: 40px;
        width: auto;
    }
    .navbar-toggler {
        order: 1;
    }
    .navbar-main {
        order: 0;
    }
    .nav-item i {
        margin-right: 10px;
    }
    .collapsed .nav-item span {
        display: none;
    }
    .collapsed {
        width: 80px;
    }
  
   .dropdown-menu-end {
        right: 0;
        left: auto;
    }
      body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }
    .header {
        height: 56px;
        background-color: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 1rem;
        position: sticky;
        top: 0;
        z-index: 1030;
    }
    .sidebar {
        height: 100%;
        width: 250px;
        position: fixed;
        top: 56px;
        left: 0;
        background-color: #343a40;
        color: var(--bs-primary);
        padding-top: 1rem;
    }
    .sidebar.collapsed {
        width: 56px;
    }
    .sidebar .nav-link {
        color: var(--bs-primary);
    }
    .avatar-menu .small-screen-nav {
        display: none;
    }
    .main-content {
        margin-top: 56px;
        padding: 1rem;
        flex: 1;
    }
    .footer {
        height: 150px;
        background-color: #f8f9fa;
        width: 100%;
        margin-top: auto;
    }
    .footer-content {
        padding: 1rem;
    }

    

    /* Media query for small screens */
    @media (max-width: 575.98px) {
        .sidebar {
            display: none;
        }
        #sidebar {
            display: none;
        }
        .main-content {
            margin-left: 0px;
            width: 100%;
        }

            .navbar-nav {
                display: none;
            }
        }

  
    @media (max-width: 768px) {
        .sidebar {
            width: 56px;
        }

            .navbar-main img {
                height: 40px;
                width: 40px;
            }
            .navbar-nav {
                display: none;
            }
            .navbar-toggler {
                display: block;
            }
            .sidebar-left {
                width: 80px;
                margin-left: 0;
            }
            .main-content {
                margin-left: 350px;
            }
            .sidebar-collapse-button {
                left: 0;
            }
        }


    @media (max-width: 992px) {
        .avatar-menu .small-screen-nav {
            display: block;
        }

        .main-content {
        margin-left: 250px;
            }
    }
 
</style>

";

?>
</head>

<body>



    <!-- Collapsible Left Sidebar -->
    <div class="bg-light sidebar sidebar-left p-3 pt-5" id="sidebar">
        <nav class="nav flex-column">
            <h5 class="text-secondary">Admin Functions</h5>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="#"><i class="bi bi-house"></i><span>Dashboard</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="bi bi-people"></i><span>Users</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="bi bi-gear"></i><span>Settings</span></a>
                </li>
                <!-- Add more links as needed -->
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="bi bi-bar-chart"></i><span>Reports</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="bi bi-graph-up"></i><span>Analytics</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="bi bi-envelope"></i><span>Messages</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="bi bi-bell"></i><span>Notifications</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="bi bi-life-preserver"></i><span>Support</span></a>
                </li>
            </ul>
            <button class="btn sidebar-collapse-button" id="collapseButton">
                <i class="bi bi-chevron-left"></i>
            </button>
        </nav>
    </div>



    <!-- Main Content -->
    <main class="main-content pt-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <h1>Main Content Area</h1>
                    <p>Responsive content goes here.</p>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus imperdiet, nulla et dictum interdum, nisi lorem egestas odio, vitae scelerisque enim ligula venenatis dolor. Maecenas nisl est, ultrices nec congue eget, auctor vitae massa. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum. Praesent mauris. Fusce nec tellus sed augue semper porta. Mauris massa. Vestibulum lacinia arcu eget nulla. </p>
                    <p>Block level button:</p>
                    <button type="button" class="btn btn-primary btn-lg btn-block">Block level button</button>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus imperdiet, nulla et dictum interdum, nisi lorem egestas odio, vitae scelerisque enim ligula venenatis dolor. Maecenas nisl est, ultrices nec congue eget, auctor vitae massa. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum. Praesent mauris. Fusce nec tellus sed augue semper porta. Mauris massa. Vestibulum lacinia arcu eget nulla. </p>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus imperdiet, nulla et dictum interdum, nisi lorem egestas odio, vitae scelerisque enim ligula venenatis dolor. Maecenas nisl est, ultrices nec congue eget, auctor vitae massa. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum. Praesent mauris. Fusce nec tellus sed augue semper porta. Mauris massa. Vestibulum lacinia arcu eget nulla. </p>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus imperdiet, nulla et dictum interdum, nisi lorem egestas odio, vitae scelerisque enim ligula venenatis dolor. Maecenas nisl est, ultrices nec congue eget, auctor vitae massa. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer nec odio. Praesent libero. Sed cursus ante dapibus diam. Sed nisi. Nulla quis sem at nibh elementum imperdiet. Duis sagittis ipsum. Praesent mauris. Fusce nec tellus sed augue semper porta. Mauris massa. Vestibulum lacinia arcu eget nulla. </p>

                </div>
            </div>
        </div>
    </main>


    <script>
        document.getElementById('collapseButton').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            const collapseButtonIcon = this.querySelector('i');
            if (sidebar.classList.contains('collapsed')) {
                collapseButtonIcon.classList.remove('bi-chevron-left');
                collapseButtonIcon.classList.add('bi-chevron-right');
            } else {
                collapseButtonIcon.classList.remove('bi-chevron-right');
                collapseButtonIcon.classList.add('bi-chevron-left');
            }
        });

        // Add a resize event listener to handle responsive behavior
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const collapseButtonIcon = document.getElementById('collapseButton').querySelector('i');
            if (window.innerWidth <= 768) {
                sidebar.classList.add('collapsed');
                collapseButtonIcon.classList.remove('bi-chevron-left');
                collapseButtonIcon.classList.add('bi-chevron-right');
            } else {
                sidebar.classList.remove('collapsed');
                collapseButtonIcon.classList.remove('bi-chevron-right');
                collapseButtonIcon.classList.add('bi-chevron-left');
            }
        });

        // Trigger the resize event listener to set the initial state
        window.dispatchEvent(new Event('resize'));
    </script>
    <?PHP
    include('x_footer.inc');


    // Search and replace content before sending it to the client
    $content = ob_get_clean();
    $content = str_replace('</head>', $additionalstyles . '</head>', $content);
    echo $content;

    // End output buffering
    ob_end_flush();
