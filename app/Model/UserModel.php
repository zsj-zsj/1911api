<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserModel extends Model
{
    protected $table='u_user';
    protected $primaryKey='user_id';
    protected $guarded = [];
    public $timestamps = false;
}
