<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Exception;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

class ProductInventory extends Model
{
    use HasFactory;
    protected $table = 'product_inventories';
    protected $fillable = ['product_id','warehouse_id','quantity_available','minimum_stock_level','status','created_at','updated_at'];
    protected $primaryKey = 'id';
    public $timestamps = true;


    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @throws Exception
     */
    public function validate(array $data)
    {
        $validators = [
            'product_id' => v::notEmpty()->setName('product_id')->setTemplate('Khách hàng không được rỗng'),
            'warehouse_id' => v::notEmpty()->setName('warehouse_id')->setTemplate('Nhà kho không được rỗng'),
            'quantity_available' => v::notEmpty()->setName('quantity_available')->setTemplate('Số lượng hiện có không được rỗng'),
            'minimum_stock_level' => v::notEmpty()->setName('minimum_stock_level')->setTemplate('Mức tồn kho tối thiểu không được rỗng'),
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

        if (isset($data['product_id']) && !Product::find($data['product_id'])) {
            $errors['product_id'] = ['Khách hàng không tồn tại'];
        }

        if (isset($data['warehouse_id']) && !Warehouse::find($data['warehouse_id'])) {
            $errors['warehouse_id'] = ['Nhà không không tồn tại'];
        }

        if (!empty($errors)) {
            throw new Exception(json_encode(['errors' => $errors]), 400);
        }
    }
}