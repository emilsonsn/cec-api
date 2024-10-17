<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $table = 'clients';

    protected $fillable = [
        'name',
        'cpf_cnpj',
        'phone',
        'whatsapp',
        'email',
        'address',
        'city',
        'state',
    ];

}
