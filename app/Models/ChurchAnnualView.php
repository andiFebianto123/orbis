<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class ChurchAnnualView extends Model
{
    use CrudTrait;

    public function ExportExcelButton(){
        return '<a href="javascript:void(0)"  onclick="exportReport()" class="btn btn-xs btn-success"><i class="la la-file-excel-o"></i> Export Button</a>';
    }

    public function DetailButton(){
        return '<a href="'.backpack_url('church-annual-report/' . $this->year . '/detail').'" <i class="la la-eye"></i> Report Detail</a>';
    }
}
