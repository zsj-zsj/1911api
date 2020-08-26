<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CateModel extends Model
{
    protected $table='new_category';
    protected $primaryKey='cate_id';
    protected $guarded = [];
    public $timestamps = false;
}
