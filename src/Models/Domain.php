<?php

namespace kevinoo\PanoramaWhois\Models;

use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\{
    Model,
    Factories\HasFactory
};

/**
 * @property string $domain
 * @property array|null $who_is_data
 * @property DateTime $created_at
 * @property DateTime $updated_at
 *
 * @method static static find($domain)
 */
class Domain extends Model
{
    use HasFactory;

    protected $table = 'domains';
    protected $primaryKey = 'domain';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'panorama-whois-cache';

    protected $fillable = [
        'domain',
        'who_is_data',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'who_is_data' => 'array',
    ];
}
