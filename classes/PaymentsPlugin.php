<?php 
namespace _29kPayments;
require_once __DIR__ . '/BasePlugin.php';
class PaymentsPlugin extends BasePlugin {
    private static $instance = null;
    private function __construct() {}
    public static function getPluginInstance(): PaymentsPlugin {
        if(self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
?>