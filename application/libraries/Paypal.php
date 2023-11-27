<?php
 

class Paypal {
    
   var $last_error;                 
   
   var $ipn_log;                   
   var $ipn_log_file;              
   var $ipn_response;                
   var $ipn_data = array();         
   
   var $fields = array();         

   
   function Paypal() {
      
      $this->paypal_url = 'https://www.paypal.com/cgi-bin/webscr';
      
      $this->last_error = '';
      
      $this->ipn_log_file = '.ipn_results.log';
      $this->ipn_log = true; 
      $this->ipn_response = '';
       
      $this->add_field('rm','2');           
      $this->add_field('cmd','_xclick'); 
      
   }
   
   function add_field($field, $value) {
     
      $this->fields["$field"] = $value;
   }

   function submit_paypal_post() {
  

      echo "<html>\n";
       
      echo "<body onLoad=\"document.forms['paypal_form'].submit();\">\n";
       
      echo "<form method=\"post\" name=\"paypal_form\" ";
      echo "action=\"".$this->paypal_url."\">\n";

      foreach ($this->fields as $name => $value) {
         echo "<input type=\"hidden\" name=\"$name\" value=\"$value\"/>\n";
      }
        
      echo "</form>\n";
      echo "</body></html>\n";
    
   }
   
   function validate_ipn() {      
 
		$raw_post_data = file_get_contents('php://input');
		$raw_post_array = explode('&', $raw_post_data);
		$myPost = array();
		foreach ($raw_post_array as $keyval) 
		{
		  $keyval = explode ('=', $keyval);
		  if (count($keyval) == 2)
			 $myPost[$keyval[0]] = urldecode($keyval[1]);
		}
		 
		$req = 'cmd=_notify-validate';
		if(function_exists('get_magic_quotes_gpc')) 
		{
		   $get_magic_quotes_exists = true;
		} 
		foreach ($myPost as $key => $value) 
		{        
		   if($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) 
		   { 
				$value = urlencode(stripslashes($value)); 
		   } 
		   else 
		   {
				$value = urlencode($value);
		   }
		   $req .= "&$key=$value";
		}
		  
		 
		$ch = curl_init($this->paypal_url);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
		 
		 
		if( !($res = curl_exec($ch)) ) {
			curl_close($ch);
			$this->write_log('curl error');
			exit;
		}
		curl_close($ch);
		  
		if (strcmp ($res, "VERIFIED") == 0) 
		{
			return true;
			 
			
		}
		else if (strcmp ($res, "INVALID") == 0) 
		{ 
			return false;
		}
      
   }
   
   function log_ipn_results($success) {
       
      if (!$this->ipn_log) return;  
       
      $text = '['.date('m/d/Y g:i A').'] - '; 
      
      if ($success) $text .= "SUCCESS!\n";
      else $text .= 'FAIL: '.$this->last_error."\n";
       
      $text .= "IPN POST Vars from Paypal:\n";
      foreach ($this->ipn_data as $key=>$value) {
         $text .= "$key=$value, ";
      }
  
      $text .= "\nIPN Response from Paypal Server:\n ".$this->ipn_response;
      
      $fp=fopen($this->ipn_log_file,'a');
      fwrite($fp, $text . "\n\n"); 

      fclose($fp);  
   }

   function dump_fields() {
  
      echo "<h3>Paypal->dump_fields() Output:</h3>";
      echo "<table width=\"95%\" border=\"1\" cellpadding=\"2\" cellspacing=\"0\">
            <tr>
               <td bgcolor=\"black\"><b><font color=\"white\">Field Name</font></b></td>
               <td bgcolor=\"black\"><b><font color=\"white\">Value</font></b></td>
            </tr>"; 
      
      ksort($this->fields);
      foreach ($this->fields as $key => $value) {
         echo "<tr><td>$key</td><td>".urldecode($value)."&nbsp;</td></tr>";
      }
 
      echo "</table><br>"; 
   }
}         


 
