<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            padding-top: 56px; /* Height of the navbar */
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .main-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
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
        @media (max-width: 768px) {
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
        }
        .footer {
            background: #f8f9fa;
            padding: 20px 0;
            width: 100%;
        }
    </style>
</head>
<body>
    <!-- Sticky Header -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top navbar-main">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="https://via.placeholder.com/150x40" alt="Company Logo" id="companyLogo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Pricing</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Help</a>
                    </li>
                </ul>
            </div>
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="bi bi-bell"></i>
                </div>
                <div class="me-3">
                    <i class="bi bi-gear"></i>
                </div>
                <div class="dropdown">
                    <img src="https://via.placeholder.com/40" alt="Avatar" class="rounded-circle dropdown-toggle" id="avatarMenu" data-bs-toggle="dropdown" aria-expanded="false">
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="avatarMenu">
                        <li><a class="dropdown-item" href="#">Profile</a></li>
                        <li><a class="dropdown-item" href="#">Account</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="flex-grow-1 main-content">
        <div class="container mt-3">
            <div class="row">
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
    </div>

    <!-- Sticky Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 col-sm-6">
                    <p>Footer column 1 content here</p>
                </div>
                <div class="col-md-4 col-sm-6">
                    <p>Footer column 2 content here</p>
                </div>
                <div class="col-md-4 col-sm-12">
                    <p>Footer column 3 content here</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
