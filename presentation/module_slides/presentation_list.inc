<?PHP

##  ========================================================================================================================================================
##  must conform to this format
##  You can provide:  $sectionClass, $sectionTag, $content, $speechScript, $section_suffix
##  ========================================================================================================================================================
/*
<!-- Slide #' .   $counter . ' - ID:' . $slide['id'] . ' ====================================================================== -->' . $section_prefix . '
<section class="' . $sectionClass . '"  id="' . $sectionTag . '"
data-speech="' . str_replace(array("\r", "\n"), '', $speechScript) . '">
' . $content . '
</section>
' . $section_suffix;
*/

#breakpoint ('hello');
$content='
        <div class="row justify-content-center">
        <div class="col-12">
            <ul class="flexblock specs">';
#-------------------------------------------------------------------------------
# GET SLIDES
#-------------------------------------------------------------------------------
$sql = "SELECT dp.presentation, dp.`grouping`, bs.content, 
               (SELECT COUNT(*) - 1 
                FROM bg_slides 
                WHERE presentation = dp.presentation 
                  AND `grouping` = dp.grouping 
                  AND status = 'active') AS slide_cnt 
        FROM (SELECT DISTINCT presentation, `grouping` 
              FROM bg_slides 
              WHERE status = 'active' 
                AND `grouping` NOT IN ('presentationlist')) AS dp
        JOIN bg_slides bs 
          ON bs.presentation = dp.presentation 
         AND bs.grouping = dp.grouping 
        WHERE bs.status = 'active'
        AND bs.id = (SELECT MIN(id) FROM bg_slides WHERE presentation = dp.presentation AND `grouping` = dp.grouping)
        ORDER BY dp.presentation ASC";
$stmt = $database->prepare($sql);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$presentationlist = $results;
#breakpoint ($presentationlist);
foreach ($presentationlist as $presrow) {
    $presrow_content = $presrow['content'] ?? '';
    $slidecount = '<h2 class="text-muted">' . $presrow['slide_cnt'] . ' slides</h2>';
    if (empty($presrow['slide_cnt'])) $slidecount='';
    if (strpos($presrow_content, '!authentication_required') !== false) {
        $icon = '<i class="bi bi-file-earmark-lock2-fill text-warning"></i>';
     } else {
        $icon = '<i></i>';
    }
    $content .= '<li>
    <div style="display: flex; align-items: center; justify-content: space-between;">
    <div class="col-8">
      <a href="/presentation/' . $presrow['grouping'] . '"><h1 class="fw-bold">' . $presrow['presentation'] . '</h1></a></div>
      <div class="col-3"> ' . $slidecount . '</div>
     <div class="col-1"> ' . $icon . '</div>
    </div>
  </li>
  ';
}

$content .= '</ul>
              </div>
            </div>
          </div>';