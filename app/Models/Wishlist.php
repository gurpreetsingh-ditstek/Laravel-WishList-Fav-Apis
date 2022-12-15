<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Wishlist extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id')->select('id', 'name', 'unique_code');
    }
    public function treatmentRelation() {
        return $this->hasMany(UserRequestFile::class);
    }
    public function treatmentdetails() {
        return $this->hasMany(TreatmentDetail::class,'treatment_id','treatment_id');
    }
    public function treatmentdetailsforapi() {
        return $this->hasMany(TreatmentDetail::class,'treatment_id','treatment_id')->where('title','!=', '');
    }
    public function treatment() {
        return $this->hasOne(Treatment::class,'id','treatment_id')->select('id','title');
    }
}
