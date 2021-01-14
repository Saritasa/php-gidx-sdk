<?php

namespace GidxSDK\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class Request extends FormRequest
{
    /**
     * Authorizes usage of this request.
     *
     * @return boolean
     */
    public function authorize(): bool
    {
        return true;
    }
}
