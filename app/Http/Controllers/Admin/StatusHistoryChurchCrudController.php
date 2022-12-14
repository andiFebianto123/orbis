<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StatusHistoryChurchRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\Church;
use App\Models\StatusHistoryChurch;
use App\Helpers\HitApi;

/**
 * Class StatusHistoryChurchCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class StatusHistoryChurchCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\StatusHistoryChurch::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/statushistorychurch');
        CRUD::setEntityNameStrings('Church Status Histories', 'Church Status Histories');
        $this->crud->currentId = request()->churches_id;
        $this->crud->redirectTo = backpack_url('church/'.$this->crud->currentId.'/show');
        $isChurchExists =  Church::where('id',$this->crud->currentId)->first();
        if($isChurchExists == null){
            abort(404);
        }
        $this->crud->saveOnly=true;
    }

    public function index()
    {
        abort(404);
    }
    
    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->addColumn([
            'name' => 'id', // The db column name
            'label' => "ID", // Table column heading
            'type' => 'number'
        ]);

        $this->crud->addColumn([
            'name' => 'status', // The db column name
            'label' => "Status", // Table column heading
            'type' => 'text'
        ]);

        $this->crud->addColumn([
            'name' => 'reason', // The db column name
            'label' => "Reason", // Table column heading
            'type' => 'text'
        ]);

        $this->crud->addColumn([
            'name' => 'date_status', // The db column name
            'label' => "Date", // Table column heading
            'type' => 'date'
        ]);

        $this->crud->addColumn([
            'name' => 'churches_id', // The db column name
            'label' => "Church", // Table column heading
            'type' => 'relationship',
            'attribute' => 'church_name',
        ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(StatusHistoryChurchRequest::class);

        $this->crud->addField([
            'name'            => 'status',
            'label'           => "Status",
            'options'         => ['Active' => "Active", 'Non-active' => "Non-active"],
            'type'            => 'select2_from_array',
        ]);

        $this->crud->addField([
            'name'            => 'reason',
            'label'           => "Reason",
            'type'            => 'text',
        ]);

        $this->crud->addField([
            'name'  => 'date_status',
            'type'  => 'date_picker',
            'label' => 'Date Status',

            // optional:
            'date_picker_options' => [
                'todayBtn' => 'linked',
                'format'   => 'dd-mm-yyyy',
                'language' => 'en'
            ],
        ]);

        $this->crud->addField([
            'label'     => 'Church', // Table column heading
            'type'      => 'hidden',
            'name'      => 'churches_id', // the column that contains the ID of that connected entity;
            'default'   => request('churches_id')
        ]);
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

    public function store()
    {
        $this->crud->hasAccessOrFail('create');

        // execute the FormRequest authorization and validation, if one is required
        $request = $this->crud->validateRequest();


        $current_status_now = StatusHistoryChurch::leftJoin('status_history_churches as temps', function($leftJoin){
            $leftJoin->on('temps.churches_id', 'status_history_churches.churches_id')
            ->where(function($innerQuery){
                $innerQuery->whereRaw('status_history_churches.date_status < temps.date_status')
                ->orWhere(function($deepestQuery){
                    $deepestQuery->whereRaw('status_history_churches.date_status = temps.date_status')
                    ->where('status_history_churches.id', '<', 'temps.id');
                });
            });
        })->whereNull('temps.id')
        ->where('status_history_churches.churches_id', $this->crud->currentId)
        ->select('status_history_churches.churches_id', 'status_history_churches.status')->first()->status ?? '-';

        // insert item in the db
        $item = $this->crud->create($this->crud->getStrippedSaveRequest());
        $this->data['entry'] = $this->crud->entry = $item;

        $current_status_last = StatusHistoryChurch::leftJoin('status_history_churches as temps', function($leftJoin){
            $leftJoin->on('temps.churches_id', 'status_history_churches.churches_id')
            ->where(function($innerQuery){
                $innerQuery->whereRaw('status_history_churches.date_status < temps.date_status')
                ->orWhere(function($deepestQuery){
                    $deepestQuery->whereRaw('status_history_churches.date_status = temps.date_status')
                    ->where('status_history_churches.id', '<', 'temps.id');
                });
            });
        })->whereNull('temps.id')
        ->where('status_history_churches.churches_id', $item->churches_id)
        ->select('status_history_churches.churches_id', 'status_history_churches.status')->first()->status ?? '-';

        if($current_status_now != $current_status_last){
            $send = new HitApi;
            $id = [$item->churches_id];
            $module = 'sub_region';
            $response = $send->action($id, 'update', $module)->json();
        }

        // show a success message
        \Alert::success(trans('backpack::crud.insert_success'))->flash();

        return redirect(backpack_url('church/'.$item->churches_id.'/show'));
    }

    public function update()
    {
        $this->crud->hasAccessOrFail('update');

        // execute the FormRequest authorization and validation, if one is required
        $request = $this->crud->validateRequest();

        $current_status_now = StatusHistoryChurch::leftJoin('status_history_churches as temps', function($leftJoin){
            $leftJoin->on('temps.churches_id', 'status_history_churches.churches_id')
            ->where(function($innerQuery){
                $innerQuery->whereRaw('status_history_churches.date_status < temps.date_status')
                ->orWhere(function($deepestQuery){
                    $deepestQuery->whereRaw('status_history_churches.date_status = temps.date_status')
                    ->where('status_history_churches.id', '<', 'temps.id');
                });
            });
        })->whereNull('temps.id')
        ->where('status_history_churches.churches_id', $this->crud->currentId)
        ->select('status_history_churches.churches_id', 'status_history_churches.status')->first()->status ?? '-';

        // update the row in the db
        $item = $this->crud->update($request->get($this->crud->model->getKeyName()),
                            $this->crud->getStrippedSaveRequest());
        $this->data['entry'] = $this->crud->entry = $item;

        $current_status_last = StatusHistoryChurch::leftJoin('status_history_churches as temps', function($leftJoin){
            $leftJoin->on('temps.churches_id', 'status_history_churches.churches_id')
            ->where(function($innerQuery){
                $innerQuery->whereRaw('status_history_churches.date_status < temps.date_status')
                ->orWhere(function($deepestQuery){
                    $deepestQuery->whereRaw('status_history_churches.date_status = temps.date_status')
                    ->where('status_history_churches.id', '<', 'temps.id');
                });
            });
        })->whereNull('temps.id')
        ->where('status_history_churches.churches_id', $item->churches_id)
        ->select('status_history_churches.churches_id', 'status_history_churches.status')->first()->status ?? '-';

        if($current_status_now != $current_status_last){
            $send = new HitApi;
            $id = [$item->churches_id];
            $module = 'sub_region';
            $response = $send->action($id, 'update', $module)->json();
        }

        // show a success message
        \Alert::success(trans('backpack::crud.update_success'))->flash();

        return redirect(backpack_url('church/'.$item->churches_id.'/show'));    
    }

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');

        // get entry ID from Request (makes sure its the last ID for nested resources)
        $id = $this->crud->getCurrentEntryId() ?? $id;

        $item = $this->crud->getEntry($id);

        $current_status_now = StatusHistoryChurch::leftJoin('status_history_churches as temps', function($leftJoin){
            $leftJoin->on('temps.churches_id', 'status_history_churches.churches_id')
            ->where(function($innerQuery){
                $innerQuery->whereRaw('status_history_churches.date_status < temps.date_status')
                ->orWhere(function($deepestQuery){
                    $deepestQuery->whereRaw('status_history_churches.date_status = temps.date_status')
                    ->where('status_history_churches.id', '<', 'temps.id');
                });
            });
        })->whereNull('temps.id')
        ->where('status_history_churches.churches_id', $item->churches_id)
        ->select('status_history_churches.churches_id', 'status_history_churches.status')->first()->status ?? '-';

        $delete = $this->crud->delete($id);

        $current_status_last = StatusHistoryChurch::leftJoin('status_history_churches as temps', function($leftJoin){
            $leftJoin->on('temps.churches_id', 'status_history_churches.churches_id')
            ->where(function($innerQuery){
                $innerQuery->whereRaw('status_history_churches.date_status < temps.date_status')
                ->orWhere(function($deepestQuery){
                    $deepestQuery->whereRaw('status_history_churches.date_status = temps.date_status')
                    ->where('status_history_churches.id', '<', 'temps.id');
                });
            });
        })->whereNull('temps.id')
        ->where('status_history_churches.churches_id', $item->churches_id)
        ->select('status_history_churches.churches_id', 'status_history_churches.status')->first()->status ?? '-';

        if($current_status_now != $current_status_last){
            $send = new HitApi;
            $id = [$item->churches_id];
            $module = 'sub_region';
            $response = $send->action($id, 'update', $module)->json();
        }

        return $delete;
    }

}
