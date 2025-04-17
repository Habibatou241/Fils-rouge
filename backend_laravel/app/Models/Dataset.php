<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dataset extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'name',
        'original_filename',
        'file_path',
        'file_type',
        'row_count',
        'columns'
    ];

    protected $casts = [
        'columns' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
{
    return $this->belongsTo(Project::class);
}


}
