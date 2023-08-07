<?php

namespace App\Modules\Dns\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $timestamps = false;
    protected $table = 'domains';
    protected $fillable = [
        'name',
        'master',
        'last_check',
        'type',
        'notified_serial',
        'account',
    ];


    public function records(){
        return $this->hasMany(Record::class, 'domain_id', 'id');
    }

}