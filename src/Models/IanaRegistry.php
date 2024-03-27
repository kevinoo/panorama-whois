<?php

namespace kevinoo\PanoramaWhois\Models;

use Illuminate\Database\Eloquent\{
    Model,
    Factories\HasFactory
};

/**
 * @property int $id
 * @property string $name
 * @property string $status
 * @property string $link
 * @property string $phone
 * @property string $abuse_email
 */
class IanaRegistry extends Model
{
    use HasFactory;

    protected $table = 'iana_registry';
    protected $connection = 'panorama-whois';
    public const CREATED_AT = null;
    public const UPDATED_AT = null;

    protected $fillable = [
        'id',
        'name',
        'status',
        'link',
        'phone',
        'abuse_email',
    ];

}
