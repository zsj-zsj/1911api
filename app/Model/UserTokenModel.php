<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserTokenModel extends Model
{
    protected $table='u_token';
    protected $primaryKey='token_id';
    protected $guarded = [];
    public $timestamps = false;
}
