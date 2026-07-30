<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class BookStoreRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'string', 'unique:books,isbn'],
            'published_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['integer', 'exists:genres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => '登録者は必須です',
            'user_id.integer' => '登録者IDは整数で入力してください',
            'user_id.exists' => '指定された登録者は存在しません',
            'title.required' => 'タイトルは必須です',
            'title.max' => 'タイトルは255文字以内で入力してください',
            'author.required' => '著者名は必須です',
            'author.max' => '著者名は255文字以内で入力してください',
            'isbn.required' => 'ISBNは必須です',
            'isbn.digits' => 'ISBNは13桁で入力してください',
            'isbn.unique' => 'このISBNは既に使用されています',
            'published_at.required' => '出版日は必須です',
            'published_date.date' => '出版日は有効な日付形式で入力してください',
            'image_url.url' => '画像URLは有効なURL形式で入力してください',
            'image_url.max' => '画像URLは255文字以内で入力してください',
            'genres.required' => 'ジャンルは1つ以上選択してください',
            'genres.min' => 'ジャンルは1つ以上選択してください',
            'genres.*.exists' => '選択したジャンルは存在しません',
        ];
    }
}
