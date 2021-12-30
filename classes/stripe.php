<?php
  
  require "vendor/phpmailer/stripe/stripe-php/init.php";

  class stripe{

	  public $name, $email, $password;

    public function __construct($db) {
     	  $this->conn = $db;
    }

    public function getStripeTokens($data){
      $curl = curl_init();

      curl_setopt_array($curl, array(
          CURLOPT_SSL_VERIFYPEER => true,
          CURLOPT_URL => 'https://api.stripe.com/v1/tokens',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_FRESH_CONNECT => true,
          CURLOPT_POSTFIELDS => http_build_query($data),
          CURLOPT_POST => true,
          CURLOPT_HTTPHEADER => [
              'Authorization: Bearer sk_test_51JokMVIDkCPjtVJbJvGB1LFnuDq24T7ewWpg6itEVagpWt7xOF3u9mzTBcdA6DGVwLczHyRIN66cIYBljgLpEU7600m6M6YYRf',
              'Content-type: application/x-www-form-urlencoded',
          ]
      ));

      $response = curl_exec($curl);

      curl_close($curl);
      return $response;
    }

    public function charge_amount($token,$amount,$email){
        $stripe = new \Stripe\StripeClient(
            'sk_test_51JokMVIDkCPjtVJbJvGB1LFnuDq24T7ewWpg6itEVagpWt7xOF3u9mzTBcdA6DGVwLczHyRIN66cIYBljgLpEU7600m6M6YYRf'
        );
        
        $customer = $stripe->customers->create([
        'email' => $email,
        'source' => $token,
        ]);

        $stripe->charges->create([
            'customer'=>$customer->id,
            'amount' => $amount*100,
            'currency' => 'usd',
            'description' => 'You have successfully transferred ' . $amount . ' United States dollars',
        ]);
        return $stripe;
    }

    public function addPayment($data,$customer_id,$amount){
        $number = $data['card[number]'];
        $expYear = $data['card[exp_year]'];
        $expMonth = $data['card[exp_month]'];
        $cvc = $data['card[cvc]'];
        $time = time();

        $sql = "INSERT INTO billing_info(customer_id, amount, card_number, exp_month, exp_year, cvc, created_at) VALUES($customer_id , '$amount' , '$number' , '$expMonth' , '$expYear' , '$cvc', '$time')";
        $stmt = $this->conn->prepare($sql);
        if($stmt->execute()){
            return true;
        }else{
            return false;
        }
    }

    public function updateBalance($amount,$customer_id){
      $query = "SELECT *,COUNT(email) AS num FROM users WHERE id= :customer_id";
      $insert = $this->conn->prepare($query);
      $insert->bindValue(':customer_id', $customer_id);
      $insert->execute();

      $row = $insert->fetch(PDO:: FETCH_ASSOC);
      if($row['num'] > 0){
        $balance = $row['balance'];
        $updatedBalance = $balance + $amount;
        $sql = "UPDATE users SET balance = '$updatedBalance' WHERE id = '$customer_id'";
        $stmt = $this->conn->prepare($sql);
        if($stmt->execute()){
          return true;
        }else{
          return false;
        }
      }else{
        return false;
      }
    }
  }
?>