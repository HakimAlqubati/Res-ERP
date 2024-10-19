<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TestImageReco extends Model
{
    use HasFactory;

    protected $table = 'test_image_reco';

    protected $fillable = [
        'title',
        'image',
        'image2',
        'details',
    ];

    public function getImage1Attribute()
    {
        return url('/storage') . '/' . $this->image;
    }
    public function getImage3Attribute()
    {
        return url('/storage') . '/' . $this->image2;
    }
}
