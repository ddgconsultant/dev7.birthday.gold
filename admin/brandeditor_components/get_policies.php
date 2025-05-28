<?php include ($_SERVER['DOCUMENT_ROOT'].'/core/site-controller.php'); 


$counter=[];
foreach(['companies', 'terms', 'privacy', 'errors', 'inserted'] as $key){
    $counter[$key]=0;
}
// Turn off output buffering
ob_implicit_flush(true);
ob_end_flush();
$processingarray=[];
// Fetch companies from bg_companies
#$query = "SELECT company_id, company_url, info_url, signup_url FROM bg_companies where `status`='finalized'";
$query="SELECT
c.company_id, 
c.company_url, 
c.info_url, 
c.signup_url
FROM
bg_companies AS c	
where 	c.company_id not in (select company_id from bg_company_attributes where `type`='url' and `grouping` ='policies')
and 	c.`status` in ('finalized' , 'new', 'new2', 'active')
";
$stmt = $database->prepare($query);
$stmt->execute();

$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Possible variations for "Terms and Conditions" and "Privacy Policy"
$terms_variations = ['Terms and Conditions', 'Terms & Conditions', 'Terms of Service', 'Terms of Use', 'User Agreement', 'Terms'];
$privacy_variations = ['Privacy Policy', 'privacy-policy', 'Privacy Statement', 'Privacy Notice', 'Privacy'];

echo "<b>Starting script at " . date("Y-m-d H:i:s") . "</b><br>";
flush();

foreach ($companies as $company) {
    $counter['companies']++;
    $company_id = $company['company_id'];
    $urls = [$company['company_url'], $company['info_url'], $company['signup_url']];

   // Initialize variables to hold URLs
   $terms_url = '';
   $privacy_url = '';
   $base_url = ''; // Initialize variable to hold base URL


    echo "<h3>Processing company ID: $company_id</h3>";
    flush();

    foreach ($urls as $url) {
        if (!$url) continue; // Skip if the URL is null or empty


                // Extract the base URL
                $parsed_url = parse_url($url);
                $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];

                
        echo "Checking URL: $url<br>";
        flush();

       // Fetch the HTML content of the URL using cURL
       $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);       
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       $html_content = curl_exec($ch);
       $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
       curl_close($ch);

      

         

       if ($http_code != 200) {
           echo "Failed to fetch URL: $url (HTTP Code: $http_code)<br>";
           flush();
           $counter['errors']++;
           continue;
       }
       libxml_use_internal_errors(true); // Suppress HTML parsing errors and warnings
       
       // Parse the HTML content
       $dom = new DOMDocument();
       if (@$dom->loadHTML($html_content) === false) {
           echo "Failed to parse HTML content for URL: $url<br>";
           flush();
           
            
            libxml_clear_errors(); // Clear the error buffer
          
           $xpath = new DOMXPath($dom);
           continue;
          }


        // Loop through all anchor tags
        foreach ($dom->getElementsByTagName('a') as $anchor) {
            $link_text = $anchor->nodeValue;
            $link_href = $anchor->getAttribute('href');

            // Check for Terms variations
            if (!$terms_url) {
                foreach ($terms_variations as $term) {
                    if (stripos($link_text, $term) !== false) {
                        $terms_url = $link_href;
                        echo "Found terms URL: $terms_url<br>";
                        $counter['terms']++;
                        flush();
                        break;
                    }
                }
            }

            // Check for Privacy variations
            if (!$privacy_url) {
                foreach ($privacy_variations as $privacy) {
                    if (stripos($link_text, $privacy) !== false) {
                        $privacy_url = $link_href;
                        echo "Found privacy URL: $privacy_url<br>";
                        flush();
                        $counter['privacy']++;
                        break;
                    }
                }
            }

            // If both URLs are found, break the loop
            if ($terms_url && $privacy_url) {
                break;
            }
        }

        // If both URLs are found, break the loop
        if ($terms_url && $privacy_url) {
            break;
        }
    }

    // Insert URLs into bg_company_attributes
    if ($terms_url || $privacy_url) {
        $processingarray['terms']=$terms_url;
        $processingarray['privacy']=$privacy_url;

        foreach ($processingarray as $policy => $url){
if (!empty($url)) {
    if (strpos($url, '://')===true) $finalurl=$url;
        else
        $finalurl=$base_url.'/'.$url;
        $insert_query = "INSERT INTO bg_company_attributes (company_id, `type`, `name`, description, `status`, create_dt, modify_dt, `grouping`, start_dt) 
        VALUES (?, 'url', ?, ?, 'active', now(), now(), 'policies', now())";
        $stmt= $database->prepare($insert_query);
        $stmt->execute([$company_id, $policy, $finalurl]);
        echo "Inserted URLs for company ID: $company_id at " . date("Y-m-d H:i:s") . "<br>";
        flush();
        $counter['inserted']++;
        }
    }
    }
}



echo "<b>Script completed at " . date("Y-m-d H:i:s").'</b>';
print_r($counter);
?>
