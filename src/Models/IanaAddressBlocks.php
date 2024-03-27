<?php

namespace kevinoo\PanoramaWhois\Models;

use Illuminate\Database\Eloquent\{
    Model,
    Factories\HasFactory
};

/**
 * @property int $prefix
 * @property string $designation
 * @property string $date
 * @property string $whois
 * @property string $rdap
 * @property string $status
 */
class IanaAddressBlocks extends Model
{
    use HasFactory;

    protected $table = 'iana_address_blocks';
    protected $primaryKey = 'prefix';
    protected $connection = 'panorama-whois';
    public const CREATED_AT = null;
    public const UPDATED_AT = null;

    protected $fillable = [
        'prefix',
        'designation',
        'date',
        'whois',
        'rdap',
        'status',
    ];

}
