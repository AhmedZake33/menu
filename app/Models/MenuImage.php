<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** One picture of a client's own menu, shown on their public link when they use the image menu. */
class MenuImage extends Model
{
    protected $guarded = ['id'];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function url(): string
    {
        return Storage::url($this->path);
    }
}
