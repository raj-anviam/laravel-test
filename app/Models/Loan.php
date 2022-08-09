<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Installment;

class Loan extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function installments() {
        return $this->hasMany(Installment::class);
    }
}
