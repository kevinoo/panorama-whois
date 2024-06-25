<?php

namespace kevinoo\PanoramaWhois\Models;

use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\{
    Model,
    Factories\HasFactory
};


/**
 * @property string   $image_url
 * @property string   $content
 * @property DateTime $created_at
 * @property DateTime $expired_at
 */
class CachedOcrRequests extends Model
{
    use HasFactory;

    protected $table = 'cached_ocr_requests';
    protected $primaryKey = 'image_url';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'panorama-whois';

    public const UPDATED_AT = false;

    protected $fillable = [
        'image_url',
        'content',
        'created_at',
        'expired_at',
    ];

    /**
     * The attributes that should be cast to native types.
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'expired_at' => 'datetime',
    ];
}
