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
        'user_id',
        'path',
        'positionX',
        'positionY',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
   
}
