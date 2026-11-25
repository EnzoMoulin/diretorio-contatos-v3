<?php

namespace App\Models;

use App\Casts\PgTextArray;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $termo
 * @property list<string> $expansao
 */
class Sinonimo extends Model
{
    protected $table = 'sinonimos';

    public $timestamps = false;

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['expansao' => PgTextArray::class];
    }
}
