<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MdrCatalog extends Model
{
    protected $table = 'oc_enamel_mdr_catalog';
    
    protected $fillable = [
        'category',
        'slug',
        'name',
        'duration_use',
        'is_implantable',  // true = corone/ponti/protesi fissa → retention 15 anni (MDR Art. 10(8))
        'risk_class',
        'mdr_rule',
        'pms_years',
        'is_active',
    ];

    protected $casts = [
        'is_implantable' => 'boolean',
        'is_active'      => 'boolean',
    ];
}