<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ChurchAnnualReportDetailRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\Church;
use App\Models\Personel;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class QuickReportController extends Controller
{
    public function newchurch()
    {
        $new_church_tables = Church::whereYear('founded_on', Carbon::now()->year)
                        ->leftJoin('church_types','churches.church_type_id','church_types.id')
                        ->leftJoin('rc_dpwlists','churches.rc_dpw_id','rc_dpwlists.id')
                        ->leftJoin('country_lists','churches.country_id','country_lists.id')
                        ->select('entities_type','rc_dpw_name','country_name','church_name','founded_on','first_email')
                        ->get();

        $data['new_church_tables'] = $new_church_tables;

        return view('vendor.backpack.base.newchurchreport',$data);
    }

    public function newpastor()
    {
        $new_pastor_tables = Personel::whereYear('first_lisenced_on', Carbon::now()->year)
                        ->leftJoin('rc_dpwlists','personels.rc_dpw_id','rc_dpwlists.id')
                        ->leftJoin('country_lists','personels.country_id','country_lists.id')
                        ->select('first_name','rc_dpw_name','street_address','country_name','email','first_lisenced_on')
                        ->get();

        $data['new_pastor_tables'] = $new_pastor_tables;

        return view('vendor.backpack.base.newpastorreport',$data);
    }

    public function inactivechurch()
    {
        $inactive_church_reports = Church::where('church_status', 'Non Active')
                    ->leftJoin('church_types','churches.church_type_id','church_types.id')
                    ->leftJoin('rc_dpwlists','churches.rc_dpw_id','rc_dpwlists.id')
                    ->leftJoin('country_lists','churches.country_id','country_lists.id')
                    ->select('entities_type','rc_dpw_name','country_name','church_name','church_status','first_email')
                    ->get();
        
        $data['inactive_church_reports'] = $inactive_church_reports;

        return view('vendor.backpack.base.inactivechurch',$data);
    }
    
    public function inactivepastor()
    {
        $inactive_pastor_reports = Personel::where('acc_status_id', '2')
                        ->leftJoin('rc_dpwlists','personels.rc_dpw_id','rc_dpwlists.id')
                        ->leftJoin('country_lists','personels.country_id','country_lists.id')
                        ->leftJoin('account_status','personels.acc_status_id','account_status.id')
                        ->select('first_name','rc_dpw_name','street_address','country_name','email','acc_status')
                        ->get();

        $data['inactive_pastor_reports'] = $inactive_pastor_reports;

        return view('vendor.backpack.base.inactivepastor',$data);
    }

    public function allchurch()
    {
        $all_church_tables = Church::leftJoin('church_types','churches.church_type_id','church_types.id')
                        ->leftJoin('rc_dpwlists','churches.rc_dpw_id','rc_dpwlists.id')
                        ->leftJoin('country_lists','churches.country_id','country_lists.id')
                        ->select('entities_type','rc_dpw_name','country_name','church_name','founded_on','first_email',
                        'church_address', 'office_address', 'city', 'province', 'postal_code', 'phone', 'church_status')
                        ->get();

        $data['all_church_tables'] = $all_church_tables;

        return view('vendor.backpack.base.allchurchreport',$data);
    }

    public function allpastor()
    {
        $all_pastor_tables = Personel::leftJoin('rc_dpwlists','personels.rc_dpw_id','rc_dpwlists.id')
                        ->leftJoin('country_lists','personels.country_id','country_lists.id')
                        ->leftJoin('account_status','personels.acc_status_id','account_status.id')
                        ->select('first_name','rc_dpw_name','street_address','country_name','email',
                        'city','province','acc_status','phone','postal_code')
                        ->get();

        $data['all_pastor_tables'] = $all_pastor_tables;

        return view('vendor.backpack.base.allpastorreport',$data);
    }

}
