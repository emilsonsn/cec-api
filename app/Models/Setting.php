<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = 'settings';

    protected $fillable = [
        'limit',
        'display'
    ];

    public function getDisplayAttribute()
    {
        if(isset($this->attributes['display']) && $this->attributes['display']){
            return env('APP_URL') . '/storage/display/' . $this->attributes['display'];
        }
    }
}
