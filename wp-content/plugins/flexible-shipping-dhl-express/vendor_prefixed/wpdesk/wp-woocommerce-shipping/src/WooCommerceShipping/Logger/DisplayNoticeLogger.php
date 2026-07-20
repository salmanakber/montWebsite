<?php

/**
 * WooCommerce Logger: WooCommerceLogger class.
 *
 * @package WPDesk\WooCommerceShipping
 */
namespace DhlVendor\WPDesk\WooCommerceShipping\Logger;

use DhlVendor\Psr\Log\LoggerInterface;
use DhlVendor\Psr\Log\LoggerTrait;
use DhlVendor\Psr\Log\LogLevel;
/**
 * Wants to show all logs using wc_add_notice
 */
class DisplayNoticeLogger implements LoggerInterface
{
    const WC_NOTICE = 'notice';
    const SERVICE_NAME = 'service_name';
    const DATA = 'data';
    const INSTANCE_ID = 'instance_id';
    use LoggerTrait;
    /**
     * @var string
     */
    private $service_name;
    /**
     * @var string
     */
    private $instance_id;
    /**
     * DisplayLogs constructor.
     *
     * @param string $service_name .
     * @param int $instance_id .
     */
    public function __construct(string $service_name, $instance_id)
    {
        $this->service_name = $service_name;
        $this->instance_id = (int) $instance_id;
    }
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level   Level.
     * @param string $message Message.
     * @param array  $context context.
     *
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $this->show($message, $context, self::WC_NOTICE);
    }
    /**
     * Format message.
     *
     * @param string $message Message.
     *
     * @return string
     */
    private function format_value($message): string
    {
        if (!is_string($message)) {
            $message = print_r($message, \true);
        } else if ($this->is_json($message)) {
            $message = json_encode(json_decode($message), \JSON_PRETTY_PRINT);
        } else if ($this->is_xml($message)) {
            $message = $this->format_xml($message);
        }
        return trim($message);
    }
    private function format_xml(string $xml): string
    {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = \false;
        $dom->formatOutput = \true;
        $dom->loadXML($xml);
        return $dom->saveXML();
    }
    /**
     * Check if string is XML.
     *
     * @param string $string String.
     *
     * @return bool
     */
    private function is_xml(string $string): bool
    {
        $doc = @simplexml_load_string($string);
        return $doc !== \false;
    }
    /**
     * Check if string is JSON.
     *
     * @param string $string String.
     *
     * @return bool
     */
    private function is_json(string $string): bool
    {
        json_decode($string);
        return json_last_error() === \JSON_ERROR_NONE;
    }
    /**
     * Show notices
     *
     * @param string $message Message.
     * @param array  $context context.
     * @param string $type    Type.
     *
     * @return void
     */
    private function show($message, array $context, $type)
    {
        $message = sprintf('%1$s: %2$s', $this->service_name, $message);
        $dump = '';
        foreach ($context as $label => $value) {
            $value = $this->format_value($value);
            ob_start();
            include __DIR__ . '/view/display-notice-context-single-value.php';
            $dump .= trim(ob_get_clean());
        }
        $dump .= sprintf('<br/><div><i>%1$s</i></div>', __('This notice is visible only for Administrators.', 'flexible-shipping-dhl-express'));
        $message = trim($message . $dump);
        if (!wc_has_notice($message, $type)) {
            wc_add_notice($message, $type, [self::SERVICE_NAME => $this->service_name, self::INSTANCE_ID => $this->instance_id]);
        }
    }
}
