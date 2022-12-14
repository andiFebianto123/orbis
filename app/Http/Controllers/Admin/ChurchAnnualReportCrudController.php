<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Carbon\Carbon;
use App\Models\RcDpwList;
use App\Models\CountryList;
use Illuminate\Http\Request;
use App\Models\StructureChurch;
use App\Models\ChurchEntityType;
use App\Exports\ExportAnnualReport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Route;
use App\Models\ChurchAnnualDesignerView;
use App\Http\Requests\ChurchAnnualReportRequest;
use App\Models\Church;
use Illuminate\Support\Facades\DB;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ChurchAnnualReportCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;

    protected $listColumns;

    public function setup()
    {
        $this->crud->typeReport = $this->getCurrentType();
        $this->crud->disableResponsiveTable();
        $this->crud->disablePersistentTable();
        $this->crud->denyAccess(['create', 'update', 'show']);
        $this->setupListReport();
    }

    
    
    protected function setupListOperation()
    {
        if($this->crud->typeReport == 'annual'){
            CRUD::addColumns([
                [
                    'label' => 'Year',
                    'type' => 'text',
                    'name' => 'year'
                ],
                [
                    'label' => 'Churches',
                    'type' => 'text',
                    'name' => 'total'
                ]
            ]);
        }
        else if($this->crud->typeReport == 'detail' || $this->crud->typeReport == 'designer'){
            CRUD::addColumns([
                [
                    'label' => 'RC / DPW',
                    'type' => 'text',
                    'name' => 'rc_dpw_name'
                ],
                [
                    'label' => 'Church Name',
                    'type' => 'text',
                    'name' => 'church_name',
                ],
                [
                    'label' => 'Church Type',
                    'type' => 'text',
                    'name' => 'entities_type'
                ],
                [
                    'label' => 'Local Church',
                    'type' => 'closure',
                    'name' => 'local_church',
                    'function' => function($entries){
                        $lc = null;
                        $church = Church::where('id' , $entries->church_local_id)->first();
                        if ($church) {
                            $lc = $church->church_name;
                        }
                        return $lc;
                    }
                ],
                [
                    'label' => 'Lead Pastor Name',
                    'type' => 'text',
                    'name' => 'lead_pastor_name'
                ],
                [
                    'label' => 'Leadership Structure',
                    'type' => 'closure',
                    'name' => 'leadership_structure',
                    'function' => function($entries){
                        $leaderships = StructureChurch::join('personels', 'personels.id', 'structure_churches.personel_id')
                            ->join('ministry_roles', 'ministry_roles.id', 'structure_churches.title_structure_id')
                            ->join('title_lists', 'title_lists.id', 'personels.title_id')
                            ->where('structure_churches.churches_id', $entries->id)
                            ->get(['structure_churches.id as id', 'ministry_roles.ministry_role as ministry_role', 
                            'title_lists.short_desc', 'title_lists.long_desc','personels.first_name', 'personels.last_name']);
                        $str_leadership = "";  
                        $total = sizeof($leaderships) - 1;
                        foreach ($leaderships as $key => $leadership) {
                            $str_leadership .= $leadership->first_name." ".$leadership->last_name. " (".$leadership->ministry_role.")";
                            if ($key < $total) {
                                $str_leadership .= "<br>";
                            }
                        }
                        return $str_leadership;
                    }
                ],
                [
                    'label' => 'Coordinator',
                    'type' => 'text',
                    'name' => 'coordinator_name'
                ],
                [
                    'label' => 'Contact Person',
                    'type' => 'text',
                    'name' => 'contact_person'
                ],
                [
                    'label' => 'Church Address',
                    'type' => 'text',
                    'name' => 'church_address'
                ],
                [
                    'label' => 'Office Address',
                    'type' => 'text',
                    'name' => 'office_address'
                ],
                [
                    'label' => 'City',
                    'type' => 'text',
                    'name' => 'city'
                ],
                [
                    'label' => 'State',
                    'type' => 'text',
                    'name' => 'province'
                ],
                [
                    'label' => 'Postcode',
                    'type' => 'text',
                    'name' => 'postal_code'
                ],
                [
                    'label' => 'Country',
                    'type' => 'text',
                    'name' => 'country_name'
                ],
                [
                    'label' => 'Phone',
                    'type' => 'text',
                    'name' => 'phone'
                ],
                [
                    'label' => 'Fax',
                    'type' => 'text',
                    'name' => 'fax'
                ],
                [
                    'label' => 'Email',
                    'type' => 'text',
                    'name' => 'first_email'
                ],
                [
                    'label' => 'Secondary Email',
                    'type' => 'text',
                    'name' => 'second_email'
                ],
                [
                    'label' => 'Last Church Status',
                    'type' => 'closure',
                    'name' => 'status',
                    'function' => function($entries){
                        return $entries->status != null ? $entries->status : '-';
                    }
                ],
                [
                    'label' => 'Last Status Date',
                    'type' => 'closure',
                    'name' => 'date_status',
                    'function' => function($entries){
                        return $entries->date_status != null ? $entries->date_status : '-';
                    }
                ],
                [
                    'label' => 'Founded On',
                    'type' => 'text',
                    'name' => 'founded_on'
                ],
                [
                    'label' => 'Service Time Church',
                    'type' => 'text',
                    'name' => 'service_time_church'
                ],
                [
                    'label' => 'Task Color',
                    'type' => 'text',
                    'name' => 'task_color'
                ],
                [
                    'label' => 'Latitude',
                    'type' => 'text',
                    'name' => 'latitude'
                ],
                [
                    'label' => 'Longitude',
                    'type' => 'text',
                    'name' => 'longitude'
                ],
                [
                    'label' => 'Notes',
                    'type' => 'text',
                    'name' => 'notes'
                ]
            ]);
        }
        else{
            $this->crud->denyAccess('list');
        }
        
    }

    public function setupListReport()
    {
        $detailYear = $this->getCurrentYear();
        $crudModel = $this->crud->typeReport == "annual" ? \App\Models\ChurchAnnualView::class : \App\Models\ChurchAnnualDesignerView::class;
        $crudRoute = $this->crud->typeReport == "annual" ? 
        config('backpack.base.route_prefix') . '/church-annual-report' : 
        ( $this->crud->typeReport == "designer" ? config('backpack.base.route_prefix') . '/church-report-designer' : 
        config('backpack.base.route_prefix') . '/church-annual-report/' . $detailYear . '/detail');
        $entityName = $this->crud->typeReport == "annual" ? "Church Annual Report" :  ( $this->crud->typeReport == "designer" ? "Church Report Designer" :
        "Church List " . $detailYear);
        $this->crud->entityName = $entityName;
        $this->crud->entityNameAnnual = "Church Annual Report";
        $this->crud->routeAnnual = config('backpack.base.route_prefix') . '/church-annual-report';
        $this->crud->routeDesigner = config('backpack.base.route_prefix') . '/church-report-designer';
        $this->crud->viewAfterContent = ['export_report'];
        $this->crud->routeExport =  $this->crud->typeReport == "annual" ?  '/church-annual-report' : ( $this->crud->typeReport == "designer" ? '/church-report-designer' : 
        '/church-annual-report/' . $detailYear . '/detail');

        CRUD::setModel($crudModel);
        CRUD::setRoute($crudRoute);
        CRUD::setEntityNameStrings($entityName, $entityName);

        $this->crud->addButtonFromModelFunction('top', 'exportButton', 'ExportExcelButton');
        if($this->crud->typeReport == "annual"){
            $this->crud->orderBy("year");
            $this->crud->addButtonFromModelFunction('line', 'detailButton', 'DetailButton');
        }
        else if($this->crud->typeReport == "detail"){
            $this->crud->addClause('year', $detailYear);
        }
        else{
            if(! request()->ajax()){
                $this->crud->rc_dpw = RcDpwList::select('id', 'rc_dpw_name')->get();
                $this->crud->churchType = ChurchEntityType::select('id', 'entities_type')->get();
                $this->crud->country = CountryList::select('id', 'country_name')->get();
                $this->crud->churchStatus = ChurchAnnualDesignerView::select('status')->groupBy('status')->get();
            }
            if ($this->crud->getRequest()->filled('rc_dpw_id')) {
                try{
                    $value = json_decode($this->crud->getRequest()->rc_dpw_id);
                    if(is_array($value)){
                       $this->crud->addClause('whereIn', 'rc_dpw_name', $value);
                    //    $value = array_map(function($d){
                    //         return "'$d'";
                    //    }, $value);
                    //    $value = implode(',', $value);
                    //    $this->crud->query->whereRaw("EXISTS (SELECT 1 FROM churches_rcdpw 
                    //    INNER JOIN rc_dpwlists ON rc_dpwlists.id = churches_rcdpw.rc_dpwlists_id
                    //    WHERE churches_rcdpw.churches_id = church_annual_designer_views.id AND rc_dpwlists.rc_dpw_name IN ({$value}))");
                    }
                    else{
                        $this->crud->addClause('whereRaw', 0);
                    }
                }
                catch(Exception $e){
                    $this->crud->addClause('whereRaw', 0);
                    throw $e;
                }
            }
            if ($this->crud->getRequest()->filled('church_type_id')) {
                try{
                    $value = json_decode($this->crud->getRequest()->church_type_id);
                    if(is_array($value)){
                        $this->crud->addClause('whereIn', 'entities_type', $value);
                    }
                    else{
                        $this->crud->addClause('whereRaw', 0);
                    }
                }
                catch(Exception $e){
                    $this->crud->addClause('whereRaw', 0);
                    throw $e;
                }
            }
            if ($this->crud->getRequest()->filled('country_id')) {
                try{
                    $value = json_decode($this->crud->getRequest()->country_id);
                    if(is_array($value)){
                        $this->crud->addClause('whereIn', 'country_name', $value);
                    }
                    else{
                        $this->crud->addClause('whereRaw', 0);
                    }
                }
                catch(Exception $e){
                    $this->crud->addClause('whereRaw', 0);
                    throw $e;
                }
            }
            if ($this->crud->getRequest()->filled('church_status_id')) {
                try{
                    $value = json_decode($this->crud->getRequest()->church_status_id);
                    if(is_array($value)){
                        $this->crud->addClause('whereIn', 'status', $value);
                    }
                    else{
                        $this->crud->addClause('whereRaw', 0);
                    }
                    // $this->crud->addClause('where', 'status', $this->crud->getRequest()->pastor_status_id);
                }
                catch(Exception $e){
                    $this->crud->addClause('whereRaw', 0);
                    throw $e;
                }
            }
            $this->crud->viewBeforeContent = ['annualreport.report_designer_panel'];
        }
        
    }



    private function getCurrentType()
    {
        $route = explode('/',Route::current()->uri);

        return Route::current()->parameter('year') != null ? 'detail' : (preg_match('/church-report-designer/', $route[1]) ? 'designer' : 'annual');
    }

    private function getCurrentYear()
    {
        return Route::current()->parameter('year');
    }

    public function exportReport(Request $request)
    {
        $visibleColumn = $request->visible_column;
        $type = 'church_' . $this->crud->typeReport;
        $year = $this->getCurrentYear() ?? 0;
        $fileName = $this->crud->typeReport == 'annual' ? 'Church Annual Report' : ($this->crud->typeReport == 'detail' ? 'Church List ' . $year : 'Church Report');
        $this->setupListOperation();
        $columnList = CRUD::columns();
        $realVisibleColumn = [];
        $index = 0;
        $filterBy = [];
        if($this->crud->typeReport == 'designer')
        {
            if($request->rc_dpw_id != "null"){
                $filterBy['rc_dpw_name'] = $request->rc_dpw_id;
            }
            if($request->church_type_id != "null"){
                $filterBy['entities_type'] = $request->church_type_id;
            }
            if($request->country_id != "null"){
                $filterBy['country_name'] = $request->country_id;
            }
            if($request->church_status_id != "null"){
                $filterBy['status'] = $request->church_status_id;
            }
        }
        
        foreach($columnList as $indexColumn => $columnData){
            if($this->crud->typeReport  != 'designer' || (isset($visibleColumn) && in_array($index, $visibleColumn))){
                $realVisibleColumn[$indexColumn] = $columnData['label'];
            }
            $index++;
        }
        return Excel::download(new ExportAnnualReport($type, $realVisibleColumn, $year, $filterBy), $fileName . '.xlsx');
    }
}
