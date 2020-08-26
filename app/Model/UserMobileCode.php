<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserMobileCode extends Model
{
    protected $table='u_mobile_code';
    protected $primaryKey='msg_id';
    protected $guarded = [];
    public $timestamps = false;
}
