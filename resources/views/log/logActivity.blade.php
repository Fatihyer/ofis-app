<!DOCTYPE html>
<html>
<head>
	<title>Log Activity Lists</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css" />
</head>
<body>


<div class="container">
	<h1>Log Activity Lists</h1>
 @if ($file)<a class="btn btn-success" href="{{route('posts.show',$file)}}">RETURN TO FILE</a>
 @endif 
	<table class="table table-bordered">
		<tr>
			<th>No</th>
      
			<th>Subject</th>
      <th>File</th>
			<th>URL</th>
			<th>Method</th>
			<th>date</th>
		
			<th>User Id</th>
			<th>Action</th>
		</tr>
		@if($logs->count())
			@foreach($logs as $key => $log)
			<tr>
				<td>{{ ++$key }}</td>
				<td>{{ $log->subject }}</td>
        <td><a href="{{route('posts.show',$log->post_id)}}">{{$log->post_id}}</a></td>
				<td class="text-success">{{ $log->url }}</td>
				<td><label class="label label-info">{{ $log->method }}</label></td>
				<td class="text-warning">{{ $log->created_at }}</td>
		
				<td>@if (isset($log->user->name)){{ $log->user->name }}@endif   </td>
				<td>{{ $log->changes }}</td>
			</tr>
			@endforeach
		@endif
	</table>
</div>


</body>
</html>