<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'address_id', 'invoice_number', 'payment_method', 'bank_name',
        'voucher_id', 'voucher_discount', 'shipping_cost',
        'product_subtotal', 'total_amount', 'status',
        'courier', 'receipt_number', 'payment_expired_at', 'notes',
    ];

    protected $casts = [
        'payment_expired_at' => 'datetime',
        'voucher_discount'   => 'float',
        'shipping_cost'      => 'float',
        'product_subtotal'   => 'float',
        'total_amount'       => 'float',
    ];

    // STATUS MAP (DB → Frontend)
    const STATUS_MAP = [
        'pending_payment'       => 'belum-dibayar',
        'waiting_verification'  => 'menunggu verifikasi',
        'processing'            => 'dikemas',
        'shipped'               => 'dikirim',
        'delivered'             => 'diterima',
        'completed'             => 'selesai',
        'cancelled'             => 'dibatalkan',
    ];

    public function getFrontendStatusAttribute(): string
    {
        return self::STATUS_MAP[$this->status] ?? $this->status;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'address_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderHistory::class)->orderBy('created_at', 'desc');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}