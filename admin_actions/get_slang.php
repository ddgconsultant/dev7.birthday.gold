<?php include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 

exit;

// Loop through decades from 1930 to 2020
$decades = range(1920, 2000, 10);
#$decades=[1930];


foreach ($decades as $era) {
    // Initialize variables for each decade
    $terms = [];

    // Get the HTML content
    $html = file_get_contents("https://www.alphadictionary.com/slang/?term=&beginEra=$era&endEra=&clean=true&submitsend=Search");

    // Initialize DOMDocument and load HTML content
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML($html);
    libxml_clear_errors();

    // Initialize DOMXPath for querying
    $xpath = new DOMXPath($doc);

    // Loop through each 'li' element
    $li_elements = $xpath->query("//ul[@class='results']/li");

    foreach ($li_elements as $li) {
        $word = trim($xpath->query(".//span[@class='word']", $li)->item(0)->nodeValue);
        $meaning = trim($xpath->query(".//span[@class='meaning']", $li)->item(0)->nodeValue);

        $terms[] = [
            'term' => $word,
            'definition' => $meaning,
        ];

        if (count($terms) % 10 === 0) {
            // Prepare SQL for these 10 terms
            $event_description = json_encode($terms);
            $sql = "INSERT INTO bg_historic_eventdata (type, event_year, event_month, event_day, source, create_dt, modify_dt, event_description) VALUES ('slang_words', $era, 1, 1, 'alphadictionary.com',  now(), now(), ?);";
            
            $stmt = $database->prepare($sql);
            $stmt->execute([$event_description]);

            // Clear the terms for the next batch
            $terms = [];
        }
    }

    // If there are remaining terms less than 10
    if (count($terms) > 0) {
        $event_description = json_encode($terms);
        $sql = "INSERT INTO bg_historic_eventdata (type, event_year, event_month, event_day, source, create_dt, modify_dt, event_description) VALUES ('slang_words', $era, 1, 1, 'alphadictionary.com', now(), now(), ?);";
        
        $stmt = $database->prepare($sql);
        $stmt->execute([$event_description]);
    }

echo "$era<br>
";

}

?>
