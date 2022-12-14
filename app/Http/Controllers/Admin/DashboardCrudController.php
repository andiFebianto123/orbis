<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\DashboardRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\Church;
use App\Models\StatusHistoryChurch;
use App\Models\StatusHistory;
use App\Models\Personel;
use App\Models\SpecialRolePersonel;
use App\Models\StructureChurch;
use App\Models\LegalDocumentChurch;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * Class DashboardCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class DashboardCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Dashboard::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/dashboard');
        CRUD::setEntityNameStrings('dashboard', 'Dashboard');
    }

    public function index()
    {
        $church_count = StatusHistoryChurch::leftJoin('status_history_churches as temps', function($leftJoin){
                            $leftJoin->on('temps.churches_id', 'status_history_churches.churches_id')
                            ->where(function($innerQuery){
                                $innerQuery->whereRaw('status_history_churches.date_status < temps.date_status')
                                ->orWhere(function($deepestQuery){
                                    $deepestQuery->whereRaw('status_history_churches.date_status = temps.date_status')
                                    ->where('status_history_churches.id', '<', 'temps.id');
                                });
                            });
                        })->whereNull('temps.id')
                        ->where('status_history_churches.status', 'Active');

        $personel_count = StatusHistory::leftJoin('status_histories as temps', function($leftJoin){
                            $leftJoin->on('temps.personel_id', 'status_histories.personel_id')
                            ->where(function($innerQuery){
                                $innerQuery->whereRaw('status_histories.date_status < temps.date_status')
                                ->orWhere(function($deepestQuery){
                                    $deepestQuery->whereRaw('status_histories.date_status = temps.date_status')
                                    ->where('status_histories.id', '<', 'temps.id');
                                });
                            });
                        })->whereNull('temps.id')
                        ->where('status_histories.status', 'Active');
                        // ->where('status_histories.status_histories_id', '1');

        $today_birthday = Personel::whereDay('date_of_birth', Carbon::now()->day)
                    ->whereMonth('date_of_birth', Carbon::now()->month)
                    ->select('first_name', DB::raw('count(first_name) as total'))
                    ->groupBy('first_name')
                    ->get();
        $country_tables = Church::join('country_lists','churches.country_id','country_lists.id')
                 ->select('country_name', DB::raw('count(country_name) as total'))
                 ->groupBy('country_name')
                 ->get();
        $type_tables = Church::join('church_types','churches.church_type_id','church_types.id')
                 ->select('entities_type', DB::raw('count(entities_type) as total'))
                 ->groupBy('entities_type')
                 ->get();
        $personel_tables = Personel::join('title_lists','personels.title_id','title_lists.id')
                 ->select('long_desc', DB::raw('count(long_desc) as total'))
                 ->groupBy('long_desc')
                 ->get();
                 
        $rcdpw_tables = Church::join('churches_rcdpw', 'churches.id', 'churches_rcdpw.churches_id')
        ->join('rc_dpwlists', 'churches_rcdpw.rc_dpwlists_id', 'rc_dpwlists.id')
        ->select('rc_dpwlists.rc_dpw_name', DB::raw('count(rc_dpwlists.rc_dpw_name) as total'))
        ->groupBy('rc_dpwlists.rc_dpw_name')->get();

        // $rcdpw_tables = Church::join('rc_dpwlists','churches.rc_dpw_id','rc_dpwlists.id')
        //          ->select('rc_dpw_name', DB::raw('count(rc_dpw_name) as total'))
        //          ->groupBy('rc_dpw_name')
        //          ->get();
        
        $personel_vip_tables = SpecialRolePersonel::join('special_roles','special_role_personels.special_role_id','special_roles.id')
                 ->select('special_role', DB::raw('count(special_role) as total'))
                 ->groupBy('special_role')
                 ->get();
        $ministry_role_tables = StructureChurch::join('ministry_roles','structure_churches.title_structure_id','ministry_roles.id')
                 ->select('ministry_role', DB::raw('count(ministry_role) as total'))
                 ->groupBy('ministry_role')
                 ->get();
        $pastors_birthday_tables = Personel::whereMonth('date_of_birth', Carbon::now()->month)
                    ->join('title_lists','personels.title_id','title_lists.id')
                    ->select('first_name', 'date_of_birth', 'short_desc')
                    ->get();
        $pastors_anniversary_tables = Personel::whereMonth('anniversary', Carbon::now()->month)
                    // ->where('marital_status', 'married')
                    ->join('title_lists','personels.title_id','title_lists.id')
                    ->select('first_name', 'anniversary', 'short_desc')
                    ->get();
        $id_card_expiration_tables = Personel::whereMonth('valid_card_end', Carbon::now()->month)
                    ->whereYear('valid_card_end', Carbon::now()->year)
                    ->join('title_lists','personels.title_id','title_lists.id')
                    ->select('first_name', 'valid_card_end', 'short_desc')
                    ->get();
        $license_expiration_tables = LegalDocumentChurch::whereMonth('exp_date', Carbon::now()->month)
                    ->whereYear('exp_date', Carbon::now()->year)
                    ->join('legal_documents','legal_document_churches.legal_document_id','legal_documents.id')
                    ->join('churches','legal_document_churches.churches_id','churches.id')
                    ->select('church_name','documents','exp_date')
                    ->get();
        $inactive_church_tables = StatusHistoryChurch::where('status', 'Non-active')
                    ->whereYear('date_status', Carbon::now()->year)
                    ->leftJoin('churches','status_history_churches.churches_id','churches.id')
                    ->select('church_name', 'date_status')
                    ->get();

        // $inactive_pastor_tables = StatusHistory::whereNotIn('status_histories_id', [1,4])
        //             ->whereYear('date_status', Carbon::now()->year)
        //             ->leftJoin('personels','status_histories.personel_id','personels.id')
        //             ->select('first_name','last_name','date_status')
        //             ->get();

        $inactive_pastor_tables = StatusHistory::whereNotIn('status', ['Active','pending'])
                    ->whereYear('date_status', Carbon::now()->year)
                    ->leftJoin('personels','status_histories.personel_id','personels.id')
                    ->select('first_name','last_name','date_status')
                    ->get();

        $new_pastor_tables = Personel::whereMonth('valid_card_start', Carbon::now()->month)
                    ->whereYear('valid_card_start', Carbon::now()->year)
                    ->join('title_lists','personels.title_id','title_lists.id')
                    ->select('first_name', 'valid_card_start', 'short_desc')
                    ->get();
        $new_church_tables = Church::whereMonth('founded_on', Carbon::now()->month)
                    ->whereYear('founded_on', Carbon::now()->year)
                    ->get();

        $data['church_count'] = $church_count->count();
        $data['country_count'] = $country_tables->count();
        $data['personel_count'] = $personel_count->count();
        $data['today_birthday'] = $today_birthday->count();

        $data['type_tables'] = $type_tables;
        $data['country_tables'] = $country_tables;
        $data['personel_tables'] = $personel_tables;
        $data['rcdpw_tables'] = $rcdpw_tables;
        $data['personel_vip_tables'] = $personel_vip_tables;
        $data['ministry_role_tables'] = $ministry_role_tables;
        $data['pastors_birthday_tables'] = $pastors_birthday_tables;
        $data['pastors_anniversary_tables'] = $pastors_anniversary_tables;
        $data['id_card_expiration_tables'] = $id_card_expiration_tables;
        $data['license_expiration_tables'] = $license_expiration_tables;
        $data['inactive_church_tables'] = $inactive_church_tables;
        $data['inactive_pastor_tables'] = $inactive_pastor_tables;
        $data['new_pastor_tables'] = $new_pastor_tables;
        $data['new_church_tables'] = $new_church_tables;

        // return dd($data);
        return view('vendor.backpack.base.dashboard',$data);
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::setFromDb(); // columns

        /**
         * Columns can be defined using the fluent syntax or array syntax:
         * - CRUD::column('price')->type('number');
         * - CRUD::addColumn(['name' => 'price', 'type' => 'number']); 
         */
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(DashboardRequest::class);

        CRUD::setFromDb(); // fields

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number'])); 
         */
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
