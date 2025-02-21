<?php

namespace ShoppingCart\Models;

use CodeIgniter\Config\Config;
use CodeIgniter\Model;

class ShoppingCart extends Model
{
    protected $table;
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['identifier', 'instance', 'content'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get config table name.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        
        $this->table = config('Cart')->table ?? 'shoppingcart';
    }
}
