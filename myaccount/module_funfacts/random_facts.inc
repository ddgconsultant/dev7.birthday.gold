<?PHP
# DISPLAY RANDOM FACTS CARD
#-------------------------------------------------------------------------------
$pageoutput='';
$cardevent=[];
// Generate a random card
if (empty($choices)) $choices = ['topsong', 'historic_event', 'slang_words', 'inventions', 'generations'];
#$choices = ['generations'];
$key = array_rand($choices);
$randomcard = $choices[$key];
#breakpoint($randomcard);
switch ($randomcard) {
case 'topsong':
$output = $app->getTopSong($current_user_data['birthdate']);
if (!empty($output['status'])) {
$cardevent['title']='Hottest Song in '.$output['findYear'];
$cardevent['icon']='music.gif';
$cardevent['content']=$output['message'];

}

break;
##--------------------------------------
case 'historic_event':
$output=false;
$output = $app->getHistoricalEvents($current_user_data['birthdate']);
#breakpoint($output);

// If no events were found, set a default message
if (empty($output)) {
$eventcontent = '<div id="events"><p class="fw-bold">No historical events found for your birthdate.</p>
<p>Your birth should have definiately made history!</p></div>';
} else {
// Prepare the output string
$eventcontent = '<div class="" id="events">
<strong>'.$output['comment'].'</strong> <ul>';
foreach ($output['data'] as $event) {
// Check if year, month, and day keys exist in the array and they are not empty
#echo print_r($event, 1).'<br>';

$date = new DateTime($event['year'].'-'.$event['month'].'-'.$event['day']);
$formatted_date = $date->format('M jS');
$formatted_date = "On $formatted_date";
$eventcontent .= "<li>".$formatted_date.', '.$event['event']."</li>";

}
$eventcontent .= '</ul></div>';
}
$cardevent['title']='Events That Occured';
$cardevent['icon']='event.gif';
$cardevent['content']=$eventcontent;

break;
##--------------------------------------
case 'slang_words':
$events = $app->getHistoricalEventsFromDatabase($birthdates['decade_1_1'], 'slang_words');
if (empty($events)) {
// If no events were found, set a default message
$eventcontent = '<div id="events"><p class="fw-bold">Hmmm... No Slang Words data found.</p></div>';
} else {
 # $event = $events[0];
# breakpoint($events);
 $event = $events[array_rand($events)];

 // Assuming $event['event'] contains the JSON-encoded slang words data.
 $slangData = json_decode($event['event'], true);
 #breakpoint($slangData);
 if ($slangData) {
   $providertag='';
   if(!empty($event['source'])) $providertag=' <i class="ps-3 fw-normal">brought to you by: '.$event['source'].'</i>';
   // Get four random key-value pairs from the slang data.
   $randomSlangPairs = array_rand($slangData, 4);
   $eventcontent = '';
   // Loop through the random pairs and format them.
   foreach ($randomSlangPairs as $key) {
     $term = $slangData[$key]['term'];
     $definition = $slangData[$key]['definition'];
     $formattedPair = "<b>$term</b> $definition";
     $eventcontent .= '<p class="mb-1 pb-0">'.$formattedPair . "</p>";
   }
 } 
}
$cardevent['title']=$event['year']."'s Slang".' '.$providertag;
$cardevent['icon']='speech-bubble.gif';
$cardevent['content']=$eventcontent;


 break;

##--------------------------------------
case 'inventions':
 # breakpoint($birthdates);
 $eventcontent='';
 $providertag='';
 $events = $app->getHistoricalEventsFromDatabase($current_user_data['birthdate'], 'inventions');
 if (empty($events)) {
 // If no events were found, set a default message
 $eventcontent = '<div id="events"><p class="fw-bold">Hmmm... No Inventions data found.</p></div>';
 } else {
   #breakpoint($events);
   $eventcontent='<ul>';
   foreach ($events as $event){
   $eventcontent .= '<li>'.$event['event']."</li>";
   }
   $eventcontent.='</ul>';
  }
   if(!empty($event['source'])) $providertag=' <i class="ps-3 fw-normal">brought to you by: <a href="//'.$event['source'].'">Wikipedia</a></i>';
 
$cardevent['title']=$event['year']."'s inventions".' '.$providertag;
$cardevent['icon']='implementation.gif';
$cardevent['content']=$eventcontent;

   break;
##--------------------------------------
case 'generations':
# breakpoint($birthdates);
$eventcontent='';
$providertag='';
$events = $app->getHistoricalEventsFromDatabase($current_user_data['birthdate'], 'generations');
$eventcontent = '';
$eventtitle = '';  // Initialize to avoid undefined variable error
$providertag = '';

if (empty($events)) {
    $eventcontent = '<div id="events"><p class="fw-bold">Hmmm... No generation data found.</p></div>';
} else {
    // Ensure $events[0]['event'] is an array before iterating
    if (is_array($events[0]['event'])) {
        $eventcontent = '<ul>';
        foreach ($events[0]['event'] as $eventkey => $eventval) {
            if ($eventkey == 'Original') {
                $eventtitle = $eventval ?? '';  // Fallback to empty string if null
            } else {
                $eventcontent .= '<li><span class="fw-bold">' . $eventkey . "</span>: " . $eventval . "</li>";
            }
        }
        $eventcontent .= '</ul>';
    }
}

// Handle the source provider tag
if (!empty($events) && !empty($events['source'])) {
    $providertag = ' <i class="ps-3 fw-normal">brought to you by: <a href="//' . $events['source'] . '">Wikipedia</a></i>';
}

$cardevent['title'] = 'You are part of ' . $eventtitle . ' ' . $providertag;
$cardevent['icon'] = 'aging.gif';
$cardevent['content'] = $eventcontent;
   break;

}


$funfactrecord= '

<!-- '.$randomcard.' card -->
<div class="card h-100">
<div class="card-body">
<div class="card-title">'.$cardevent['title'].'</div>
<div class="d-flex align-items-start">
<div class="avatar avatar-2xl me-3">
<img src="/public/images/icon/'.$cardevent['icon'].'" style="float: right; width:48px; height: 48px;">
</div>
<div>
' .$cardevent['content']. '
</div>
</div>
</div>
</div>

';
## ^^ end random card