<?php

namespace App\Models;

class Topic extends Model
{
    protected $fillable = ['title', 'content', 'user_id', 'category_id', 'reply_count', 'order', 'excerpt', 'slug'];
}
