@extends("install.layouts.base")

@section("title", "📥 Install")
@section("sub_title", "Database Credentials")

@section("content")
<div class="d-flex justify-content-center my-5">
	<div class="card" style="width: 600px;">
		<div class="card-body text-left">

			@if(session('error'))
		        <div class="alert alert-danger">{{ session('error') }}</div>
		    @endif

			<form action="{{ url('install/database') }}" method="post" class="m-4 mx-2">
				{{ csrf_field() }}

				<div class="mb-3 row">
				  <label for="db_host" class="col-sm-3 col-form-label">DB Host</label>
				  <div class="col-sm-9">
				    <input type="text" class="form-control" id="db_host" name="db_host" value="localhost">
				  </div>
				</div>

				<div class="mb-3 row">
				  <label for="DB_name" class="col-sm-3 col-form-label">DB Name</label>
				  <div class="col-sm-9">
				    <input type="text" class="form-control" id="DB_name" name="db_name">
				  </div>
				</div>

				<div class="mb-3 row">
				  <label for="db_username" class="col-sm-3 col-form-label">DB Username</label>
				  <div class="col-sm-9">
				    <input type="text" class="form-control" id="db_username" name="db_user">
				  </div>
				</div>

				<div class="mb-5 row">
				  <label for="db_pass" class="col-sm-3 col-form-label">DB Password</label>
				  <div class="col-sm-9">
				    <input type="password" class="form-control" id="db_pass" name="db_pass">
				  </div>
				</div>

				@if (!$results["errors"])
					<div class="mt-3">
						<button type="submit" class="btn btn-primary w-100 btn-lg">Continue</buttom>
					</div>
				@endif
			</form>
		</div>
	</div>
</div>
@endsection
