<?php


namespace golibdatabase\Database\Message;


use Closure;
use golibdatabase\Database\Model\LoggingEntryPoint;

/**
 * Trait MessageHandler
 * @package golibdatabase\Database\Message
 */
trait MessageHandler
{
    /**
     * handle all errors
     * @var closure[]
     */
    private array $errorHandler = [];

    /**
     * logs some entries
     * @param int $logLevel
     * @param mixed ...$entries
     * @return mixed|void
     */
    public function log(int $logLevel, ...$entries)
    {
        $message = [$this->debugLabel($logLevel)];

        foreach ($entries as $entry) {
            if (is_string($entry) || is_numeric($entry)) {
                $message[] = $entry;
            } elseif (is_bool($entry)) {
                $message[] = $entry ? "TRUE" : "FALSE";
            } else {
                $message[] = '['.print_r($entry, true).']';
            }
        }
        $this->triggerError(implode(" ", $message), $logLevel);
    }

    /**
     * creates a readable string of loglevel codes
     * for messages
     * @param int $logLevel
     * @return string
     */
    private function debugLabel(int $logLevel): string
    {
        switch ($logLevel) {
            case E_WARNING:
            case E_USER_WARNING:
            case LoggingEntryPoint::WARNING:
                return "WARNING";
            case E_NOTICE:
            case E_USER_NOTICE:
            case LoggingEntryPoint::NOTICE:
                return "NOTICE";
            case E_USER_ERROR:
            case E_ERROR:
            case LoggingEntryPoint::ERROR:
                return "ERROR";
            case LoggingEntryPoint::DEBUG:
                return "DEBUG";
            case LoggingEntryPoint::CRITICAL;
                return "CRITICAL";
            default:
                return "";
        }
    }

    /**
     * sets or overwrite the errorhandler depending on
     * error level/ error_type
     * @param Closure $handler
     * @param int $error_type
     */
    public function setErrorHandler(closure $handler, int $error_type = E_USER_NOTICE)
    {
        $this->errorHandler[$error_type] = $handler;
    }

    /**
     * executes the error handler
     * @param string $errorMessage
     * @param int $error_type
     */
    public function triggerError(string $errorMessage, int $error_type = E_USER_NOTICE)
    {
        if (array_key_exists($error_type, $this->errorHandler)) {
            $this->errorHandler[$error_type]->call($this, $errorMessage, $error_type);
        }
    }

    /**
     * set one callback to a list of error types.
     * it will be assigned to each error type so
     * it can be overwritten an time
     * @param Closure $handler
     * @param int ...$errorTypes
     */
    public function setErrorHandlers(closure $handler, int ...$errorTypes)
    {
        foreach ($errorTypes as $errorType) {
            $this->setErrorHandler($handler, $errorType);
        }
    }

    /**
     * set the native trigger_error function as errorhandler function
     * to the submitted error types
     * @param int ...$errorTypes
     */
    public function setPhpNativeTriggerErrorFor(int ...$errorTypes) {
        $this->setErrorHandlers(function (string $errorMessage, int $error_type) {
            trigger_error($errorMessage, $error_type);
        }, ...$errorTypes);
    }

    /**
     * shortcut for debug messages
     * @param mixed ...$entries
     */
    public function debug(...$entries) {
        $this->log(LoggingEntryPoint::DEBUG,...$entries);
    }
    /**
     * shortcut for info messages
     * @param mixed ...$entries
     */
    public function info(...$entries) {
        $this->log(LoggingEntryPoint::INFO,...$entries);
    }
    /**
     * shortcut for error messages
     * @param mixed ...$entries
     */
    public function error(...$entries) {
        $this->log(E_USER_ERROR,...$entries);
    }
    /**
     * shortcut for error warnings
     * @param mixed ...$entries
     */
    public function warn(...$entries) {
        $this->log(E_USER_WARNING,...$entries);
    }

}