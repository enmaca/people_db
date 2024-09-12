<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class People extends Model
{
    use HasFactory;

    protected $table = 'people';

    protected $fillable = [
        'edad',
        'nombre',
        'paterno',
        'materno',
        'fecha_nacimiento',
        'sexo',
        'calle',
        'curp',
        'int',
        'ext',
        'colonia',
        'cp',
        'ine_cve',
        'ine_e',
        'ine_d',
        'ine_m',
        'ine_s',
        'ine_l',
        'ine_mza',
        'ine_consec',
        'ine_cred',
        'ine_folio',
    ];
}
