<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Covid19 extends Model
{
    protected $fillable = ['confirmed','recovered','deceased','active'];
}
