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
    public function validate(array $data)
    {
        $validators = [
            'warehouse_id' => v::notEmpty()->setName('group_customer_id')->setTemplate('Nhà kho không được rỗng'),
            'total_price' => v::notEmpty()->setName('total_price')->setTemplate('Tổng tiền không được rỗng'),
            'type' => v::notEmpty()->setName('type')->setTemplate(';Loại hóa đơn không được rỗng'),
            'status' => v::notEmpty()->setName('status')->setTemplate('Trạng thái không được rỗng'),
        ];

        $errors = [];
        foreach ($validators as $field => $validator) {
            try {
                $validator->assert(isset($data[$field]) ? $data[$field] : null);
            } catch (ValidationException $exception) {
                $errors[$field] = $exception->getMessages();
            }
        }

        if (isset($data['warehouse_id']) && !Warehouse::find($data['warehouse_id'])) {
            $errors['warehouse_id'] = ['Nhà kho không tồn tại'];
        }

        if (!empty($errors)) {
            throw new Exception(json_encode(['errors' => $errors]), 400);
        }
    }
}