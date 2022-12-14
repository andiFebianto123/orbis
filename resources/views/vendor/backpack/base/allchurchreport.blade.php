@extends(backpack_view('blank'))
@section('content')

<div class="row">
    <div class="col-md-12">
  		<div class="card">
			<div class="card-header" style="background: #b5c7e0; font-weight:bold;">
			  	Church List
  			</div>
				<div class="card-body">
					<div class = "row">
						<div class="col-md-12">
							<table id ="tableAllChurch" class = "table table-striped">
								<thead>
									<tr>
										<th>No.</th>
										<th>RC / DPW</th>
										<th>Church Name</th>
										<th>Church Type</th>
										<th>Lead Pastor Name</th>
										<th>Contact Person</th>
										<th>Church Address</th>
										<th>Office Address</th>
										<th>City</th>
										<th>Province / State</th>
										<th>Postal Code</th>
										<th>Country</th>
										<th>Phone</th>
										<th>Fax</th>
										<th>Email</th>
										<th>Church Status</th>
										<th>Founded On</th>
										<th>Service Time Church</th>
										<th>Notes</th>
									</tr>
								</thead>
								<tbody>
									@foreach($all_church_tables as $key => $all_church_table)
										<tr>
											<td></td>
											<td>{{$all_church_table->rc_dpw_name}}</td>
											<td>{{$all_church_table->church_name}}</td>
											<td>{{$all_church_table->entities_type}}</td>
											<td>{{$all_church_table->lead_pastor_name}}</td>
											<td>{{$all_church_table->contact_person}}</td>
											<td>{{$all_church_table->church_address}}</td>
											<td>{{$all_church_table->office_address}}</td>
											<td>{{$all_church_table->city}}</td>
											<td>{{$all_church_table->province}}</td>
											<td>{{$all_church_table->postal_code}}</td>
											<td>{{$all_church_table->country_name}}</td>
											<td>{{$all_church_table->phone}}</td>
											<td>{{$all_church_table->fax}}</td>
											<td>{{$all_church_table->first_email}}</td>
											<td>{{
													App\Models\StatusHistoryChurch::leftJoin('status_history_churches as temps', function($leftJoin){
														$leftJoin->on('temps.churches_id', 'status_history_churches.churches_id')
														->where(function($innerQuery){
															$innerQuery->whereRaw('status_history_churches.date_status < temps.date_status')
															->orWhere(function($deepestQuery){
																$deepestQuery->whereRaw('status_history_churches.date_status = temps.date_status')
																->whereRaw('status_history_churches.id < temps.id');
															});
														});
													})->whereNull('temps.id')
													->where('status_history_churches.churches_id', $all_church_table->id)
													->select('status_history_churches.churches_id', 'status_history_churches.status')->first()->status ?? '-'
												}}
											</td>
											<td>{{$all_church_table->founded_on}}</td>
											<td>{{$all_church_table->service_time_church}}</td>
											<td>{{$all_church_table->notes}}</td>
										</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection

@section('after_styles')
	<link rel="stylesheet" href="{{ asset('packages/backpack/crud/css/crud.css').'?v='.config('backpack.base.cachebusting_string') }}">
	<link rel="stylesheet" href="{{ asset('packages/backpack/crud/css/show.css').'?v='.config('backpack.base.cachebusting_string') }}">

	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css">

    <style>
		.active{
		background:aliceblue;
		}

		.bg-light {
			background-color: #f9fbfd !important;
		}

		body{
			background: #f9fbfd;
		}
	</style>
@endsection

@section('after_scripts')

	<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
	<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>

	<script src ="https://code.jquery.com/jquery-3.5.1.js"></script>
	<script src ="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
	<script src ="https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js"></script>
	<script src ="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
	<script src ="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
	<script src ="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
	<script src ="https://cdn.datatables.net/buttons/1.7.0/js/buttons.html5.min.js"></script>
	<script src ="https://cdn.datatables.net/buttons/1.7.0/js/buttons.print.min.js"></script>

	<script>
		$(document).ready(function() {
		var t = $('#tableAllChurch').DataTable( {
        	"columnDefs": [ {
				"searchable": false,
				"orderable": false,
				"targets": 0
        	} ],
        	"order": [[ 1, 'asc' ]],
			"scrollY": 400,
        	"scrollX": true,
			"pagingType": "simple_numbers",
			dom: 'Bfrtip',
			buttons: [
				{
					extend: 'excel', 
					text: 'Export to Excel', 
					title: 'All Church',
					exportOptions: {
						columns: ':visible',
						format: {
							body: function ( data, column, row ) {
							return (column === 1 && column === 5) ? data.replace( /\n/g, '"&CHAR(10)&CHAR(13)&"' ) : data.replace(/(&nbsp;|<([^>]+)>)/ig, "");;
							}
						}
					},
					customize: function( xlsx ) {
						var sheet = xlsx.xl.worksheets['sheet1.xml'];
						// $('row c', sheet).attr( 's', '55' );
						$('row c', sheet).each(function(index) {
							if (index > 0) {
								$(this).attr('ht', 60);
								$(this).attr('customHeight', 2);
								$(this).attr( 's', '55' );
							}
						});
					}
				},
			]
		} );
		
		t.on( 'order.dt search.dt', function () {
			t.column(0, {search:'applied', order:'applied'}).nodes().each( function (cell, i) {
				cell.innerHTML = i+1;
			} );
    	} ).draw();
		$( "<hr>" ).insertAfter( ".buttons-excel" );
    	$(".dt-button").addClass("btn btn-sm btn btn-outline-primary");
		} );
  	</script>

@endsection