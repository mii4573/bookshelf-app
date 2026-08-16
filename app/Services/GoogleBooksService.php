<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GoogleBooksService
{
    public function searchByIsbn(string $isbn): ?array
    {
        $cleanedIsbn = preg_replace('/[^0-9X]/i', '', $isbn);

        if (empty($cleanedIsbn)) {
            return null;
        }

        // 1. まず Google Books API で検索
        $googleData = $this->searchGoogleBooks($cleanedIsbn);
        if ($googleData) {
            return $googleData;
        }

        // 2. Google で取れなかった場合、openBD API (日本書籍に非常に強い) で検索
        $openBdData = $this->searchOpenBd($cleanedIsbn);
        if ($openBdData) {
            return $openBdData;
        }

        return null;
    }

    /**
     * Google Books API 検索
     */
    private function searchGoogleBooks(string $isbn): ?array
    {
        try {
            $response = Http::withoutVerifying() // Docker環境等のSSL証明書エラー回避
                ->get("https://www.googleapis.com/books/v1/volumes?q=isbn:{$isbn}");

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();

            if (empty($data['totalItems']) || !isset($data['items'][0]['volumeInfo'])) {
                // isbn: で引っかからない場合のフォールバック検索
                $response = Http::withoutVerifying()
                    ->get("https://www.googleapis.com/books/v1/volumes?q={$isbn}");
                if ($response->failed()) return null;
                $data = $response->json();
            }

            if (empty($data['totalItems']) || !isset($data['items'][0]['volumeInfo'])) {
                return null;
            }

            $info = $data['items'][0]['volumeInfo'];

            return [
                'title'          => $info['title'] ?? '',
                'author'         => isset($info['authors']) ? implode(', ', $info['authors']) : '',
                'publisher'      => $info['publisher'] ?? '',
                'published_date' => $this->formatDate($info['publishedDate'] ?? ''),
                'description'    => $info['description'] ?? '',
                'image_url'      => $info['imageLinks']['thumbnail'] ?? ($info['imageLinks']['smallThumbnail'] ?? null),
                'isbn'           => $isbn,
            ];
        } catch (\Exception $e) {
            \Log::error("GoogleBooksService Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * openBD API 検索 (フォールバック用)
     */
    private function searchOpenBd(string $isbn): ?array
    {
        try {
            $response = Http::withoutVerifying()
                ->get("https://api.openbd.jp/v1/get?isbn={$isbn}");

            if ($response->failed()) {
                return null;
            }

            $data = $response->json();

            if (empty($data[0]['summary'])) {
                return null;
            }

            $summary = $data[0]['summary'];

            return [
                'title'          => $summary['title'] ?? '',
                'author'         => $summary['author'] ?? '',
                'publisher'      => $summary['publisher'] ?? '',
                'published_date' => $this->formatDate($summary['pubdate'] ?? ''),
                'description'    => $summary['cover'] ? '' : '',
                'image_url'      => $summary['cover'] ?? null,
                'isbn'           => $isbn,
            ];
        } catch (\Exception $e) {
            \Log::error("openBD Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 日付フォーマット補正
     */
    private function formatDate(string $rawDate): string
    {
        if (!$rawDate) return '';

        // YYYYMMDD 形式 (openBD用)
        if (preg_match('/^\d{8}$/', $rawDate)) {
            return substr($rawDate, 0, 4) . '-' . substr($rawDate, 4, 2) . '-' . substr($rawDate, 6, 2);
        }
        // YYYY 形式
        if (preg_match('/^\d{4}$/', $rawDate)) {
            return $rawDate . '-01-01';
        }
        // YYYY-MM 形式
        if (preg_match('/^\d{4}-\d{2}$/', $rawDate)) {
            return $rawDate . '-01';
        }

        return $rawDate;
    }
}