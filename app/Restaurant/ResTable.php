<?php

namespace App\Restaurant;

use App\Restaurant\ResTablePicture;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResTable extends Model
{
    use SoftDeletes;
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    
    
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function tablePicture(){
        return $this->hasMany(ResTablePicture::class,'table_id','id');
    }
}
