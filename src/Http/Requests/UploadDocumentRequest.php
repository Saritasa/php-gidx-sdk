<?php

namespace GidxSDK\Http\Requests;

use GidxSDK\Enums\GidxCategoryTypes;

/**
 * Gidx upload document request.
 *
 * @property-read int $category_type
 */
class UploadDocumentRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return mixed[]
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|max:10240',
            'category_type' => 'required|integer|in:' . implode(',', GidxCategoryTypes::getConstants()),
        ];
    }

    /**
     * Define attributes.
     *
     * @return mixed[]
     */
    public function attributes(): array
    {
        return [
            'file' => 'The document file',
        ];
    }
}
