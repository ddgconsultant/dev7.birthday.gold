<?php
// Direct PHP test of bulk-action
$_SERVER['REQUEST_METHOD'] = 'POST';

// Set up the JSON input
$testData = [
    '_token' => 'test-token',
    'action' => 'mark-unread',
    'messageIds' => ['DLVR6S-OVMNO-VQTMO-QWTM6M'],
    'server' => 'march01:1072'
];

// Simulate JSON input
$jsonInput = json_encode($testData);

// Override file_get_contents to return our test data
stream_wrapper_unregister("php");
stream_wrapper_register("php", "MockPhpStream");

class MockPhpStream {
    protected $content;
    protected $position = 0;
    
    function stream_open($path, $mode, $options, &$opened_path) {
        if ($path == 'php://input') {
            $this->content = $GLOBALS['jsonInput'];
            return true;
        }
        return false;
    }
    
    function stream_read($count) {
        $ret = substr($this->content, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }
    
    function stream_eof() {
        return $this->position >= strlen($this->content);
    }
    
    function stream_stat() {
        return [];
    }
}

// Include the bulk-action file
include(__DIR__ . '/bulk-action.php');