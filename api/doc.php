<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>API Documentation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    .post-color {
        background-color: #F7FCF7;  /* Lighter shade */
        border-color: #49cc90;
    }
    .get-color {
        background-color: #F0F7FF;  /* Lighter blue shade */
        border-color: #007BFF;
    }
    .post-color .accordion-button {
        background-color: #efffef;
        border-color: #49cc90;
    }
    .get-color .accordion-button {
        background-color: #B4C9E9;
        border-color: #4990cc;
    }


    </style>
</head>
<body>

<div class="container mt-5">
    <h1 class="text-center">API Documentation</h1>
    <div class="mt-3">
        <a href="_birthday.gold_openapi.yaml" class="btn btn-primary mb-3" download>Download Raw [yaml file]</a>
    </div>
    <hr>
    <div class="accordion" id="mainAccordion">
        <?php
        require '../vendor/autoload.php';
        use Symfony\Component\Yaml\Yaml;

        $yaml = file_get_contents('_birthday.gold_openapi.yaml');
        $data = Yaml::parse($yaml);

        function print_method_band($method, $colorClass, $content) {
            $method_id = str_replace('/', '-', $method);
            echo '<div class="accordion-item ">
                    <h2 class="accordion-header ' . $colorClass . ' ">
                        <button class="btn button accordion-button ' . $colorClass . '" type="button" data-bs-toggle="collapse" data-bs-target="#' . $method_id . '">
                        <h5 class="mb-0"><span class="badge bg-secondary">  ' . strtoupper($method) . '</span></h5>
                        </button>
                    </h2>
                    <div id="' . $method_id . '" class="accordion-collapse collapse  ' . $colorClass . ' ">
                        <div class="accordion-body  ' . $colorClass . '">
                            ' . $content . '
                        </div>
                    </div>
                  </div>';
        }
        

        foreach ($data as $key => $value) {
            if ($key === 'paths') {
                foreach ($value as $path => $methods) {
                    $path_id = str_replace('/', '-', $path);
                    
                    echo '<div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-' . $path_id . '">
                                  ' . $path . '
                                </button>
                            </h2>
                            <div id="collapse-' . $path_id . '" class="accordion-collapse collapse">
                                <div class="accordion-body">';
                
                    foreach ($methods as $method => $methodData) {
                        $colorClass = strtolower($method) == 'post' ? 'post-color' : 'get-color';


                        $content = is_array($methodData) ? '<pre>' . json_encode($methodData, JSON_PRETTY_PRINT) . '</pre>' : $methodData;

                      #  $content = is_array($methodData) ? '<pre>'.print_r($methodData,1).'</pre>' : $methodData;
                      
                        $method_id = str_replace('/', '-', $method);
                        print_method_band($method_id, $colorClass, $content);
                    }
                
                    echo '      </div>
                            </div>
                          </div>';
                }
            }
        }
        ?>
    </div>
    <?php
    foreach ($data as $key => $value) {
        if ($key !== 'paths') {
            echo '<div class="my-3 border p-3">';
            echo '<h2>' . $key . '</h2>';
            echo '<pre>' . print_r($value, true) . '</pre>';
            echo '</div>';
        }
    }
    ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
