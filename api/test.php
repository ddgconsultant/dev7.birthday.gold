<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha1/css/bootstrap.min.css">
    <title>API Documentation</title>
    <style>
        body {
            padding: 20px;
        }
        .endpoint-list a {
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        .method {
            padding: 5px;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
        }
        .get {
            background-color: #4CAF50;
            color: white;
        }
        .post {
            background-color: #FF9800;
            color: white;
        }
        .put {
            background-color: #2196F3;
            color: white;
        }
        .delete {
            background-color: #F44336;
            color: white;
        }
        .endpoint-header {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            margin-top: 40px;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Left Side (Endpoints) -->
            <div class="col-md-3">
                <h3>Endpoints</h3>
                <div class="list-group endpoint-list">
                    <a href="#endpoint1" class="list-group-item">Users</a>
                    <a href="#endpoint2" class="list-group-item">Products</a>
                </div>
            </div>

            <!-- Right Side (Methods and Examples) -->
            <div class="col-md-9">
                <h3>Details</h3>
                <!-- Users Endpoint -->
                <div id="endpoint1" class="endpoint-header">
                    <h4>Users</h4>
                </div>
                <div>
                    <h5 class="method get">GET /users</h5>
                    <p>Retrieve a list of all users.</p>
                    <h6>Parameters:</h6>
                    <p>None</p>
                    <h6>Response Example:</h6>
                    <pre><code>[{"id":1,"name":"John Doe"},{"id":2,"name":"Jane Doe"}]</code></pre>
                    <h5 class="method post">POST /users</h5>
                    <p>Create a new user.</p>
                    <h6>Request Body Example:</h6>
                    <pre><code>{"name":"John Doe"}</code></pre>
                    <h6>Response Example:</h6>
                    <pre><code>{"id":1,"name":"John Doe"}</code></pre>
                </div>

                <!-- Products Endpoint -->
                <div id="endpoint2" class="endpoint-header">
                    <h4>Products</h4>
                </div>
                <div>
                    <h5 class="method get">GET /products</h5>
                    <p>Retrieve a list of all products.</p>
                    <h6>Parameters:</h6>
                    <p>None</p>
                    <h6>Response Example:</h6>
                    <pre><code>[{"id":1,"name":"Widget"},{"id":2,"name":"Gadget"}]</code></pre>
                    <h5 class="method put">PUT /products/:id</h5>
                    <p>Update a specific product.</p>
                    <h6>Request Body Example:</h6>
                    <pre><code>{"name":"Updated Widget"}</code></pre>
                    <h6>Response Example:</h6>
                    <pre><code>{"id":1,"name":"Updated Widget"}</code></pre>
                    <h5 class="method delete">DELETE /products/:id</h5>
                    <p>Delete a specific product.</p>
                    <h6>Response Example:</h6>
                    <pre><code>{"status":"success"}</code></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha1/js/bootstrap.min.js"></script>
</body>

</html>
