<?php

namespace App\Models;

use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    /** @use HasFactory<ModuleFactory> */
    use HasFactory;

    protected $guarded = [];

    /** @var list<string> */
    public const SECTIONS = ['rh', 'sst', 'sig', 'sintyc', 'inicio'];

    /** @var list<string> */
    public const TYPES = ['enlace', 'documento', 'formulario', 'indicadores', 'calendario'];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'visible' => 'boolean',
        ];
    }

    public function scopeSection($query, string $section)
    {
        return $query->where('section', $section);
    }

    public function scopeVisible($query)
    {
        return $query->where('visible', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('id');
    }
}
