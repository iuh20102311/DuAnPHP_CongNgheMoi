<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Exception;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

class Discount extends Model
{
    use HasFactory;
    protected $table = 'discounts';
    protected $fillable = ['coupon_code','discount_value','discount_unit','minimum_order_value','maximum_order_value','valid_until','valid_start','note','created_at','updated_at'];
    protected $primaryKey = 'id';
    public $timestamps = true;

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_discounts');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_discounts');
    }

    /**
     * @throws Exception
     */
    public function validate(array $data, bool $isUpdate = false) : string
    {
        $validators = [
            'coupon_code' => v::notEmpty()->setName('coupon_code')->setTemplate(',Mã giảm giá không được rỗng'),
            'discount_value' => v::notEmpty()->setName('discount_value')->setTemplate('Gía trị mã giảm giá không được rỗng'),
            'discount_unit' => v::notEmpty()->setName('discount_unit')->setTemplate('Đơn vị giảm giá không được rỗng'),
            'minimum_order_value' => v::notEmpty()->setName('minimum_order_value')->setTemplate('Gía trị đơn hàng thấp nhất không được rỗng'),
            'maximum_order_value' => v::notEmpty()->setName('maximum_order_value')->setTemplate('Gía trị đơn hàng lớn nhất không được rỗng'),
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