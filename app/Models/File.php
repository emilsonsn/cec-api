<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = 'files';

    protected $fillable = [
        'uuid',
        'user_id',
        'assign_id',
        'path',
        'signature',
        'filename',
        'positionX',
        'positionY',
    ];

    public function getPathAttribute()
    {
        if(isset($this->attributes['path']) && $this->attributes['path']){
            return env('APP_URL') . '/storage/files_assign/' . $this->attributes['path'];
        }
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
   
}
