<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileAvatarUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * SVG is intentionally not allowed: it can embed scripts, while raster
     * formats are inert when rendered in an image tag.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
        ];
    }
}
