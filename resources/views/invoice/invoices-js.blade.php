<script type="application/javascript">


	$(".addRow").click(function(){
 var $prototypeRow = $("#prototype").find("tr").eq(0);   
		$prototypeRow.clone(true).insertAfter($(this).closest("tr"));
		return false;
	});
	$(".removeRow").click(function(){
		if( confirm('Do you want remove row?') ){
			$(this).closest("tr").remove();
		}
		return false;
	});


</script>

<script type="application/javascript">
$(".selectInvoiceAgency").change(InvoiceAcente);  
  
    function InvoiceAcente()   {
      var Iacente_id = $(this).val();
      var token = $("input[name='_token']").val();  
      $.ajax({
          url: "{{route('selectInvoiceAcente')}}",
          method: 'POST',
          data: {id:Iacente_id, _token:token},
        
          success: function(data) {
            console.log(data);
              $("#invoicetittle").val(data[0].tittle); 
              $("#invoiceaddress").val(data[0].address);
              $("#invoicecity").val(data[0].city);
              $("#invoicecountry_id").val(data[0].country_id);
              $("#invoicevd").val(data[0].vd);
              $("#invoicevdno").val(data[0].vdno);
              },
           error: function(jqXHR, textStatus, errorThrown) { // What to do if we fail
                console.log(JSON.stringify(jqXHR));
                console.log("AJAX error: " + textStatus + ' : ' + errorThrown);
            }
         
      });
  }
  
 </script> 

<script type="application/javascript">
$('#addinvoice').on('show.bs.modal', accountlist);

    function accountlist()   {
     
        
      
      var token = $("input[name='_token']").val();
      $.ajax({
          url: "{{route('selectaccount')}}",
          method: 'POST',
          data: { _token:token},
        
          success: function(data) {
            //console.log(data);
           
            //$("select[name='id_state'").html(data.options);
            $.each(data.options, function(key, value) {   
               $("select[name='account_id'")
                 .append($("<option></option>")
                    .attr("value",key)
                    .text(value)); 
              });
            
          }
      });
  }
  




</script>
