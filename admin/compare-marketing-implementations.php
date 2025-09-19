<?php
// Include site controller
$addClasses[] = 'mail';
$addClasses[] = 'marketing';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');

// Check if user is staff
if (!$account->isstaff()) {
    header('Location: /');
    exit;
}

// Both implementations are now the same (RPN integrated into class.marketing.php)
$simpleMarketing = $marketing; // Now uses RPN implementation
$rpnMarketing = $marketing; // Same RPN implementation

// Test cases for comparison
$testCases = [
    [
        'name' => 'Simple: All Users',
        'tokens' => [['type' => 'all']]
    ],
    [
        'name' => 'Simple: Gender = Female',
        'tokens' => [['type' => 'gender', 'value' => 'female']]
    ],
    [
        'name' => 'Simple: State = CA',
        'tokens' => [['type' => 'state', 'value' => 'CA']]
    ],
    [
        'name' => 'AND Operation: Gender = Male AND State = CA',
        'tokens' => [
            ['type' => 'gender', 'value' => 'male'],
            ['type' => 'operator', 'value' => 'AND'],
            ['type' => 'state', 'value' => 'CA']
        ]
    ],
    [
        'name' => 'OR Operation: State = CA OR State = NV',
        'tokens' => [
            ['type' => 'state', 'value' => 'CA'],
            ['type' => 'operator', 'value' => 'OR'],
            ['type' => 'state', 'value' => 'NV']
        ]
    ]
];

// Include header
$theme = 'dark';
$pageTitle = 'Compare Marketing Implementations';
include($_SERVER['DOCUMENT_ROOT'] . '/core/components/v7/bg_headerv7.inc');
?>

<div class="container-fluid mb-5">
    <div class="content-header-dark">
        <div class="container">
            <div class="row align-items-center py-4">
                <div class="col-auto">
                    <i class="fas fa-balance-scale text-white" style="font-size: 2.5rem;"></i>
                </div>
                <div class="col">
                    <h1 class="text-white mb-0">Compare Marketing Implementations</h1>
                    <p class="text-white-50 mb-0">Side-by-side comparison of simple vs RPN implementations</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Implementation Comparison</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 40%">Test Case</th>
                                        <th style="width: 20%" class="text-center">Current Implementation<br><small class="text-muted">(class.marketing.php)</small></th>
                                        <th style="width: 20%" class="text-center">RPN Implementation<br><small class="text-muted">(claudecode/marketing)</small></th>
                                        <th style="width: 20%" class="text-center">Match?</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($testCases as $test): ?>
                                    <?php
                                    // Test current implementation
                                    $simpleStart = microtime(true);
                                    try {
                                        $simpleCount = $simpleMarketing->getRecipientCount($test['tokens']);
                                        $simpleTime = round((microtime(true) - $simpleStart) * 1000, 2);
                                        $simpleError = false;
                                    } catch (Exception $e) {
                                        $simpleCount = 0;
                                        $simpleTime = 0;
                                        $simpleError = $e->getMessage();
                                    }
                                    
                                    // Test RPN implementation
                                    $rpnStart = microtime(true);
                                    try {
                                        $rpnCount = $rpnMarketing->getRecipientCount($test['tokens']);
                                        $rpnTime = round((microtime(true) - $rpnStart) * 1000, 2);
                                        $rpnError = false;
                                    } catch (Exception $e) {
                                        $rpnCount = 0;
                                        $rpnTime = 0;
                                        $rpnError = $e->getMessage();
                                    }
                                    
                                    $match = ($simpleCount === $rpnCount && !$simpleError && !$rpnError);
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($test['name']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <code><?php echo htmlspecialchars(json_encode($test['tokens'])); ?></code>
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($simpleError): ?>
                                                <span class="badge bg-danger">Error</span>
                                                <br><small class="text-danger"><?php echo htmlspecialchars(substr($simpleError, 0, 50)); ?></small>
                                            <?php else: ?>
                                                <span class="badge bg-primary fs-6"><?php echo number_format($simpleCount); ?></span>
                                                <br><small class="text-muted"><?php echo $simpleTime; ?>ms</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($rpnError): ?>
                                                <span class="badge bg-danger">Error</span>
                                                <br><small class="text-danger"><?php echo htmlspecialchars(substr($rpnError, 0, 50)); ?></small>
                                            <?php else: ?>
                                                <span class="badge bg-primary fs-6"><?php echo number_format($rpnCount); ?></span>
                                                <br><small class="text-muted"><?php echo $rpnTime; ?>ms</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($match): ?>
                                                <span class="badge bg-success"><i class="fas fa-check"></i> Match</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning"><i class="fas fa-exclamation-triangle"></i> Mismatch</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0">Current Implementation</h5>
                            </div>
                            <div class="card-body">
                                <h6>Capabilities:</h6>
                                <ul>
                                    <li>✅ Basic AND/OR operations</li>
                                    <li>✅ All standard criteria types</li>
                                    <li>❌ No parentheses support</li>
                                    <li>❌ No NOT operator</li>
                                    <li>✅ Simple and straightforward</li>
                                </ul>
                                <h6>Best For:</h6>
                                <p>Simple filtering where all operators have the same precedence</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">RPN Implementation</h5>
                            </div>
                            <div class="card-body">
                                <h6>Capabilities:</h6>
                                <ul>
                                    <li>✅ Full parentheses support</li>
                                    <li>✅ NOT operator</li>
                                    <li>✅ Complex nested expressions</li>
                                    <li>✅ Proper operator precedence</li>
                                    <li>✅ More flexible queries</li>
                                </ul>
                                <h6>Best For:</h6>
                                <p>Complex filtering with nested conditions like: (A OR B) AND (C OR D)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Test Links</h5>
                    </div>
                    <div class="card-body">
                        <a href="/admin/test-rpn-marketing" class="btn btn-primary me-2">
                            <i class="fas fa-flask me-2"></i>Full RPN Test Suite
                        </a>
                        <a href="/myaccount/marketing/newsletter-edit" class="btn btn-secondary">
                            <i class="fas fa-envelope me-2"></i>Newsletter Editor (Current Implementation)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include($_SERVER['DOCUMENT_ROOT'] . '/core/components/v7/bg_footer.inc'); ?>