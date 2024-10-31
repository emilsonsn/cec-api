<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodeChallenge extends Model
{
    use HasFactory;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = "codes_challenge";

    protected $fillable = [
        'code',
        'file_id'
    ];

    public function  user() {
        return $this->belongsTo(User::class);                
    }
}
