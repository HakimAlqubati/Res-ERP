<?php

namespace App\Models;

use OwenIt\Auditing\Contracts\Auditable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Model;

class Location extends Model implements HasMedia, Auditable
{
    use InteractsWithMedia, \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'address',
        'city_id',
        'district_id',
        'country_id',
        'postal_code',
        'latitude',
        'longitude',
        'locationable_id', // Polymorphic foreign key
        'locationable_type', // Polymorphic type
    ];
    protected $auditInclude = [
        'address',
        'city_id',
        'district_id',
        'country_id',
        'postal_code',
        'latitude',
        'longitude',
        'locationable_id', // Polymorphic foreign key
        'locationable_type', // Polymorphic type
    ];

    /**
     * Register media collections for the location.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images') // Collection name
            ->useDisk('public') // Storage disk (optional, defaults to 'public')
            ->singleFile(); // Allow only one file in the collection (optional)
    }

    /**
     * Get the parent locationable model (e.g., Branch, User, Store).
     */
    public function locationable()
    {
        return $this->morphTo();
    }

    // Example relationships for District, City, and Country (if you have separate models for them)
    public function district()
    {
        return $this->belongsTo(District::class); // Assuming you have a District model
    }

    public function city()
    {
        return $this->belongsTo(City::class); // Assuming you have a City model
    }

    public function country()
    {
        return $this->belongsTo(Country::class); // Assuming you have a Country model
    }

    public function location(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => json_encode([
                'latitude' => (float) $attributes['latitude'],
                'longitude' => (float) $attributes['longitude'],
            ]),
            set: fn($value) => [
                'latitude' => $value['latitude'],
                'longitude' => $value['longitude'],
            ],
        );
    }
}
