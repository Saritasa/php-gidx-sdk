<?php

namespace GidxSDK\Http\Requests;

/**
 * Request for withdrawal an amount api.
 *
 * @property-read int $amount
 */
class WithdrawPreviewRequest extends Request
{
    private const AMOUNT = 'amount';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return mixed[]
     */
    public function rules(): array
    {
        return [
            self::AMOUNT => 'int|required|min:1',
        ];
    }
}
