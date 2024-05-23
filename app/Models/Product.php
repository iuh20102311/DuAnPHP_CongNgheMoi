<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Exception;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

class Product extends Model
{
    use HasFactory;
    protected $table = 'products';
    protected $fillable = ['sku','name','packing','price','quantity','weight','image','description','status','created_at','updated_at'];
    protected $primaryKey = 'id';
    public $timestamps = true;

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'material_product');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(Discount::class, 'product_discounts');
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_details');
    }

    public function ProductExportReceiptDetails(): HasMany
    {
        return $this->hasMany(ProductExportReceiptDetail::class, 'product_id');
    }

    public function ProductImportReceiptDetails(): HasMany
    {
        return $this->hasMany(ProductImportReceiptDetail::class, 'product_id');
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(ProductInventory::class, 'product_id');
    }

    /**
     * @throws Exception
     */
    public function validate(array $data, bool $isUpdate = false) : string
    {
        $validators = [
            'sku' => v::notEmpty()->setName('sku')->setTemplate('Mã hàng hóa không được rỗng'),
            'name' => v::notEmpty()->setName('name')->setTemplate('Tên không được rỗng'),
            'packing' => v::notEmpty()->setName('packing')->setTemplate('Loại vật chứa không được rỗng'),
            'price' => v::notEmpty()->setName('price')->setTemplate('Gía cả không được rỗng'),
            'quantity' => v::notEmpty()->setName('quantity')->setTemplate('Số lượng không được rỗng'),
            'weight' => v::notEmpty()->setName('weight')->setTemplate('Khối lượng không được rỗng'),
            'image' => v::notEmpty()->setName('image')->setTemplate('Hình ảnh không được rỗng'),
            //'description' => v::notEmpty()->setName('district')->setTemplate('Đường không được rỗng'),
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