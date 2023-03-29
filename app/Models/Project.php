<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'img',
        'type_id'
    ];

    // appends the return of getFullImgPathAttribute to the Project istance
    protected $appends = ['full_img_path'];

    // Hidden ONLY from query
    protected $hidden = [
        'img',
    ];

    // function to append image storage to use in front end with API
    public function getFullImgPathAttribute() {
        $fullPath = null;
        if ($this->img) {
            $fullPath = asset('storage/'.$this->img);
        }

        return $fullPath;
    }   

    public function type() {
        return $this->belongsTo(Type::class);
    }

    public function technologies() {
        return $this->belongsToMany(Technology::class);
    }
    
}
