<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WebSlides Slide Format Selector</title>
    <link rel="stylesheet" href="static/css/webslides.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }
        .container {
            display: flex;
            height: 100%;
        }
        .left-column {
            width: 45%;
            padding: 20px;
            box-sizing: border-box;
        }
        .right-column {
            width: 55%;
            padding: 20px;
            box-sizing: border-box;
            height: 100%;
        }
        iframe {
            width: 100%;
            border: 1px solid #ccc;
        }
        #demo-iframe {
            height: 550px;
        }
        #form-iframe {
            height: 100%;
        }
        #slide-format {
            width: 75%;
        }
    </style>
    <script>
        function updateDemo() {
            const format = document.getElementById('slide-format').value;
            document.getElementById('demo-iframe').src = 'https://webslides.tv/demos/portfolios#slide=' + format;
            document.getElementById('form-iframe').src = 'form.php?slide=' + format+'&grp=birthdaygoldintro&pres=Birthday+Gold+Intro';
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="left-column">
            <h2>WebSlides Slide Format Selector</h2>
            <select id="slide-format" onchange="updateDemo()">
            <?
                for ($i = 1; $i <= 80; $i++) {
                    echo ' <option value="'.$i.'">'.$i.'</option>';
                }
            ?>
            </select>
            <h4>Demo Slide</h4>
            <iframe id="demo-iframe" src="https://webslides.tv/demos/portfolios#slide=1"></iframe>
        </div>
        <div class="right-column">
            <iframe id="form-iframe" src="form.php?slide=1&grp=birthdaygoldintro&pres=Birthday+Gold+Intro"></iframe>
        </div>
    </div>
</body>
</html>
