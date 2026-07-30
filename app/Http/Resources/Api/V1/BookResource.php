<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'published_date' => $this->published_date ? $this->published_date->format('Y-m-d') : null,
            'description' => $this->when(isset($this->description), $this->description),
            'image_url' => $this->image_url,
            'genres' => $this->genres->map(function ($genre) {
                return [
                    'id' => $genre->id,
                    'name' => $genre->name,
                ];
            }),
            // レビューがない場合は 0.0、ある場合は少数第一位までに丸めた float 型で返す
            'average_rating' => (float) round($this->reviews_avg_rating ?? $this->reviews()->avg('rating') ?? 0, 1),
            'reviews_count' => (int) ($this->reviews_count ?? $this->reviews()->count()),
            'reviews' => $this->whenLoaded('reviews', function () {
                return $this->reviews->map(function ($review) {
                    return [
                        'user_name' => $review->user->name,
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                        'created_at' => $review->created_at ? $review->created_at->format('Y-m-d') : null,
                    ];
                });
            }),
        ];
    }
}