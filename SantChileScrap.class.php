<?php

  /*
   *  Scraping  para Banco Santander
   *  idea basada en código hecho por José Tomás Albornoz <jojo@eljojo.net>
   * 
   * 
   *  autor: Rodrigo Ramírez Norambuena <decipher.hk@gmail.com>
   *  fecha: 2012-08-06
   */

class SantChileScrap {

  var $base = "";
  var $rut  = "";
  var $password = "";
  var $ch = "";
  var $isLogged = false;

  function SantChileScrap(){
    $this->base = 'https://www.santandermovil.cl/Moviles/'; 
    $this->ch = curl_init ($this->base);
  }

  public function getRut()
  {
    return $this->rut;
  }

  public function setRut($rut)
  {
    $this->rut =  $rut;
  }
  
  public function getPassword()
  {
    return $this->password;
  }

  public function setPassword($password)
  {
    $this->password = $password;
  }

  function explota($limite1, $limite2, $texto) {
    $explotado = explode($limite1, $texto);
    $explotado2 = explode($limite2, $explotado[1]);
    return trim($explotado2[0]);
  }

  function _login()
  {

    $c = $this->ch;

    curl_setopt ($c, CURLOPT_COOKIEJAR, 'cookie.txt');
    curl_setopt ($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
    $output = curl_exec ($c);

    curl_setopt($c, CURLOPT_URL, $this->base.'Login.aspx');
    $output = curl_exec ($c);

    //iniciamos sesion
    $url_action  =  $this->explota('action="', '"',  $output);

    $view_state = $this->explota('name="__VIEWSTATE" value="', '"', $output); 

    $data = array(
      '__VIEWSTATE'=>$view_state,
      '__EVENTTARGET'=>'',
      '__EVENTARGUMENT'=>'',
      'txtRUT'=> $this->getRut() ,
      'txtClave'=> $this->getPassword(),
      'ctl12'=>'Ingresar'
    );

    curl_setopt($c, CURLOPT_URL, $this->base.$url_action);
    curl_setopt($c, CURLOPT_POST, 1);
    curl_setopt($c, CURLOPT_POSTFIELDS, $data);

    $output = curl_exec ($c);

    $this->isLogged = true;
  }


  function saldoContable()
  {
    if ($this->isLogged === false)  $this->_login();
 
    $c = $this->ch;
    curl_setopt($c, CURLOPT_URL, $this->base.'Saldos.aspx');
    curl_setopt($c, CURLOPT_POST, 0);
    $output = curl_exec ($this->ch);

    $saldo_contable = $this->explota('Saldo Contable: $ ', '<br>', $output);
    $saldo_contable = str_replace('.', '', $saldo_contable);
    
    return $saldo_contable;
  }

  function saldoDisponible()
  {
    if ($this->isLogged === false)  $this->_login();
    $c = $this->ch;    
    curl_setopt($c, CURLOPT_URL, $this->base.'Saldos.aspx');
    curl_setopt($c, CURLOPT_POST, 0);
    $output = curl_exec ($c);

    $saldo = $this->explota('Saldo Disponible: $', '<br>', $output);
    $saldo = str_replace('.', '', $saldo);
    
    return $saldo;
  }


  function ultimosMovimientos()
  {
    if ($this->isLogged === false)  $this->_login();
    $c = $this->ch;    
    /* CARTOLA */
    curl_setopt($c, CURLOPT_URL, $this->base.'CtasUMovT.aspx');
    $output = curl_exec($c); 

    
    if(trim($output) != ''){
    
      $movimientos = $this->explota('<h2>Ultimos Movimientos</h2>', '<div align="Center">', $output);
      
      //procesamos los movimientos
      $tr = explode("\n\t", $movimientos);
      $tran = array();

      foreach($tr as $t) {
        $t = trim($t);
        if(empty($t)) continue;
        $tb = explode("<br>", $t);
        $m = explode(' | ', $tb[3]);
        $tran[] = array(trim($tb[0]), trim($tb[1]), trim($tb[2]), trim($m[0]), str_replace('<br>', '',$m[1]));
      }
    }

    return $tran;
  }
}

?>
