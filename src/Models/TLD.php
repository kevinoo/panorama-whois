<?php

namespace kevinoo\PanoramaWhois\Models;

use DateTime;
use Illuminate\Database\Eloquent\{
    Model,
    Factories\HasFactory
};

/**
 * @property string $tld
 * @property DateTime $created_at
 */
class TLD extends Model
{
    use HasFactory;

    protected $table = 'tlds';
    protected $primaryKey = 'tld';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'panorama-whois';
    public const UPDATED_AT = null;

    protected $fillable = [
        'tld',
    ];

}
