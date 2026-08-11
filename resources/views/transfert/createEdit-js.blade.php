


<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=places&loading=async" async defer></script>
 
<script>
  
 

  let fromAutocomplete, toAutocomplete;

    $('#addtransfert').on('shown.bs.modal', function () {
        const fromInput = document.getElementById('from');
        const toInput = document.getElementById('target');

        // Check and initialize Autocomplete instances
        if (!fromAutocomplete && fromInput) {
            fromAutocomplete = new google.maps.places.Autocomplete(fromInput, {
                componentRestrictions: { country: 'fr' } // Optional restriction to France
            });
        }

        if (!toAutocomplete && toInput) {
            toAutocomplete = new google.maps.places.Autocomplete(toInput, {
                componentRestrictions: { country: 'fr' } // Optional restriction to France
            });
        }
    });


  $(document).ready(function(){
    
 
    $('[data-bs-toggle="popover"]').popover();

    $("select[name='provider_id']").change(firmajax);

    $('#edittransfert, #editdispo').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget); 
      var modal = $(this);

      var data = {
        recipient: button.data('id'),
        servicetype: button.data('servicetype'),
        date: button.data('date'),
        dateend: button.data('dateend'),
        ofisstart: button.data('ofisdate'),
        ofistime: button.data('ofistime'),
        time: button.data('time'),
        endtime: button.data('endtime'),
        from: button.data('from'),
        to: button.data('to'),
        vehicule: button.data('vehicule'),
        vehicleLocked: button.data('vehicle-locked'),
        vehicleProvider: button.data('vehicle-provider'),
        externalVehiclePrice: button.data('external-vehicle-price'),
        externalVehicleNote: button.data('external-vehicle-note'),
        pax: button.data('pax'),
        driver: button.data('driver'),
        comments: button.data('comments'),
        firma: button.data('firma'),
        mission: button.data('mission'),
        status: button.data('status')
      };

      updateModalContent(modal, data);

      const fromInput = modal.find('#data-from')[0]; // Make sure #editFrom is present in the modal
      const toInput = modal.find('#data-to')[0]; // Make sure #editTo is present in the modal

    // Check if autocomplete instances are already initialized to avoid re-initialization
    if (!window.fromAutocomplete && fromInput) {
        window.fromAutocomplete = new google.maps.places.Autocomplete(fromInput, {
            componentRestrictions: { country: 'fr' } // Optional: restrict to France
        });
    }

    if (!window.toAutocomplete && toInput) {
        window.toAutocomplete = new google.maps.places.Autocomplete(toInput, {
            componentRestrictions: { country: 'fr' } // Optional: restrict to France
        });
    }

      var token = $("input[name='_token']").val();  
      $.ajax({
        url: "{{ route('selectAjaxFirma') }}",
        method: 'POST',
        data: { id: data.firma, _token: token },
        success: function(response) {
          updateDriverOptions(modal.find("select[name='driver_id']"), response.options, data.driver, data.status);
        },
        error: function(xhr, status, error) {
          console.error('Error:', error);
          console.error('Status:', status);
          console.dir(xhr);
        }
      });


    });
  });

  function firmajax() {
      var provider_id = $(this).val();
      var token = $("input[name='_token']").val();

      $("select[name='driver_id']").html('');

      $.ajax({
        url: "{{ route('selectAjaxFirma') }}",
        method: 'POST',
        data: { id: provider_id, _token: token },
        success: function(response) {
          updateDriverOptions($("select[name='driver_id']"), response.options);
        },
        error: function(xhr, status, error) {
          console.error('Error:', error);
          console.error('Status:', status);
          console.dir(xhr);
        }
      });
  }

  function updateModalContent(modal, data) {
    modal.find('.modal-title').text('Edit ' + data.recipient);
    modal.find('.modal-body #data-id').val(data.recipient);
    modal.find('.modal-body #data-servicetype').val(data.servicetype);
    modal.find('.modal-body #data-ofisdate').val(data.ofisstart);
    modal.find('.modal-body #data-ofistime').val(data.ofistime);
    modal.find('.modal-body #data-date').val(data.date);    
    modal.find('.modal-body #data-dateend').val(data.dateend);
    modal.find('.modal-body #data-time').val(data.time);
    modal.find('.modal-body #data-endtime').val(data.endtime);
    modal.find('.modal-body #data-from').val(data.from);
    modal.find('.modal-body #data-to').val(data.to);
    modal.find('.modal-body #data-vehicule').val(data.vehicule);
    modal.find('.modal-body input[name="vehicle_locked"][type="checkbox"]').prop('checked', data.vehicleLocked === 1 || data.vehicleLocked === '1' || data.vehicleLocked === true);
    modal.find('.modal-body #data-vehicle-provider').val(data.vehicleProvider || '');
    modal.find('.modal-body #data-external-vehicle-price').val(data.externalVehiclePrice || '');
    modal.find('.modal-body #data-external-vehicle-note').val(data.externalVehicleNote || '');
    modal.find('.modal-body #data-pax').val(data.pax);
    modal.find('.modal-body #data-driver').val(data.driver);
    modal.find('.modal-body #data-comments').val(data.comments);
    modal.find('.modal-body #data-firma').val(data.firma);

    var checkbox = modal.find('.modal-body input[name="mission"]');
    checkbox.prop('checked', data.mission !== 0);
  }

  function updateDriverOptions(element, options, selectedDriver = null, status = null) {
    var sortedOptions = Object.entries(options).sort(function(a, b) {
      return a[1].localeCompare(b[1]);
    });

    $.each(sortedOptions, function(key, value) {
      element
        .append($("<option></option>")
        .attr("value", value[0])
        .text(value[1]));
    });

    if (selectedDriver) {
      element.val(selectedDriver);
    }
    @hasanyrole('ofis|transport')
    // Disable fields if status is 3
    if (status == 3) {
      element.prop('disabled', true);
      $('#data-from').prop('disabled', true);
      $('#data-to').prop('disabled', true);
      $('#data-date').prop('disabled', true);  
      $('#data-time').prop('disabled', true);    
      $('#data-dateend').prop('disabled', true);
      $('#data-firma').prop('disabled', true);
      $('#data-endtime').prop('disabled', true);
      $('#data-vehicule').prop('disabled', true);
      $('#data-comments').prop('disabled', true);
      $('#data-vehicle-provider').prop('disabled', true);
      $('#data-external-vehicle-price').prop('disabled', true);
      $('#data-external-vehicle-note').prop('disabled', true);
      $('#data-servicetype').prop('disabled', true);
      $('#data-pax').prop('disabled', true);
    } else {
      element.prop('disabled', false);
      $('#data-from').prop('disabled', false);
      $('#data-to').prop('disabled', false);
      $('#data-vehicle-provider').prop('disabled', false);
      $('#data-external-vehicle-price').prop('disabled', false);
      $('#data-external-vehicle-note').prop('disabled', false);
    }
    @endhasrole
  }

  document.getElementById('transferForm').addEventListener('submit', function(event) {
    var startTime = document.getElementById('start_time').value;
    var endTime = document.getElementById('end_time').value;
    
    if (startTime >= endTime) {
        event.preventDefault();
        alert('Finish Time cannot be before or equal to Start Time.');
    }
  });
</script>
@include('transfert.partials.vehicle-availability-script')
