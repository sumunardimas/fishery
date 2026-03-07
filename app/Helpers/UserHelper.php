<?php

use Illuminate\Support\Facades\Storage;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

if (! function_exists('getMimeTypeByFilename')) {
    function getMimeTypeByFilename($filename): ?string
    {
        $map = new \League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;
        $explodedFilename = explode('.', $filename);
        $mimeType = $map->lookupMimeType(end($explodedFilename));

        return $mimeType;
    }
}

if (! function_exists('getS3Url')) {
    function getS3Url($filepath, $filename, $durationInMinute = 5): string
    {
        $safeFilename = rawurlencode($filename);

        return Storage::temporaryUrl(
            $filepath,
            now()->addMinutes($durationInMinute),
            [
                'ResponseContentDisposition' => "inline; filename*=UTF-8''{$safeFilename}",
                'ResponseContentType' => getMimeTypeByFilename($safeFilename),
            ],
        );
    }
}

