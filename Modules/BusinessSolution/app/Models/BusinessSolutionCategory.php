<?php

namespace Modules\BusinessSolution\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSolutionCategory extends Model
{
    protected $table = 'business_solution_categories';

    protected $fillable = [
        'name', 'slug', 'description', 'status',
    ];
}
