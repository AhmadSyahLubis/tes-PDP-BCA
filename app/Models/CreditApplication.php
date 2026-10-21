<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CreditApplication extends Model
{
    protected $fillable = [
        'application_no', 'customer_id', 'branch_id', 'marketing_id', 'dealer_name',
        'vehicle_brand', 'vehicle_model', 'vehicle_type', 'vehicle_color', 'vehicle_price',
        'down_payment', 'tenor_months', 'monthly_installment', 'status',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'issued_at' => 'datetime', 'signed_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function marketing(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marketing_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ApplicationEvent::class);
    }

    public function disbursement(): HasOne
    {
        return $this->hasOne(Disbursement::class);
    }
}
