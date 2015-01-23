<?php namespace Bedard\Shop\Traits;

use Response;
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
     * Returns a response message
     * @param   string  $message
     * @return  array
     */
    private function response($message)
    {
        return [
            'message' => $message
        ];
    }

    /**
     * Returns a "smart error" repsonse;
     * @param   string  $message
     * @return  array
     */
    private function failedResponse($message)
    {
        $this->setStatusCode(406);
        return $this->response($message);
    }

}

// <daftspunk> scottbedard: Smart error is easy, just return error 406
// <kaybee> lol
// <daftspunk> return Response::make($responseContents, 406);
// <daftspunk> chewyknows: It is executed after, as late as possible
// <scottbedard> Then i can catch that with data-request-error, correct?
// <daftspunk> OctoberFan: Try Search::make()
// <daftspunk> scottbedard: I *think* so yeah
// <scottbedard> and can I pass an error message along with it? so that I can respond differently based on what went wrong?
// <OctoberFan> ... rage lol that works
// <OctoberFan> daftspunk ty
// <scottbedard> I know how to pass messages to the success handler, but I can't figure out how to pass them to the error handler
// <daftspunk> scottbedard: Yes, via $responseContents['X_OCTOBER_ERROR_MESSAGE'] = "Doodie";
// <scottbedard> Ahh, perfect
// <scottbedard> thank you dafts :)
// <daftspunk> Welcome