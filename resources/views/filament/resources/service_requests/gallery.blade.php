<div class="grid grid-cols-3 gap-4">
    @foreach ($photos as $photo)
        <div>
            <img src="{{ asset('storage/' . $photo->image_name) }}" alt="Task Photo" class="rounded-lg shadow-lg">
        </div>
    @endforeach
</div>
