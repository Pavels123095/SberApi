<?php

class SberApi {

  public $vars = array();
  public $orderId;
  public $testUrl = 'https://3dsec.sberbank.ru/';
  public $warUrl = 'https://securecardpayment.ru/';

  public function __construct($array) {
    $this->Init($array);
  }

  public function Init($array = array()) {
    if (empty($vars['token'])) {
      $this->vars['userName'] = $array['user'];
      $this->vars['password'] = $array['password'];
    } else {
      $this->vars['token'] = $array['token'];
    }
  }

  public function registerPay($amount, $returnUrl, $successUrl, $description) {
    $vars = $this->vars;
    /* ID заказа в магазине */
    $vars['orderNumber'] = $this->RandomString();
    
    /* Сумма заказа в копейках */
    $vars['amount'] = $amount * 100;
    
    /* URL куда клиент вернется в случае успешной оплаты */
    $vars['returnUrl'] = $successUrl;
      
    /* URL куда клиент вернется в случае ошибки */
    $vars['failUrl'] = $returnUrl;
    
    /* Описание заказа, не более 24 символов, запрещены % + \r \n */
    $vars['description'] = strip_tags($description);

    $url = $this->warUrl.'payment/rest/register.do?' . http_build_query($vars);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $res = curl_exec($ch);
    curl_close($ch);

    $res = json_decode($res, JSON_OBJECT_AS_ARRAY);
    if (!empty($res)) {
      $return = $this->GoToPay($res);
    } else {
      $return = $url;
    }

    return $return;
  }

  public function GoToPay($res) {

    if (empty($res['orderId'])){
      /* Возникла ошибка: */
      return $res;		

    } else {
      /* Успех: */
      /* Тут нужно сохранить ID платежа в своей БД - $res['orderId'] */
      $this->orderId = $res['orderId'];
      /* Перенаправление клиента на страницу оплаты */
      //header('Location: ' . $res['formUrl'], true);
      // /* Или на JS */
      // echo '<script>document.location.href = "' . $res['formUrl'] . '"</script>';

      return $res['formUrl'];

    }
  }

  public function getOrderId() {
    $orderId = $this->orderId;
    return $orderId;
  }

  public function getInfoOrder() {
    $vars = $this->vars;
    $vars['orderId'] = $this->getOrderId();
    
    $ch = curl_init($this->warUrl.'/payment/rest/getOrderStatusExtended.do?' . http_build_query($vars));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $res = curl_exec($ch);
    curl_close($ch);
    
    $res = json_decode($res, JSON_OBJECT_AS_ARRAY);
  }

  public function removeOrderId() {
    $this->orderId = '';
  }

  public function RandomString()
  {
      $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $string = '';
      for ($i = 0; $i < 5; $i++) {
          $string .= $characters[rand(0, strlen($characters))];
      }
      return $string;
  }
}

 
