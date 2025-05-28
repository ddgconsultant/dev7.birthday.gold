<?PHP
// /myaccount/module_enrollment/company_details.inc
function render_company_details($item_company, $layout = 'list') {
    global $rewardiconlist, $rewardicon, $apponlyicon, $benefiticon, $setuplink,
           $enroll_label_default, $have_label_default, $randomRating, $website, $qik;
    
    $companylink = $item_company['signup_url'] == $website['apponlytag'] 
        ? $item_company['company_url'] 
        : '/brand-details?cid='.$qik->encodeId($item_company['company_id']);

    $output = '<div class="' . ($layout == 'grid' ? 'p-3' : 'row g-0 h-100') . '">';
    
    // Reward icons
    $output .= '<div class="d-flex gap-2 flex-wrap mb-3">';
    foreach ($rewardiconlist as $icon) {
        if (!empty($rewardicon[$icon])) {
            $output .= $rewardicon[$icon];
        }
    }
    $output .= '</div>';

    // Company name and description
    $output .= sprintf('
        <h5 class="mb-2">
            <a href="%s" target="_signup">%s</a>%s
        </h5>
        <p class="fw-bold">%s</p>',
        $companylink,
        trim($item_company['company_name']),
        $setuplink,
        $item_company['spinner_description']
    );

    // Ratings and icons
    $output .= sprintf('
        <div class="flex-1 d-flex align-items-end fw-semi-bold fs-10">
            <span class="me-1 text-900">%s</span>
            <div>%s</div>
            <span class="ms-2 me-2 text-secondary">(%s reviews)</span>
            %s %s
        </div>',
        $randomRating,
        $display->starrating($randomRating),
        rand(10, 9999),
        $apponlyicon,
        $benefiticon
    );

    // Action buttons
    $output .= render_action_buttons($item_company);

    return $output . '</div>';
}
