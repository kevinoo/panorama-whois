<?php

namespace kevinoo\PanoramaWhois\Models;

use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\{
    Model,
    Factories\HasFactory
};

/**
 * @property string $code
 * @property string $iso2
 * @property string $name
 * @property DateTime $created_at
 * @property DateTime $updated_at
 */
class Country extends Model
{
    use HasFactory;

    protected $table = 'countries';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'panorama-whois';

    protected $fillable = [
        'code',
        'iso2',
        'name',
    ];

}
