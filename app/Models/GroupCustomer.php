<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Exception;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

class GroupCustomer extends Model
{
    use HasFactory;
    protected $table = 'group_customers';
    protected $fillable = ['name','status','created_at','updated_at'];
    protected $primaryKey = 'id';
    public $timestamps = true;

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class,'group_customer_id');
    }

    /**
     * @throws Exception
     */
    public function validate(array $data)
    {
        $validators = [
            'name' => v::notEmpty()->setName('name')->setTemplate('Tên không được rỗng'),
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

        if (!empty($errors)) {
            throw new Exception(json_encode(['errors' => $errors]), 400);
        }
    }
}