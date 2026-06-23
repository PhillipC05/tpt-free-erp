<?php

namespace App\Models\Expenses;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $table = 'expense_categories';

    protected $fillable = ['name', 'code', 'requires_receipt'];

    protected $casts = [
        'requires_receipt' => 'boolean',
    ];
}
