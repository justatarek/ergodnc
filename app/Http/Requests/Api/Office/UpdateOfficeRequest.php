<?php

namespace App\Http\Requests\Api\Office;

use App\Models\Office;
use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateOfficeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('office'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'featured_image_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('images', 'id')->where('imageable_type', Office::class)->where('imageable_id', $this->route('office')->id),
            ],
            'title'             => ['sometimes', 'required', 'string', 'max:255'],
            'description'       => ['sometimes', 'required', 'string'],
            'lat'               => ['sometimes', 'required', 'numeric'],
            'lng'               => ['sometimes', 'required', 'numeric'],
            'address_line1'     => ['sometimes', 'required', 'string', 'max:255'],
            'address_line2'     => ['sometimes', 'nullable', 'string', 'max:255'],
            'price_per_day'     => ['sometimes', 'required', 'integer', 'min:100'],
            'monthly_discount'  => ['sometimes', 'nullable', 'integer', 'min:0', 'max:90'],
            'tags'              => ['sometimes', 'required', 'array', 'min:1'],
            'tags.*'            => ['required', 'integer'],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->filled('tags')) {
                    $ids = $this->validated('tags.*');
                    $tags = Tag::whereIn('id', $ids)->pluck('id');

                    foreach ($ids as $key => $value) {
                        if ($tags->doesntContain($value)) {
                            $validator->errors()->add(
                                key: "tags.$key",
                                message: 'Invalid tag',
                            );
                        }
                    }
                }
            }
        ];
    }
}
