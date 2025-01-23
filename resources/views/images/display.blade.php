<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All S3 Images</title>
    <style>
        .gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .gallery img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <h1>All Images in S3 Bucket</h1>

    <form method="GET" action="{{ url('/images') }}">
        <label for="start_date">Start Date:</label>
        <input type="date" id="start_date" name="start_date" value="{{ $startDate ?? '' }}">
        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" name="end_date" value="{{ $endDate ?? '' }}">
        <button type="submit">Filter</button>
    </form>

    <div class="gallery">
        @forelse ($imageUrls as $url)
            <img src="{{ $url }}" alt="Image">
        @empty
            <p>No images found in the selected date range.</p>
        @endforelse
    </div>
</body>

</html>
