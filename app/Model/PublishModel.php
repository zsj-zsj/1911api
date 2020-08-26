<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PublishModel extends Model
{
    protected $table='u_publish';
    protected $primaryKey='p_id';
    protected $guarded = [];
    public $timestamps = false;
}
