<?php 





 class PagamentoMP{
      // vamos dar alguns atributos a esta class
      // como :

     // O bot�o que ir� retornar da fun��o PagarMP (string)
     public $btn_mp;

     // Definiremos o bot�o que ir� retornar, se ser� uma lightbox // do mercado pago ou n�o, como padr�o ser� false. o user ser� 
     // redirecionado para o site do mercado pago
     private $lightbox = false;

     // Esta vari�vel recebe uma array com os dados da transa��o
     public $info = array();

     // Se for em modo de teste, esta vari�vel recebe true, caso 
     // contr�rio o sistema estar� em modo de produ��o
     private $sandbox = false;

     // Suas credenciais do mercado pago
     private $client_id = "2155770650472302";
     private $client_secret = "dGYYL3hMt4vB8DPS9HazNzbPQlJy7tI9";

     
     public function PagarMP($ref , $nome , $valor , $url){
     // iniciando as credenciais do MP
     // Os valores de client_id e client_secret s�o informados  aqui
     $mp = new MP($this->client_id, $this->client_secret);
     
    $preference_data = array(   
            // dados do produto para pagamento 
            "items" => array(
                array(
                    "id"          => 0001,
                    "title"       => $nome,
                    "currency_id" => "BRL",
                    "picture_url" => "https://www.mercadopago.com/org-img/MP3/home/logomp3.gif",
                    "description" => $nome,
                    "quantity"    => 1,
                    "unit_price"  => $valor
                )
            ),

            "back_urls" => array(
               "success" => $url."/notifica.php?success",
               "failure" => $url."/notifica.php?failure",
               "pending" => $url."/notifica.php?pending"
             ),

            "notification_url" => $url."/notifica.php",
            "external_reference" => $ref
        );

      $preference = $mp->create_preference($preference_data);
     
      // Criar link para o bot�o de pagamento normal ou sandbox
        if($this->sandbox):
         //sandbox
         $mp->sandbox_mode(TRUE);
         $link = $preference["response"]["sandbox_init_point"];
            else:
            // normal em produ��o
            $mp->sandbox_mode(FALSE);
            $link = $preference["response"]["init_point"];
           endif;

        $this->btn_mp = '<a class="btn btn-success" id="btnMP" target="_blank" href="'.$link.'" '; 
        $this->btn_mp .= 'name="MP-Checkout" >Pagar</a>';


         if($this->lightbox):
            $this->btn_mp .= '<script type="text/javascript" src="//secure.mlstatic.com/mptools/render.js"></script>';
          endif;

     
        return $this->btn_mp;
     
     
     }


   public function Retorno($id , $conn){
      // iniciando as credenciais do MP
      $mp = new MP($this->client_id, $this->client_secret);
      
      
      // Dentro da fun��o Retorno
      $params = ["access_token" => $mp->get_access_token()];
      // params recebe o token

      $topic = 'payment';
      
      if ($topic == 'payment'){

      $payment_info = $mp->get("/collections/notifications/" . $id, $params, false);
      
      $merchant_order_info = $mp->get("/merchant_orders/" . $payment_info["response"]["collection"]["merchant_order_id"], $params, false);
      
      }

      
      switch ($payment_info["response"]["collection"]["status"]):

         case "approved"     : $status = "Aprovado";           break;
         case "pending"      : $status = "Pendente";           break;
         case "in_process"   : $status = "An�lise";            break;
         case "rejected"     : $status = "Rejeitado";          break;
         case "refunded"     : $status = "Devolvido";          break;
         case "cancelled"    : $status = "Cancelado";          break;
         case "in_mediation" : $status = "Media��o";           break;
   
     endswitch;
      
      
      switch ($payment_info["response"]["collection"]["payment_type"]):
      
         case "ticket"        : $forma = "Boleto";
        break;
         case "account_money" : $forma = "Saldo MP";
        break;
         case "credit_card"   : $forma = "Cart�o de Cr�dito";
        break;
         default : $forma =   $payment_info["response"]["collection"]["payment_type"];
         
      endswitch;
        
        
     $ref = $payment_info["response"]["collection"]["external_reference"];
        
      $query = mysqli_query($conn,"UPDATE fatura SET status='$status' , forma='$forma' WHERE ref='$ref'");
      if($query){
         return true;
      }
      
      
      
     }




   }

?>