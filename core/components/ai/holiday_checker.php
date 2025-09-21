<?PHP

$addClasses[] = 'ai';
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');


// modules/ai/holiday_checker.php
$moduleuse_ai_engine = 'anthropic_text';
$moduleuse_ai_backupengine = 'openai_text';

class HolidayChecker {
    private $ai;
    
    public function __construct($primary_engine, $backup_engine = null) {
        $this->ai = AI::getInstance();  // Already configured
        $this->primary_engine = $primary_engine;
        $this->backup_engine = $backup_engine;
    }
    
    public function check(): bool {
        $prompt = "Tomorrow is what date? Is it a US Federal Holiday?...";
        try {
            $response = $this->ai->ask($prompt, $this->primary_engine);
            return $this->ai->parseResponse($response, 'boolean');
        } catch (Exception $e) {
            if ($this->backup_engine) {
                try {
                    $response = $this->ai->ask($prompt, $this->backup_engine);
                    return $this->ai->parseResponse($response, 'boolean');
                } catch (Exception $e) {
                    error_log("Backup AI check failed: " . $e->getMessage());
                }
            }
            return false;
        }
    }
}