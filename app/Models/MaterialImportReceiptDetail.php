<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MaterialImportReceiptDetail extends Model
{
    use HasFactory;
    protected $table = 'material_import_receipt_details';
    protected $fillable = ['material_import_receipt_id','material_id','quantity','price','created_at','updated_at'];
    protected $primaryKey = 'id';
    public $timestamps = true;

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function MaterialImportReceipt(): BelongsTo
    {
        return $this->belongsTo(MaterialImportReceipt::class, 'material_import_receipt_id');
    }
}