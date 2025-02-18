<?php
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

function uploadImageToGraphQL(TemporaryUploadedFile $file) {
    $fileName = $file->getClientOriginalName();
    $fileContent = file_get_contents($file->getRealPath());

    // 🛠 Gửi request multipart/form-data lên API GraphQL
    $uploadResponse = Http::withHeaders([
        'Authorization' => 'Bearer ' . session('accessToken'),
    ])->attach(
        '1', // Key "1" trùng với mapping
        $fileContent,
        $fileName
    )->post(env('GRAPHQL_ENDPOINT'), [
        'operations' => json_encode([
            'operationName' => 'fileImageUpload',
            'variables' => ['input' => ['image' => null]],
            'query' => '
                mutation fileImageUpload($input: FileImageUploadInput!) {
                    fileImageUpload(input: $input) {
                        url
                        originalFilename
                    }
                }
            ',
        ]),
        'map' => json_encode(['1' => ['variables.input.image']]), // Map file với biến "image"
    ]);

    // 📌 Kiểm tra phản hồi từ API
    $uploadResult = $uploadResponse->json();
    Log::debug('GraphQL Upload Response: ' . json_encode($uploadResult));

    if (isset($uploadResult['data']['fileImageUpload']['url'])) {
        // 🖼 Trả về URL ảnh từ API GraphQL để Filament sử dụng
        return $uploadResult['data']['fileImageUpload']['url'];
    } else {
        Log::error('Upload error: ' . json_encode($uploadResult['errors'] ?? 'Unknown error'));
        return null;
    }
}
