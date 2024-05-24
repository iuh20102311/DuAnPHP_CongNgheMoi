<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductImportReceiptDetail extends Model
{
    use HasFactory;
    protected $table = 'product_import_receipt_details';
    protected $fillable = ['product_import_receipt_id','product_id','provider_id','quantity','created_at','updated_at'];
    protected $primaryKey = 'id';
    public $timestamps = true;

    public function ProductImportReceipt(): BelongsTo
    {
        return $this->belongsTo(ProductImportReceipt::class, 'product_import_receipt_id');
    }

    public function importReceipt()
    {
        return $this->belongsTo(ProductImportReceipt::class, 'product_import_receipt_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }
}