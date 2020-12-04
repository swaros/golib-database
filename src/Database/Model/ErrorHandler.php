<?php


namespace golibdatabase\Database\Model;


use closure;

interface ErrorHandler
{
    /**
     * set the the errorhandler
     * @param closure $handler
     * @param int $error_type
     * @return mixed
     */
    public function setErrorHandler(closure $handler, int $error_type = E_USER_NOTICE);

    /**
     * handle the error
     * @param string $errorMessage
     * @param int $error_type
     * @return mixed
     */
    public function triggerError(string $errorMessage, int $error_type = E_USER_NOTICE);

    /**
     * set a errorhandler they is responsible
     * for different errors
     * @param closure $handler
     * @param int ...$errorTypes
     * @return mixed
     */
    public function setErrorHandlers(closure $handler, int ...$errorTypes);


}