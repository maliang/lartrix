<?php

namespace Lartrix\Controllers;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class UploadController extends Controller
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico'];
    private const MAX_IMAGE_SIZE = 2 * 1024 * 1024;

    /**
     * 图片上传接口
     *
     * 返回 success(['url' => ...])，前端通过 data.url 取得地址。
     */
    public function image(): array
    {
        $file = request()->file('file');
        if (!$file instanceof UploadedFile) {
            error(__t('upload.file_required'), null, 40022);
        }

        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->guessExtension()));
        if (!in_array($extension, self::IMAGE_EXTENSIONS, true)) {
            error(__t('upload.image_type_invalid'), null, 40022);
        }

        if ($file->getSize() > self::MAX_IMAGE_SIZE) {
            error(__t('upload.image_too_large'), null, 40022);
        }

        $mime = strtolower((string) $file->getMimeType());
        if (!$this->isAllowedImageMime($mime, $extension)) {
            error(__t('upload.image_type_invalid'), null, 40022);
        }

        $date = date('Ymd');
        $directory = public_path('uploads' . DIRECTORY_SEPARATOR . 'lartrix' . DIRECTORY_SEPARATOR . $date);
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;

        try {
            $saved = $file->move($directory, $filename);
        } catch (FileException $e) {
            error($e->getMessage(), null, 500);
        }

        $url = '/uploads/lartrix/' . $date . '/' . $saved->getFilename();

        return success(__t('upload.ok'), [
            'url' => $url,
            'path' => $url,
            'name' => $file->getClientOriginalName(),
            'size' => $saved->getSize(),
            'mime' => $mime,
        ]);
    }

    private function isAllowedImageMime(string $mime, string $extension): bool
    {
        $allowed = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'ico' => ['image/x-icon', 'image/vnd.microsoft.icon'],
        ];

        return in_array($mime, $allowed[$extension] ?? [], true);
    }
}
