<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class S3ImageService
{
    /**
     * Fetch all image URLs from the S3 bucket, optionally filtered by date.
     *
     * @param string|null $startDate Format: YYYY-MM-DD
     * @param string|null $endDate   Format: YYYY-MM-DD
     * @return array
     */
    public function getAllImages(?string $startDate = null, ?string $endDate = null): array
    {
        // Get all files in the bucket
        $files = Storage::disk('s3')->allFiles();

        // Filter files to include only images
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $images = array_filter($files, function ($file) use ($imageExtensions) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            return in_array(strtolower($extension), $imageExtensions);
        });

        // Filter images by date, if specified
        if ($startDate || $endDate) {
            $images = array_filter($images, function ($image) use ($startDate, $endDate) {
                $lastModified = Storage::disk('s3')->lastModified($image);
                $fileDate = Carbon::createFromTimestamp($lastModified);

                if ($startDate && $fileDate->lt(Carbon::parse($startDate))) {
                    return false;
                }

                if ($endDate && $fileDate->gt(Carbon::parse($endDate))) {
                    return false;
                }

                return true;
            });
        }

        // Generate URLs for the images
        return array_map(function ($image) {
            return Storage::disk('s3')->url($image);
        }, $images);
    }
}
