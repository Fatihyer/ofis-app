<div class="col-xl-4 col-lg-4 col-md-4 col-sm-4 grid-margin stretch-card">
            <div class="card border-primary" id="whatever">
               <div class="card-body">                          
              <h3 class="card-title">Solde</h3>
            <?php
            $num = $post->hareket
              ->groupBy('kur_id')
              ->map(function ($row) {
                  return $row->sum(function($h){
                      return $h->ab == 2 ? -$h->amount : $h->amount;
                  });
              });
                $karzarar=array();
                $eurocarpan=1;
                $usdcarpan=1; 
                 foreach ($num as $kz=>$key)
                 {
                   echo $key, $kurs[$kz],  " ";
                   $karzarar[$kz]=$key;
                     
                 } 
                 
               echo "<br/>TCMB:<br/>";  
                if(isset($usdkur->value)) {echo $usdkur->kur->short_name,": " , $usdcarpan=$usdkur->value,"<br/>";
                                          
                                          }  
                else { echo "Ajouter le taux USD <a href=\"",route('dovizs.index'),"?start_date=",date('Y-m-d', strtotime($post->start_date)),"\">Ajouter</a>"; }  
                 
                 if(isset($eurkur->value)) {echo $eurkur->kur->short_name,": " ,  $eurocarpan=$eurkur->value;
                                          
                                          }  
                else { echo "Ajouter le taux EUR <a href=\"",route('dovizs.index'),"?start_date=",date('Y-m-d', strtotime($post->start_date)),"\">Ajouter</a>"; }    // // 
                
               echo "<p><strong>K/Z=",(array_key_exists($eurid->value,$karzarar)? $karzarar[$eurid->value]:0)+ 
                          (array_key_exists($usdid->value,$karzarar)? $karzarar[$usdid->value]*$usdcarpan/$eurocarpan:0)+
                         (array_key_exists($tlid->value,$karzarar)? $karzarar[$tlid->value]/$eurocarpan:0)
                        ,"</strong></p>"; 
               
                 ?>   
                
                                   
                </div>
         </div>
      </div>
      
  <!-- /col --></div>