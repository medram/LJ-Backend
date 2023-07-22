@extends("install.layouts.base")

@section("title", "📥 Install")
@section("sub_title", "Server Requirements")

@section("content")
<div class="d-flex justify-content-center my-5">
	<div class="card" style="width: 600px;">
		<div class="card-body p-0">

			@if ($results["errors"])
				<div class="alert alert-warning m-3">Please ensure to enable all the requirements from your hosting panel.</div>
			@endif

			<ul class="list-group list-group-flush">
			  <li class="list-group-item d-flex justify-content-between">
			  	<div>
			  		<span>PHP Version ({{ $php_version }} minimum)</span>
			  	</div>
			  	@if ($results["php_version"])
				  	<span>✅</span>
			  	@else
			  		<span>❌</span>
			  	@endif
			  </li>
			  @foreach ($results["extensions"] as $type => $extensions)
				  @foreach ($extensions as $name => $status)
				  	<li class="list-group-item d-flex justify-content-between">
					  	<div>
					  		<span>{{ $name }}</span>
					  	</div>
				  		@if ($status)
						  	<span>✅</span>
					  	@else
					  		<span>❌</span>
					  	@endif
				  	</li>
				  @endforeach
			  @endforeach
			</ul>
			@if (!$results["errors"])
				<div class="p-3">
					<a href="database" class="btn btn-primary w-100 btn-lg">Continue</a>
				</div>
			@endif
		</div>
	</div>
</div>
@endsection
