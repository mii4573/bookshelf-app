<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // まずルートパラメータから更新対象のジャンルIDを取得（新規登録時は null）
        $genreId = $this->route('genre') ? $this->route('genre')->id : null;

        // 配列を1つだけ返します
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('genres', 'name')->ignore($genreId),
            ],
        ];
    }

    /**
     * エラーメッセージの定義
     */
    public function messages(): array
    {
        return [
            'name.required' => 'ジャンル名は必須です',
            'name.max' => 'ジャンル名は255文字以内で入力してください',
            'name.unique' => 'このジャンル名は既に使用されています',
        ];
    }
}
