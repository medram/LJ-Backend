@extends("install.layouts.base")

@section("title", "📥 Install")
@section("sub_title", "License/Purchase Code verification")

@section("content")
<div class="d-flex justify-content-center my-5">
	<div class="card" style="width: 600px;">
		<div class="card-body text-left">

			@if(session('error'))
		        <div class="alert alert-danger">{{ session('error') }}</div>
		    @endif

			<form action="{{ url('install/verify') }}" method="post" class="m-4 mx-2">
				{{ csrf_field() }}

				<div class="mb-3 row text-start">
				  <label for="lc" class="col-form-label">Purchase Code</label>
				  <div class="col-sm-12 my-2">
				    <input type="text" class="form-control" id="lc" name="lc">
				  </div>
				  <small class="mb-2"><a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-" target="_blank">Where to find mine?</a></small>
				</div>

				<div class="mt-3">
					<button type="submit" class="btn btn-primary w-100 btn-lg">Verify & Install</buttom>
				</div>
			</form>
		</div>
	</div>
</div>
@endsection
