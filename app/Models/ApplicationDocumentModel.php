<?php

namespace App\Models;

use CodeIgniter\Model;

class ApplicationDocumentModel extends Model
{
    protected $table = 'application_documents';
    protected $allowedFields = ['application_id', 'doc_type', 'file_url', 'status'];
}
