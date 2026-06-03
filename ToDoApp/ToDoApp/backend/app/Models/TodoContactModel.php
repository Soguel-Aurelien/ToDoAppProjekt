<?php

namespace App\Models;

use CodeIgniter\Model;

class TodoContactModel extends Model
{
    protected $table = 'todo_contacts';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'todo_id',
        'email',
        'created_at',
    ];
}
