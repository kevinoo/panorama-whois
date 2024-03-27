<?php

namespace kevinoo\PanoramaWhois\Models;

use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\{
    Model,
    Factories\HasFactory
};

/**
 * @property int $ip_from
 * @property int $ip_to
 * @property string $country_code
 */
class IpRangesByCountries extends Model
{
    use HasFactory;

    protected $table = 'ip_ranges_by_countries';
    protected $primaryKey = null;
    public $incrementing = false;
    protected $connection = 'panorama-whois';

    protected $fillable = [
        'ip_from',
        'ip_to',
        'country_code',
    ];

}
