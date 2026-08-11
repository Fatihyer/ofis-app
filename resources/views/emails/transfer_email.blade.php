<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transfer Details</title>
</head>
<body>
    <p>Bonjour,
        <br>
        Vous pouvez trouver ci-dessous votre billet collectif - ordre de mission</p>
    <h2>Transfer Details</h2>
    <ul>
    <li><strong> ID:</strong> {{ $transfer->id }}</li>
    <li><strong>Passenger:</strong> {{ $transfer->pax}}</li>
      <ul>  @foreach($transfer->post->client as $clients) 
    <li>{{$clients->name." ".$clients->surname." ".$clients->tel}}</li>
   @endforeach </ul>
    <li><strong>Pickup Time: :</strong>{{date('d-m-Y D H:i', strtotime($transfer->start_date))}}</li>
    <li><strong>Pickup Adress: :</strong>{{$transfer->from}}</li>
    <li><strong>Dropoff Address :</strong>{{$transfer->target}}</li>
    <li><strong>Vehicule:</strong> {{$transfer->vehicule->name}}</li>
    <li><strong>Payment:</strong>{{(isset($transfer->harekets[0]->payment->name)?$transfer->harekets[0]->payment->name:"")}}</li>
    <li><strong>Driver/Company:</strong> {{$transfer->driver->name}}</li>    
    <li><strong>Comment:</strong> {{$transfer->comments}}</li>    


</ul>

Mission link : http://ofis.francepanoramic.com/m/{{$transfer->mission_url}}




    <!-- Include other transfer details as needed -->
</body>
</html>