<?php namespace Bedard\Shop\Traits;

use Request;

trait AjaxResponderTrait
{

    /**
     * Determines if an ajax request is being made
     * @return  boolean
     */
    private function isAjax()
    {
        $handler = trim(Request::header('X_OCTOBER_REQUEST_HANDLER'));
        return preg_match('/^(?:\w+\:{2})?on[A-Z]{1}[\w+]*$/', $handler) && method_exists($this, $handler);
    }

    /**
     * Response builder
     * @param   string  $message    The message being sent back to the page
     * @param   boolean $result     True / false on if the request was ok
     * @param   boolean $error      Sets a 406 status code if something unexpected happened
     */
    private function response($message, $success = TRUE, $error = FALSE)
    {
        // Set the response message and status
        $response['message'] = $message;
        $response['success'] = $success;

        // If we have a actual error, set the status code to 406
        if ($error) $this->setStatusCode(406);

        return $response;
    }

}