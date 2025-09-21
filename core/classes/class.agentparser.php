<?php

require_once $dir['vendor'].'/autoload.php'; 

class AgentParser {
    private $parser;

    /**
     * Get all parsed user agent details as an array after parsing the provided user agent string.
     *
     * @param string $userAgent The user agent string to parse.
     * @return array
     */

     
     
    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function getAllDetails($userAgent) {
        $this->parser = new WhichBrowser\Parser($userAgent);

        return [
            'browser' => $this->getBrowser(),
            'browser_icon' => $this->getBrowserIcon(),
            'browser_icontag' => $this->getIconTag($this->getBrowserIcon()),
            'os' => $this->getOS(),
            'os_icon' => $this->getOSIcon(),
            'os_icontag' => $this->getIconTag($this->getOSIcon()),
            'deviceType' => $this->getDeviceType(),
            'deviceType_icon' => $this->getDeviceTypeIcon(),
            'deviceType_icontag' => $this->getIconTag($this->getDeviceTypeIcon()),
            'deviceModel' => $this->getDeviceModel()
        ];
    }


    
    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    private function getBrowser() {
        $browser = $this->parser->browser;
        $browserName = $browser->name ?? 'Unknown Browser';
        $browserVersion = $browser->version ? $browser->version->toString() : '';
        return $browserName . " " . $browserVersion;
    }


    
    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    private function getBrowserIcon() {
        $browserName = strtolower($this->parser->browser->name ?? 'unknown');
        return match($browserName) {
            'chrome' => 'bi-browser-chrome',
            'firefox' => 'bi-firefox',
            'safari' => 'bi-safari',
            'edge' => 'bi-edge',
            default => 'bi-globe'
        };
    }


    
    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    private function getOS() {
        $os = $this->parser->os;
        $osName = $os->name ?? 'Unknown OS';
        $osVersion = $os->version ? $os->version->toString() : '';
        return $osName . " " . $osVersion;
    }


    
    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    private function getOSIcon() {
        $osName = strtolower($this->parser->os->name ?? 'unknown');
        return match($osName) {
            'windows' => 'bi-windows',
            'macos', 'os x', 'ios' => 'bi-apple',
            'android' => 'bi-android',
            'linux' => 'bi-linux',
            default => 'bi-laptop'
        };
    }


    
    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    private function getDeviceType() {
        return ucwords($this->parser->device->type) ?? 'Unknown Device Type';
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    private function getDeviceTypeIcon() {
        $deviceType = strtolower($this->parser->device->type ?? 'unknown');
         return match($deviceType) {
            'mobile' => 'bi-phone',
            'tablet' => 'bi-tablet',
            'desktop' => 'bi-display',
            default => 'bi-laptop'
        };
    }

    // private function getDeviceModel() {
    //     return $this->parser->device->model ?? 'Unknown Model';
    // }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
        private function getDeviceModel() {
        $model = $this->parser->device->model;
        if (empty($model)) {
            // Fallbacks based on OS if model isn't detected
            if (strpos(strtolower($this->parser->os->name), 'android') !== false) {
                return 'Android Device';
            } elseif (strpos(strtolower($this->parser->os->name), 'ios') !== false) {
                return 'iOS Device';
            }
        }
        return $model ?? 'Unknown Model';
    }

    
    /**
     * Generate an HTML icon tag from an icon class name.
     *
     * @param string $iconClass The icon class name.
     * @return string
     */
    private function getIconTag($iconClass) {
        return "<i class='bi $iconClass'></i>";
    }
}
/* 
// Example usage:
$userAgent = "Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36";
$agentParser = new AgentParser();
$details = $agentParser->getAllDetails($userAgent);

echo "Browser: " . $details['browser'] . " " . $details['browser_icontag'] . "<br>";
echo "OS: " . $details['os'] . " " . $details['os_icontag'] . "<br>";
echo "Device Type: " . $details['deviceType'] . " " . $details['deviceType_icontag'] . "<br>";
echo "Device Model: " . $details['deviceModel'] . "<br>"; */