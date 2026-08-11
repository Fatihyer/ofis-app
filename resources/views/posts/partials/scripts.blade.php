
<script src="https://cdn.tiny.cloud/1/0ndnl4vpulqi0sna0omx0ggs8jtxlwnv5ujlq0n0pvjtgit7/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<script>
tinymce.execCommand('mceRemoveControl', true, '#data-body');
  </script>
<script>



$(document).on('submit','.hareketupdate', function(e){
    e.preventDefault();

    var form = $(this);
    var submit = form.find("[type=submit]");
    var url = form.attr('action');
    var data = form.serialize();
    data += '&_method=PUT';

    $.ajax({
        type : 'POST',
        url : url,
        data : data,
        beforeSend: function(){
           submit.html("Chargement...");
           submit.prop("disabled", true);
        },
        success:function(data){
           submit.html('<i class="fas fa-save"></i>');
           submit.prop("disabled", false);
           var result = $('.result'+data.id);
            result.html('<i class="fa fa-check" style="color:#008000;"></i>');

            setTimeout(function(){
                result.fadeOut(500, function(){
                    $(this).html('').show();
                });
            }, 500);
        },
        error: function(xhr) {
           submit.prop("disabled", false);
           alert("Erreur : " + xhr.responseText);
        }
    });
});

 


</script>

  <script>  $('#editmessage').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget) // Button that triggered the modal
  var recipient = button.data('id') // Extract info from data-* attributes
  var recipmname = button.data('title') 
  var recipbody = button.data('body') 
  var reciptarih = button.data('tarih') 
  var recipuser = button.data('user_id') 
  var recipusername = button.data('user_name') 
  // If necessary, you could initiate an AJAX request here (and then do the updating in a callback).
  // Update the modal's content. We'll use jQuery here, but you could use a data binding library or other methods instead.
  var modal = $(this)
  modal.find('.modal-title').text('Modifier ' + recipmname)
  modal.find('.modal-body #data-id').val(recipient)
  modal.find('.modal-body #data-title').val(recipmname)
  modal.find('.modal-body #data-body').val(recipbody)    
  modal.find('.modal-body #data-tarih').val(reciptarih)  

  if (recipuser>=1) 
    {
            if (recipuser=={{Auth::id()}}){    
            modal.find('.modal-body #data-user').prop('checked',true);

            } 
           else{
              modal.find('.modal-body #data-user').prop('checked',false);
            document.getElementById("gizle").style.display='none';
            document.getElementById("gizle2").style.display='none';
             document.getElementById("sadeceuser").innerHTML="Seul "+recipusername+ " peut modifier. Sinon, créez un nouveau message";
           } 
 }  
else
  {
  modal.find('.modal-body #data-user').prop('checked',false);   
  }
  modal.find('.modal-body #data-user').val({{Auth::id()}});     
  
      
    tinymce.init({
    selector:'textarea.description',
    height: 600
    });   
})

   $('#editmessage').on('hidden.bs.modal', function (event) {
      tinyMCE.remove()
      document.getElementById("gizle").style.display='block';
      document.getElementById("sadeceuser").innerHTML="";
     });
    
    </script>

<script>
 $('#exampleModalLong').on('show.bs.modal', function (event) {
    tinymce.init({
    selector:'textarea.description',
    height: 600,
     plugins: "advcode",
   
    });  
})

</script>
<script>

$('*[data-poload]').click(function() {
    var e=$(this);
    e.off('click');
    $.get(e.data('poload'),function(d) {
        e.popover({content: d}).popover('show');
    });
});
</script>
<script>
  var exportButton = document.getElementById('exportButton');
  if (exportButton) {
      exportButton.addEventListener('click', function() {
          var table = document.getElementById('transfersTable');
          if (!table) return;
          var html = table.outerHTML;
          var url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
          var downloadLink = document.createElement("a");
          downloadLink.href = url;
          downloadLink.download = 'transfers.xls';
          document.body.appendChild(downloadLink);
          downloadLink.click();
          document.body.removeChild(downloadLink);
      });
  }
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
        var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl)
        });
    });
</script>
<script>
$('#post-comment').on('keyup', function () {
    let el = $(this);
    let id = el.data('id');
    let comment = el.val();
    let status = $('#post-comment-status');

    clearTimeout(el.data('timer'));

    let timer = setTimeout(function () {
        status.text('Enregistrement...');

        $.ajax({
            url: "{{ route('posts.comment.update') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                id: id,
                comment: comment
            },
            success: function () {
                status.text('Enregistré').fadeIn();
                setTimeout(()=>status.fadeOut(),2000);
            },
            error: function () {
                status.text('Erreur lors de l’enregistrement');
            }
        });
    }, 800);

    el.data('timer', timer);
});
</script>

<script>
(function () {
    let activeRoute = null;
    let activeMap = null;
    let activeRenderer = null;
    let googleCallbacks = [];

    window.postTransferRouteGoogleReady = function () {
        const callbacks = googleCallbacks.splice(0);
        callbacks.forEach(function (callback) { callback(); });
    };

    function setRouteStatus(message, isError) {
        const status = document.getElementById('postTransferRouteStatus');
        if (!status) return;
        status.textContent = message;
        status.classList.toggle('text-danger', !!isError);
        status.classList.toggle('text-muted', !isError);
    }

    function ensureGoogleMaps(callback) {
        if (window.google && google.maps) {
            callback();
            return;
        }

        googleCallbacks.push(callback);

        if (document.getElementById('postTransferRouteGoogleScript')) return;

        const apiKey = @json(config('services.google_maps.api_key'));
        if (!apiKey) {
            setRouteStatus('Clé Google Maps manquante.', true);
            return;
        }

        const script = document.createElement('script');
        script.id = 'postTransferRouteGoogleScript';
        script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(apiKey) + '&callback=postTransferRouteGoogleReady';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }

    function fillRouteSteps(route) {
        const list = document.getElementById('postTransferRouteSteps');
        if (!list) return;

        list.innerHTML = '';
        (route.points || []).forEach(function (point, index) {
            const label = index === 0
                ? 'A · Départ'
                : (index === route.points.length - 1 ? String.fromCharCode(65 + index) + ' · Arrivée' : String.fromCharCode(65 + index) + ' · Étape');
            const li = document.createElement('li');
            const labelEl = document.createElement('span');
            labelEl.className = 'route-step-label';
            labelEl.textContent = label;
            li.appendChild(labelEl);
            li.appendChild(document.createTextNode(point));
            list.appendChild(li);
        });
    }

    function renderRoute(route) {
        const mapElement = document.getElementById('postTransferRouteMap');
        if (!mapElement || !window.google || !google.maps) return;

        activeMap = new google.maps.Map(mapElement, {
            center: { lat: 48.8566, lng: 2.3522 },
            zoom: 8,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true
        });

        if (activeRenderer) {
            activeRenderer.setMap(null);
        }

        activeRenderer = new google.maps.DirectionsRenderer({
            map: activeMap,
            suppressMarkers: false,
            polylineOptions: {
                strokeColor: '#2563eb',
                strokeWeight: 5,
                strokeOpacity: 0.82
            }
        });

        const waypoints = (route.waypoints || [])
            .slice(0, 23)
            .map(function (location) {
                return { location: location, stopover: true };
            });

        setRouteStatus('Calcul de l’itinéraire...', false);

        new google.maps.DirectionsService().route({
            origin: route.origin,
            destination: route.destination,
            waypoints: waypoints,
            optimizeWaypoints: false,
            travelMode: google.maps.TravelMode.DRIVING
        }, function (result, status) {
            if (status === google.maps.DirectionsStatus.OK) {
                activeRenderer.setDirections(result);
                setRouteStatus('Itinéraire affiché.', false);
            } else {
                setRouteStatus('Impossible d’afficher cet itinéraire (' + status + ').', true);
            }
        });
    }

    $(document).on('click', '.post-transfer-map-btn', function () {
        try {
            activeRoute = JSON.parse($(this).attr('data-route') || '{}');
        } catch (error) {
            activeRoute = null;
        }

        if (!activeRoute || !activeRoute.origin || !activeRoute.destination) {
            setRouteStatus('Itinéraire indisponible.', true);
            return;
        }

        $('#postTransferRouteTitle').text(activeRoute.title || 'Carte itinéraire');
        $('#postTransferRouteGoogleLink').attr('href', activeRoute.maps_url || '#');
        fillRouteSteps(activeRoute);
        setRouteStatus('Ouverture de la carte...', false);
    });

    $('#postTransferRouteModal').on('shown.bs.modal', function () {
        if (!activeRoute) return;
        ensureGoogleMaps(function () {
            window.setTimeout(function () {
                renderRoute(activeRoute);
            }, 150);
        });
    });

    $('#postTransferRouteModal').on('hidden.bs.modal', function () {
        if (activeRenderer) {
            activeRenderer.setMap(null);
        }
        activeRenderer = null;
        activeMap = null;
    });
})();
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('uploadInvoiceModal');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        if (!button) return;

        var hareketId = button.getAttribute('data-hareket-id');
        var form = document.getElementById('invoiceUploadForm');
        if (!form || !hareketId) return;

        form.action = '/harekets/' + hareketId + '/files';
    });
});
</script>

@include ('transfert.createEdit-js') {{-- Including create blade file --}}
@include ('clients.clients-js') {{-- Including create blade file --}}
@include ('invoice.invoices-js') {{-- Including create blade file --}}
