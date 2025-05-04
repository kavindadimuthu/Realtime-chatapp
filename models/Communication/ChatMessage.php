<?php

namespace app\models\Communication;

use app\core\BaseModel;

class ChatMessage extends BaseModel {

    protected $table = 'chat_message';

    public function __construct() {
        parent::__construct($this->table);
    }

    public function getTable() {
        return $this->table;
    }
}
?>