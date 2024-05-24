<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductExportReceiptDetail extends Model
{
    use HasFactory;
    protected $table = 'product_export_receipt_details';
    protected $fillable = ['product_export_receipt_id','product_id','quantity','created_at','updated_at'];
    protected $primaryKey = 'id';
    public $timestamps = true;

    public function ProductExportReceipt(): BelongsTo
    {
        return $this->belongsTo(ProductExportReceipt::class, 'product_export_receipt_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class,'product_id');
    }

    public function receipt()
    {
        return $this->belongsTo(ProductExportReceipt::class, 'product_export_receipt_id');
    }
}