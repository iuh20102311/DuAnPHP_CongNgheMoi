<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MaterialExportReceiptDetail extends Model
{
    use HasFactory;
    protected $table = 'material_export_receipt_details';
    protected $fillable = ['material_export_receipt_id','material_id','quantity','created_at','updated_at'];
    protected $primaryKey = 'id';
    public $timestamps = true;

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class,'material_id');
    }

    public function receipt()
    {
        return $this->belongsTo(MaterialExportReceipt::class, 'material_export_receipt_id');
    }

    public function MaterialExportReceipt(): BelongsTo
    {
        return $this->belongsTo(MaterialExportReceipt::class, 'material_export_receipt_id');
    }


}