<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactUs extends Model
{
    use HasFactory;

    protected $table = 'contact_us';

    protected $fillable = [
        'name',
        'email',
        'category',
        'subject',
        'message',
        'source',
        'is_read',
    ];

    public const SOURCE_MOBILE = 'mobile';
    public const SOURCE_TV = 'tv';
    public const SOURCE_OTHER = 'other';
}
