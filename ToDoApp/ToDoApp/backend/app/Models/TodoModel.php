<?php

namespace App\Models;

use CodeIgniter\Model;

class TodoModel extends Model
{
    protected $table = 'todos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'domain_id',
        'title',
        'description',
        'end_date',
        'priority',
        'status',
    ];
}
