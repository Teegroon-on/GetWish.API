<?php

namespace App\Http\Requests\Wish;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class WishUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function failedValidation(Validator $validator)
    {
        throw (new ValidationException($validator))
            ->errorBag($this->errorBag)
            ->status(400);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'nullable|max:100',
            'description' => 'nullable|max:150',
            'link' => 'nullable|max:200',
            'wishlist' => 'nullable|numeric|min:1',
            'deleteImages' => 'nullable|array|max:5',
            'deleteImages.*' => 'required|numeric',
            'addImages' => 'nullable|array|max:5',
            'addImages.*' => 'required|string',
        ];
    }
}
