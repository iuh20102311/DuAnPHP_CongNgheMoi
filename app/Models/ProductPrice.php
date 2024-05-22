<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Exception;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

class ProductPrice extends Model
{
    use HasFactory;
    protected $table = 'product_prices';
    protected $fillable = ['product_id','data_expiry','price','status','created_at','updated_at'];
    protected $primaryKey = 'id';
    public $timestamps = true;

    /**
     * @throws Exception
     */
    public function validate(array $data)
    {
        $validators = [
            'product_id' => v::notEmpty()->setName('product_id')->setTemplate('Khách hàng không được rỗng'),
            'price' => v::notEmpty()->setName('price')->setTemplate('Gía tền không được rỗng'),
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

        if (!empty($errors)) {
            throw new Exception(json_encode(['errors' => $errors]), 400);
        }
    }
}