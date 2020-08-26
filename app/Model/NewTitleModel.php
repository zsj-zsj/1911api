<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class NewTitleModel extends Model
{
    protected $table='new_title';
    protected $primaryKey='title_id';
    protected $guarded = [];
    public $timestamps = false;
}
