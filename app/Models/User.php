<?php

namespace App\Models;

class User
{
    public $id;
    public $userName;
    public $image;

    public function __construct($data = [])
    {
        $this->id = $data['id'] ?? null;
        $this->userName = $data['userName'] ?? null;
        $this->image = $data['image'] ?? null;
    }
}