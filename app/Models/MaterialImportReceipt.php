<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Exception;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

class MaterialImportReceipt extends Model
{
    use HasFactory;
    protected $table = 'material_import_receipts';
    protected $fillable = ['type','note','receipt_date','total_price','warehouse_id','status','created_at','updated_at'];
    protected $primaryKey = 'id';
    public $timestamps = true;

    public function MaterialImportReceiptDetails(): HasMany
    {
        return $this->hasMany(MaterialImportReceiptDetail::class, 'material_import_receipt_id');
    }

    /**
     * @throws Exception
     */
    public function validate(array $data, bool $isUpdate = false) : string
    {
        $validators = [
            'warehouse_id' => v::notEmpty()->setName('group_customer_id')->setTemplate('Nhà kho không được rỗng'),
            'total_price' => v::notEmpty()->setName('total_price')->setTemplate('Tổng tiền không được rỗng'),
            'type' => v::notEmpty()->setName('type')->setTemplate(';Loại hóa đơn không được rỗng'),
            'status' => v::notEmpty()->setName('status')->setTemplate('Trạng thái không được rỗng'),
        ];

        $error = "";
        foreach ($validators as $field => $validator) {
            if ($isUpdate && !array_key_exists($field, $data)) {
                continue;
            }

            try {
                $validator->assert(isset($data[$field]) ? $data[$field] : null);
            } catch (ValidationException $exception) {
                $error = $exception->getMessage();
                break;
            }
        }
        return $error;
    }
}