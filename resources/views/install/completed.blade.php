@extends("install.layouts.base")

@section("title", "📥 Install")
@section("sub_title")
{!! "<span class='text-success'>🎉 Installed Successfully</span>" !!}
@endsection

@section("content")
<div class="d-flex justify-content-center my-5">
	<div class="card" style="width: 600px;">
		<div class="card-body">
			<div class="alert alert-info text-start">❗ Ensure to copy your bellow admin credentials.</div>

			<b>Admin Credentials:</b><br>
			<pre class="">
Email: {{ $admin["email"] }}
Password: {{ $admin["password"] }}
			</pre>

			<div class="d-flex justify-content-center gap-2">
				<a href="{{ url('/') }}" target="_blank" class="btn btn-secondary">Home Page</a>
				<a href="{{ url('/admin') }}" target="_blank" class="btn btn-info text-white">Admin Dashboard</a>
			</div>
		</div>
	</div>
</div>
@endsection
