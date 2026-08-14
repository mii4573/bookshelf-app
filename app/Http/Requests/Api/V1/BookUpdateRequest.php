<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookUpdateRequest extends FormRequest
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
        // ルートパラメータから対象書籍のIDを取得
        $book = $this->route('book');
        $bookId = is_object($book) ? $book->id : $book;

        return [
            
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'digits:13', Rule::unique('books', 'isbn')->ignore($bookId)],
            'published_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['integer', 'exists:genres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            
            'title.required' => 'タイトルは必須です',
            'title.max' => 'タイトルは255文字以内で入力してください',
            'author.required' => '著者名は必須です',
            'author.max' => '著者名は255文字以内で入力してください',
            'isbn.required' => 'ISBNは必須です',
            'isbn.digits' => 'ISBNは13桁で入力してください',
            'isbn.unique' => 'このISBNは既に使用されています',
            'published_date.required' => '出版日は必須です',
            'published_date.date' => '出版日は有効な日付形式で入力してください',
            'image_url.url' => '画像URLは有効なURL形式で入力してください',
            'image_url.max' => '画像URLは255文字以内で入力してください',
            'genres.required' => 'ジャンルは1つ以上選択してください',
            'genres.min' => 'ジャンルは1つ以上選択してください',
            'genres.*.exists' => '選択したジャンルは存在しません',
        ];
    }
}
