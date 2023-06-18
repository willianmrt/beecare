<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuncionarioModel extends Model{
    use HasFactory;

    protected $table = 'funcionarios';
    protected $fillable = ['nome', 'salario', 'updated_at', 'created_at'];

    protected $primaryKey = 'id';
    protected $keyType = 'int'; 
    public $incrementing = true; 

    protected $casts = [
        'salario' => 'float' // Conversão de tipo para o campo "salario"
    ];


}
