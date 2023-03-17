<?php

namespace App\Http\Requests;

use Illuminate\Http\Request;

class BaseRequest extends Request
{
    /**
     * Set wants only json request.
     *
     * @return boolean
     */
    public function wantsJson()
    {
        return true;
    }

    /**
     * Set expects only json request.
     *
     * @return boolean
     */
    public function expectsJson()
    {
        return true;
    }
}
