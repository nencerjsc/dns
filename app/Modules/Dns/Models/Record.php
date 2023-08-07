<?php

namespace App\Modules\Dns\Models;

use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $timestamps = false;
    protected $table = 'records';
    protected $fillable = [
        'domain_id',
        'domain',
        'name',
        'type',
        'content',
        'ttl',
        'prio',
        'disabled',
        'ordername',
        'auth',
    ];

    public function domain(){
        return $this->hasOne(Zone::class, 'id', 'domain_id');
    }

}