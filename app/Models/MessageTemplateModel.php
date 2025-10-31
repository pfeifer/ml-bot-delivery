<?php

namespace App\Models;

use CodeIgniter\Model;

class MessageTemplateModel extends Model
{
    protected $table = 'message_templates';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['name', 'content'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'name' => 'required|max_length[100]',
        'content' => 'required',
    ];
    protected $validationMessages = [
        'name' => [
            'required' => 'O nome do template é obrigatório.',
            'max_length' => 'O nome não pode exceder 100 caracteres.',
        ],
        'content' => [
            'required' => 'O conteúdo da mensagem é obrigatório.',
        ],
    ];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

}