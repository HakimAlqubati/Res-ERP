<?php

namespace App\Http\Controllers;

use App\Services\S3ImageService;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    protected $s3ImageService;

    public function __construct(S3ImageService $s3ImageService)
    {
        $this->s3ImageService = $s3ImageService;
    }

    /**
     * Display all images from the S3 bucket, optionally filtered by date.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function displayAllImages(Request $request)
    {
        // Get date range from request
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Fetch images using the service
        $imageUrls = $this->s3ImageService->getAllImages($startDate, $endDate);
        return $imageUrls;
        return view('images.display', compact('imageUrls', 'startDate', 'endDate'));
    }
}
