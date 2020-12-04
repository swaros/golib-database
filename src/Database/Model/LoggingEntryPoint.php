<?php


namespace golibdatabase\Database\Model;

/**
 * Interface LoggingEntryPoint
 * @package golibdatabase\Database\Model
 *
 * these is just a simple interface to
 * define a entry point that is just there
 * and can be used to handle some information
 * while runtime.
 *
 * this should NOT replace a logger Interface
 * like Psr\Log\LoggerInterface
 *
 * instead it defines a method that can be used
 * different (testing, debugging), but also
 * it can be used to implement
 * a  Psr\Log\LoggerInterface ..
 */
interface LoggingEntryPoint
{
    const EMERGENCY = 10001;
    const ALERT = 10002;
    const CRITICAL = 10003;
    const ERROR = 10004;
    const WARNING = 10005;
    const NOTICE = 10006;
    const INFO = 10007;
    const DEBUG = 10008;

    /**
     * collect/logg Information
     *
     * this method differs from Psr\Log\LoggerInterface
     * and follows more then java like logging.
     *
     * so you should not need to fill a separate context.
     * just put the context in the log like
     *
     *    log(LoggingEntryPoint::DEBUG, "user object is loaded from cache", $userObject, $cache)
     *
     * @param int $logLevel
     * @param mixed ...$entries
     * @return mixed
     */
    function log(int $logLevel, ...$entries);
}