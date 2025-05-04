<?php

namespace app\models\Communication;

use app\core\BaseModel;

class ChatRoom extends BaseModel {

    protected $table = 'chat_room';

    public function __construct() {
        parent::__construct($this->table);
    }

    public function getTable() {
        return $this->table;
    }
}
?>